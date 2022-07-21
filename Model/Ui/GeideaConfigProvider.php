<?php
namespace Geidea\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;

class GeideaConfigProvider implements ConfigProviderInterface
{

    const CODE = 'geidea_payment';
    const VAULT_CODE = 'geidea_payment_vault';

    private $config;
    private $session;
    private $urlBuilder;
    private $localeResolver;

    public function __construct(
        ConfigInterface $config,
        SessionManagerInterface $session,
        UrlInterface $urlBuilder,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->localeResolver = $localeResolver;
    }

    public function getConfig()
    {

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
                        'callbackUrl' => $this->urlBuilder->getUrl($this->config->getValue("callbackUrl", $storeId)),
                        'language' => $this->localeResolver->getLocale() == 'ar_SA' ? 'ar' : 'en'
                    ],
                    'vaultCode' => self::VAULT_CODE
                ]
            ]
        ];
    }
}
