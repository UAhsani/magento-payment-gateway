<?php
namespace Geidea\Payment\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class VaultSaleHandler implements HandlerInterface
{
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

    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        
        $payment = $paymentDO->getPayment();

        $this->setPaymentData($payment, $response);

        $payTransaction = null;
        foreach ($response['order']['transactions'] as $transaction) {
            if (mb_strtolower($transaction["type"]) == "pay" && mb_strtolower($transaction["status"]) == "success") {
                $payTransaction = $transaction;
            }
        }

        $payment
            ->setTransactionId($payTransaction['transactionId'])
            ->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo('Response', json_encode($response));
    }
}
