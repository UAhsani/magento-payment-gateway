<?php
namespace Geidea\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;

class GeideaConfigProvider implements ConfigProviderInterface {

    const CODE = 'geidea_payment';

    private $config;
    private $session;
    private $urlBuilder;

    public function __construct(
        ConfigInterface $config,
        SessionManagerInterface $session,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig() {

        $storeId = $this->session->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'clientConfig' => [
                        'merchantKey' => $this->config->getValue("merchantKey", $storeId),
                        'logoUrl' => $this->config->getValue("logoUrl", $storeId),
                        'headerColor' => $this->config->getValue("headerColor", $storeId),
                        'reserveUrl' => $this->urlBuilder->getUrl($this->config->getValue("reserveUrl", $storeId)),
                        'authorizeUrl' => $this->urlBuilder->getUrl($this->config->getValue("authorizeUrl", $storeId)),
                        'callbackUrl' => $this->urlBuilder->getUrl($this->config->getValue("callbackUrl", $storeId))
                    ]
                ]
            ]
        ];
    }
}
