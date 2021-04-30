<?php
namespace Geidea\Payment\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

use Geidea\Payment\Ui\DataProvider\GeideaTokens\ListingDataProvider;

class AddAttributesToUiDataProvider
{
    public function __construct() { }

    public function afterGetSearchResult(ListingDataProvider $subject, SearchResult $result)
    {
        if ($result->isLoaded()) {
            return $result;
        }

        $result->getSelect()->joinLeft(
            ['cus' => $result->getTable('customer_entity')],
            'cus.entity_id = main_table.customer_id',
            ['customer_email' => 'cus.email']
        );

        return $result;
    }
}