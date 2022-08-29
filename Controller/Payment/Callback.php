<?php

namespace Geidea\Payment\Controller\Payment;

use Geidea\Payment\Gateway\Config\Config;
use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Psr\Log\LoggerInterface;

class Callback extends AppAction implements
    CsrfAwareActionInterface,
    HttpPostActionInterface
{
    private $config;
    private $orderRepository;
    private $orderFactory;
    private $orderSender;
    private $managerInterface;
    private $paymentTokenFactory;
    private $logger;
    
    public function __construct(
        Context $context,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        OrderFactory $orderFactory,
        OrderSender $orderSender,
        ManagerInterface $managerInterface,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->managerInterface = $managerInterface;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->logger = $logger;
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function checkSignature($payload)
    {
        $merchant_key = $this->config->getValue('merchantKey');
        $api_pass = $this->config->getValue('merchantPassword');
        
        $order = $payload['order'];

        $amount = number_format($order['amount'], 2);
        $currency = $order['currency'];
        $order_id = $order['orderId'];
        $order_status = $order['status'];
        $merchant_reference_id = $order['merchantReferenceId'];
        
        $result_string = $merchant_key.$amount.$currency.$order_id.$order_status.$merchant_reference_id;
        
        $hash = hash_hmac('sha256', $result_string, $api_pass, true);
        
        $result_signature = base64_encode($hash);

        if ($result_signature != $payload['signature']) {
            throw new LocalizedException(__('Invalid signature'));
        }
    }

    private function setPaymentData($payment, $payload)
    {
        $order = $payload['order'];
        
        $payment->setAdditionalInformation('orderId', $order['orderId']);
        $payment->setAdditionalInformation('currency', $order['currency']);
        $payment->setAdditionalInformation('detailedStatus', $order['detailedStatus']);
        $payment->setAdditionalInformation('customerEmail', $order['customerEmail']);
        $payment->setAdditionalInformation('totalAuthorizedAmount', $order['totalAuthorizedAmount']);
        $payment->setAdditionalInformation('totalCapturedAmount', $order['totalCapturedAmount']);
        $payment->setAdditionalInformation('totalRefundedAmount', $order['totalRefundedAmount']);
        $payment->setAdditionalInformation('createdDate', $order['createdDate']);
        $payment->setAdditionalInformation('updatedDate', $order['updatedDate']);
    }

    private function processPay($order, $payload)
    {
        $payment = $order->getPayment();

        $payTransaction = null;
        foreach ($payload['order']['transactions'] as $transaction) {
            if (mb_strtolower($transaction["type"]) == "pay" &&
                mb_strtolower($transaction["status"]) == "success"
            ) {
                $payTransaction = $transaction;
            }
        }

        if (!$payTransaction) {
            return;
        }
        
        if ($this->managerInterface->isTransactionExists(
            $payTransaction['transactionId'],
            $payment->getId(),
            $order->getId()
        )
        ) {
            return;
        }
        
        $this->setPaymentData($payment, $payload);

        $token = $payload['order']['tokenId'];

        if ($token) {
            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
            $paymentToken->setGatewayToken($token);
            $paymentToken->setExpiresAt(
                $this->getExpirationDate(
                    $payTransaction['paymentMethod']['expiryDate']['year'],
                    $payTransaction['paymentMethod']['expiryDate']['month']
                )
            );

            $paymentToken->setTokenDetails(json_encode([
                'type' => $this->config->getCcTypesMapper()[$payTransaction['paymentMethod']['brand']],
                'maskedCC' => substr($payTransaction['paymentMethod']['maskedCardNumber'], -4, 4),
                'expirationDate' => sprintf(
                    "%s/%s",
                    $payTransaction['paymentMethod']['expiryDate']['month'],
                    $payTransaction['paymentMethod']['expiryDate']['year']
                )
            ]));

            $extensionAttributes = $payment->getExtensionAttributes();
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }

        $payment
            ->setPreparedMessage(__('Geidea `pay` callback received.'))
            ->setTransactionId($payTransaction['transactionId'])
            ->setCurrencyCode($payTransaction['currency'])
            ->setIsTransactionClosed(0)
            ->setShouldCloseParentTransaction(true)
            ->setTransactionAdditionalInfo('Response', json_encode($payload));

        $payment->registerCaptureNotification($payTransaction['amount'], true);

        $this->orderRepository->save($order);
    }

    public function execute() : ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = [
            'success' => true,
            'error_message' => '',
        ];

        try {

            $payload = json_decode($this->getRequest()->getContent(), true);

            $this->checkSignature($payload);

            $merchantReferenceId = $payload['order']['merchantReferenceId'];

            if (!$merchantReferenceId) {
                throw new LocalizedException(__('`merchantReferenceId` is null'));
            }

            $order = $this->orderFactory->create()->loadByIncrementId($merchantReferenceId);

            if (!$order->getId()) {
                throw new LocalizedException(
                    __(
                        'the "%1" order ID is incorrect. Verify the ID and try again.',
                        $merchantReferenceId
                    )
                );
            }

            if (mb_strtolower($order->getOrderCurrencyCode()) != mb_strtolower($payload['order']['currency'])) {
                throw new LocalizedException(__('incorrect currency'));
            }
            
            if (round($order->getBaseGrandTotal(), 2) > $payload['order']['amount']) {
                throw new LocalizedException(__('incorrect amount'));
            }

            if (mb_strtolower($payload['order']["status"]) != "success") {
                throw new LocalizedException(__('incorrect order state'));
            }
            
            if (mb_strtolower($payload['order']["detailedStatus"]) == "paid") {
                $this->processPay($order, $payload);
            } else {
                throw new LocalizedException(__('incorrect detailed state'));
            }

        } catch (\Exception $exception) {
            $this->logger->critical($exception);

            $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request');

            $response['success'] = false;
            $response['error_message'] = $exception->getMessage();
        }

        return $result->setData($response);
    }

    private function getExpirationDate($year, $month)
    {
        $expDate = new \DateTime(
            $year
            . '-'
            . $month
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }
}
