<?php
namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Framework\Exception\LocalizedException;

class RefundBodyBuilder implements BuilderInterface
{
    const ORDER_ID = 'orderId';

    private $subjectReader;

    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $invoice = $payment->getCreditmemo()->getInvoice();

        $baseGrandTotal = $invoice->getBaseGrandTotal();

        $baseTotalRefunded = $invoice->getBaseTotalRefunded();

        if ($baseGrandTotal != $baseTotalRefunded) {
            $exc = new LocalizedException(__("You can only refund the entire amount."));
            
            throw $exc;
        }

        $result = [
            'body' => [
                self::ORDER_ID => $payment->getAdditionalInformation(self::ORDER_ID)
            ]
        ];

        return $result;
    }
}
