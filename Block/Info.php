<?php
namespace Geidea\Payment\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{
    private $fields;
    
    protected function getLabel($field)
    {
        if (!$this->fields) {
            $this->fields = [
                'orderId' => __('Order Id'),
                'currency' => __('Currency'),
                'detailedStatus' => __('Status'),
                'customerEmail' => __('Customer Email'),
                'totalAuthorizedAmount' => __('Authorized Amount'),
                'totalCapturedAmount' => __('Captured Amount'),
                'totalRefundedAmount' => __('Refunded Amount'),
                'createdDate' => __('Created Date'),
                'updatedDate' => __('Updated Date')
            ];
        }
        
        return $this->fields[$field] ?? '';
    }

    protected function getValueView($field, $value)
    {
        switch ($field) {
            case 'totalAuthorizedAmount':
            case 'totalCapturedAmount':
            case 'totalRefundedAmount':
                return number_format($value, 2);
        }
        
        return parent::getValueView($field, $value);
    }
}
