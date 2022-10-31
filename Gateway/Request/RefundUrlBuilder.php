<?php
namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class RefundUrlBuilder implements BuilderInterface
{
    public const URL = 'url';
    public const METHOD = 'method';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param ConfigInterface $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigInterface $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
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
