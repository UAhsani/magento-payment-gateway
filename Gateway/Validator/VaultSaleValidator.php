<?php
namespace Geidea\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class VaultSaleValidator extends AbstractValidator
{
    public function __construct(ResultInterfaceFactory $resultFactory)
    {
        parent::__construct($resultFactory);
    }

    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        if ($response["responseCode"] != '000')
            return $this->createResult(false, [__(sprintf("%s: %s; %s: %s",
                $response["responseCode"],
                $response["responseMessage"],
                $response["detailedResponseCode"],
                $response["detailedResponseMessage"]))]);
        
        $order = $response["order"];

        if (mb_strtolower($order["status"]) != "success")
            return $this->createResult(false, [__(sprintf("Incorrect order status: %s", $order["status"]))]);

        if (mb_strtolower($order["detailedStatus"]) != "paid")
            return $this->createResult(false, [__(sprintf("Incorrect status: %s", $order["detailedStatus"]))]);

        $transactions = $order["transactions"];

        $payTransaction = null;
        foreach ($transactions as $transaction)
            if (mb_strtolower($transaction["type"]) == "pay" && mb_strtolower($transaction["status"]) == "success")
                $payTransaction = $transaction;
        
        if (!$payTransaction)
            return $this->createResult(false, [__("Pay transaction not found")]);

        return $this->createResult(true);
    }
}
