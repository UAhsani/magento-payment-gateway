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
use Magento\Framework\View\Asset\Repository;

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
     * @var Repository
     */
    private $assetRepo;

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
     * @param Repository $assetRepo
     */
    public function __construct(
        ConfigInterface $config,
        SessionManagerInterface $session,
        UrlInterface $urlBuilder,
        ResolverInterface $localeResolver,
        BooleanUtils $booleanUtils,
        ProductMetadataInterface $productMetadata,
        ModuleList $moduleList,
        Repository $assetRepo
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->localeResolver = $localeResolver;
        $this->booleanUtils = $booleanUtils;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->assetRepo = $assetRepo;
    }

    /**
     * Get Geidea config
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->session->getStoreId();

        $baseMediaUrl = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
        $baseMediaUrl .= 'geidea/';

        $checkoutIconUrl = '';
        $relativeCheckoutIconUrl = $this->config->getValue("checkoutIcon", $storeId);
        if ($relativeCheckoutIconUrl == '') {
            $checkoutIconUrl = $this->assetRepo->getUrl("Geidea_Payment::images/geidea-logo.svg");
        } else {
            $checkoutIconUrl = $baseMediaUrl . $relativeCheckoutIconUrl;
        }

        $merchantLogoUrl = '';
        $relativeMerchantLogoUrl = $this->config->getValue("merchantLogo", $storeId);
        if ($relativeMerchantLogoUrl != '') {
            $origMerchantLogoUrl = $baseMediaUrl . $relativeMerchantLogoUrl;
            // Force https for Geidea Gateway
            $merchantLogoUrl = str_replace('http://', 'https://', $origMerchantLogoUrl);
        }

        $origCallbackUrl = $this->urlBuilder->getUrl($this->config->getValue("callbackUrl", $storeId));
        $callbackUrl = str_replace('http://', 'https://', $origCallbackUrl);

        return [
            'payment' => [
                self::CODE => [
                    'clientConfig' => [
                        'merchantKey' => $this->config->getValue("merchantKey", $storeId),
                        'headerColor' => $this->config->getValue("headerColor", $storeId),
                        'reserveUrl' => $this->urlBuilder->getUrl($this->config->getValue("reserveUrl", $storeId)),
                        'authorizeUrl' => $this->urlBuilder->getUrl($this->config->getValue("authorizeUrl", $storeId)),
                        'callbackUrl' => $callbackUrl,
                        'language' => $this->localeResolver->getLocale() == 'ar_SA' ? 'ar' : 'en',
                        'receiptEnabled' => $this->booleanUtils->toBoolean(
                            $this->config->getValue("receiptEnabled", $storeId)
                        ),
                        'integrationType' => 'plugin',
                        'name' => 'Magento',
                        'version' => $this->productMetadata->getVersion(),
                        'pluginVersion' => $this->moduleList->getOne('Geidea_Payment')['setup_version'],
                        'partnerId' => $this->config->getValue("partnerId", $storeId),
                        'checkoutIcon' => $checkoutIconUrl,
                        'merchantLogo' => $merchantLogoUrl
                    ],
                    'vaultCode' => self::VAULT_CODE
                ]
            ]
        ];
    }
}
