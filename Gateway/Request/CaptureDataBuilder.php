<?php
namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class CaptureDataBuilder implements BuilderInterface
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

        $result = [
            self::ORDER_ID => $payment->getAdditionalInformation(self::ORDER_ID)
        ];

        return $result;
    }
}
