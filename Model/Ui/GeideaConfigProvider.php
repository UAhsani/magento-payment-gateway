<?php
namespace Geidea\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;

class GeideaConfigProvider implements ConfigProviderInterface
{

    public const CODE = 'geidea_payment';
    public const VAULT_CODE = 'geidea_payment_vault';

    /**
     * @var Config
     */
    private $ConfigInterface;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var BooleanUtils
     */
    private $booleanUtils;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * Constructor
     *
     * @param ConfigInterface $config
     * @param SessionManagerInterface $session
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $localeResolver
     * @param BooleanUtils $booleanUtils
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleList $moduleList
     */
    public function __construct(
        ConfigInterface $config,
        SessionManagerInterface $session,
        UrlInterface $urlBuilder,
        ResolverInterface $localeResolver,
        BooleanUtils $booleanUtils,
        ProductMetadataInterface $productMetadata,
        ModuleList $moduleList
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->localeResolver = $localeResolver;
        $this->booleanUtils = $booleanUtils;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
    }

    /**
     * Get Geidea config
     *
     * @return array
     */
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
                        'language' => $this->localeResolver->getLocale() == 'ar_SA' ? 'ar' : 'en',
                        'receiptEnabled' => $this->booleanUtils->toBoolean(
                            $this->config->getValue("receiptEnabled", $storeId)
                        ),
                        'integrationType' => 'plugin',
                        'name' => 'Magento',
                        'version' => $this->productMetadata->getVersion(),
                        'pluginVersion' => $this->moduleList->getOne('Geidea_Payment')['setup_version'],
                        'partnerId' => $this->config->getValue("partnerId", $storeId)
                    ],
                    'vaultCode' => self::VAULT_CODE
                ]
            ]
        ];
    }
}
