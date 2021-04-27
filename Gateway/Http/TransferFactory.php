<?php
namespace Geidea\Payment\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{
    private $transferBuilder;

    public function __construct(
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
    }

    public function create(array $request)
    {
        return $this->transferBuilder
            ->setMethod($request['method'])
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setBody(json_encode($request['body'], JSON_UNESCAPED_SLASHES))
            ->setAuthUsername($request['auth']['username'])
            ->setAuthPassword($request['auth']['password'])
            ->setUri($request['url'])
            ->build();
    }
}
