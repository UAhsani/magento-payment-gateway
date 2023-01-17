<?php
namespace Geidea\Payment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\HTTP\ClientFactory;

class Client implements ClientInterface
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        Logger $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     *  Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return mixed
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => $transferObject->getBody(),
            'request_uri' => $transferObject->getUri()
        ];
        $result = [];
        $client = $this->clientFactory->create();

        $client->setHeaders($transferObject->getHeaders());

        $client->setCredentials($transferObject->getAuthUsername(), $transferObject->getAuthPassword());

        $client->setOptions($transferObject->getClientConfig());

        switch ($transferObject->getMethod()) {
            case "POST":
                break;
            default:
                throw new \LogicException(
                    sprintf(
                        'Unsupported HTTP method %s',
                        $transferObject->getMethod()
                    )
                );
        }

        try {
            $body = $transferObject->getBody();
            $client->post($uri, $body);

            $result = json_decode($client->getBody(), true);
            $log['response'] = $result;
        } catch (\Exception $e) {
            throw new \Magento\Payment\Gateway\Http\ClientException(
                __($e->getMessage())
            );
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
