<?php

namespace Geidea\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

use Geidea\Payment\Gateway\Config\Config;

class AvailabilityValidator extends AbstractValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $config
    ) {
        $this->config = $config;
        parent::__construct($resultFactory);
    }
    
    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $quote = $validationSubject['quote'];

        if ($quote === null) {
            return $this->createResult(false);
        }
        
        $storeId = $quote->getStoreId();
        $amount = $quote->getBaseGrandTotal();

        $minOrderTotal = $this->config->getValue('minOrderTotal', $storeId);
        if (!empty($minOrderTotal) && $amount < $minOrderTotal) {
            return $this->createResult(false);
        }
        
        $maxOrderTotal = $this->config->getValue('maxOrderTotal', $storeId);
        if (!empty($maxOrderTotal) && $amount > $maxOrderTotal) {
            return $this->createResult(false);
        }

        return $this->createResult(true);
    }
}
