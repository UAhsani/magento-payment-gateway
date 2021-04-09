<?php

namespace Geidea\Payment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, \Geidea\Payment\Model\Ui\GeideaConfigProvider::CODE, $pathPattern);
    }

    public function getValue($name, $storeId = null)
    {
        return parent::getValue($name, $storeId);
    }
}
