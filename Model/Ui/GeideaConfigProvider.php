<?php

namespace Geidea\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;

use Geidea\Payment\Gateway\Config\Config;

class GeideaConfigProvider implements ConfigProviderInterface {

    const CODE = 'geidea_payment';

    private $config;
    private $session;
    private $urlBuilder;

    public function __construct(
        Config $config,
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
                    'title' => $this->config->getValue("title", $storeId),
                    'reserveUrl' => $this->urlBuilder->getUrl($this->config->getValue("reserveUrl", $storeId)),
                    'authorizeUrl' => $this->urlBuilder->getUrl($this->config->getValue("authorizeUrl", $storeId)),
                    'callbackUrl' => $this->urlBuilder->getUrl($this->config->getValue("callbackUrl", $storeId)),
                    'clientConfig' => [
                        'merchantKey' => $this->config->getValue("merchantKey", $storeId),
                        'geideaSdkUrl' => $this->config->getValue("geideaSdkUrl", $storeId)
                    ]
                ]
            ]
        ];
    }
}
