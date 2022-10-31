<?php
namespace Geidea\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CaptureValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        if ($response["responseCode"] != '000') {
            return $this->createResult(
                false,
                [sprintf(
                    "%s: %s; %s: %s",
                    $response["responseCode"],
                    $response["responseMessage"],
                    $response["detailedResponseCode"],
                    $response["detailedResponseMessage"]
                )]
            );
        }
        
        $order = $response["order"];

        if (mb_strtolower($order["status"]) != "success") {
            return $this->createResult(false, [__('Incorrect order status: %1', $order["status"])]);
        }

        if (mb_strtolower($order["detailedStatus"]) != "captured") {
            return $this->createResult(false, [__('Incorrect status: %1', $order["detailedStatus"])]);
        }

        $transactions = $order["transactions"];

        $captureTransaction = null;
        foreach ($transactions as $transaction) {
            if (mb_strtolower($transaction["type"]) == "capture" &&
                mb_strtolower($transaction["status"]) == "success"
            ) {
                $captureTransaction = $transaction;
            }
        }
        
        if (!$captureTransaction) {
            return $this->createResult(false, [__("Capture transaction not found")]);
        }

        return $this->createResult(true);
    }
}
