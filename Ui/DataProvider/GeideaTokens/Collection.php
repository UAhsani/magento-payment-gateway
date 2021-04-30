<?php
namespace Geidea\Payment\Ui\DataProvider\GeideaTokens;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        $this->addFilterToMap('customer_email', 'cus.email');
        parent::_initSelect();
    }
}