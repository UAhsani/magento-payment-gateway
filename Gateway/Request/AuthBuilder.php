<?php
namespace Geidea\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class AuthBuilder implements BuilderInterface
{
    const USERNAME = 'username';
    const PASSWORD = 'password';

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
            'auth' => [
                self::USERNAME => $this->config->getValue('merchantKey', $storeId),
                self::PASSWORD => $this->config->getValue('merchantPassword', $storeId)
            ]
        ];

        return $result;
    }
}
