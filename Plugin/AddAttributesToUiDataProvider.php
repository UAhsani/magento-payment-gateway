<?php
namespace Geidea\Payment\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

use Geidea\Payment\Gateway\Config\Config;
use Geidea\Payment\Ui\DataProvider\GeideaTokens\ListingDataProvider;

class AddAttributesToUiDataProvider
{
    private $config;
    
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

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

        $result
            ->getSelect()
            ->where('main_table.payment_method_code = "' . \Geidea\Payment\Model\Ui\GeideaConfigProvider::CODE . '"');
        $result
            ->getSelect()
            ->where('main_table.is_active = "1"');

        return $result;
    }

    public function afterGetData(ListingDataProvider $subject, $data)
    {
        foreach ($data['items'] as &$item) {
            $decoded = json_decode($item['details'], true);

            $item['card'] = __(
                "%1 ending in %2 (expires %3)",
                $this->getBrandByCode($decoded['type']),
                $decoded['maskedCC'],
                $decoded['expirationDate']
            );
        }
        
        return $data;
    }

    private function getBrandByCode($code)
    {
        $mapper = $this->config->getCcTypesMapper();

        return array_search($code, $mapper) ?: "card";
    }
}
