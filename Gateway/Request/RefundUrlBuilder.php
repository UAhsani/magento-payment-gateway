<?php
namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class RefundUrlBuilder implements BuilderInterface
{
    const URL = 'url';
    const METHOD = 'method';

    private $subjectReader;
    private $config;

    public function __construct(
        SubjectReader $subjectReader,
        ConfigInterface $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $storeId = $payment->getOrder()->getStoreId();

        $result = [
            self::URL => $this->config->getValue('refundUrl', $storeId),
            self::METHOD => "POST"
        ];

        return $result;
    }
}
