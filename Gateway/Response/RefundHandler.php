<?php
namespace Geidea\Payment\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundHandler implements HandlerInterface
{
    /**
     * @param InfoInterface $payment
     * @param array $payload
     */
    private function setPaymentData($payment, $payload)
    {
        $order = $payload['order'];
        
        $payment->setAdditionalInformation('orderId', $order['orderId']);
        $payment->setAdditionalInformation('currency', $order['currency']);
        $payment->setAdditionalInformation('detailedStatus', $order['detailedStatus']);
        $payment->setAdditionalInformation('customerEmail', $order['customerEmail']);
        $payment->setAdditionalInformation('totalAuthorizedAmount', $order['totalAuthorizedAmount']);
        $payment->setAdditionalInformation('totalCapturedAmount', $order['totalCapturedAmount']);
        $payment->setAdditionalInformation('totalRefundedAmount', $order['totalRefundedAmount']);
        $payment->setAdditionalInformation('createdDate', $order['createdDate']);
        $payment->setAdditionalInformation('updatedDate', $order['updatedDate']);
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        
        $payment = $paymentDO->getPayment();

        $this->setPaymentData($payment, $response);

        $captureTransaction = null;
        foreach ($response['order']['transactions'] as $transaction) {
            if (mb_strtolower($transaction["type"]) == "refund" &&
                mb_strtolower($transaction["status"]) == "success"
            ) {
                $captureTransaction = $transaction;
            }
        }

        $payment
            ->setTransactionId($captureTransaction['transactionId'])
            ->setIsTransactionClosed(0)
            ->setParentTransactionId($payment->getAuthorizationTransaction()->getTxnId())
            ->setShouldCloseParentTransaction(true)
            ->setTransactionAdditionalInfo('Response', json_encode($response));
    }
}
