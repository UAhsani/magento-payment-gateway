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
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
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
    private $logger;
    
    public function __construct(
        Context $context,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        OrderFactory $orderFactory,
        OrderSender $orderSender,
        ManagerInterface $managerInterface,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->managerInterface = $managerInterface;
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

        if ($result_signature != $payload['signature'])
            throw new \Exception('Invalid signature');
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

    private function processAuthorization($order, $payload)
    {
        $payment = $order->getPayment();

        foreach ($payload['order']['transactions'] as $transaction) {
            switch ($transaction['type']) {
                case "Authorization":

                    if ($this->managerInterface->isTransactionExists($transaction['transactionId'], $payment->getId(), $order->getId()))
                        return;
                    
                    $this->setPaymentData($payment, $payload);
                    
                    $payment
                        ->setPreparedMessage("Authorization")
                        ->setTransactionId($transaction['transactionId'])
                        ->setCurrencyCode($transaction['currency'])
                        ->setIsTransactionClosed(0)
                        ->registerAuthorizationNotification($transaction['amount']);
                    break;
                default:
                    break;
            }
        }

        $this->orderRepository->save($order);
    }

    private function processCapture($order, $payload)
    {
        $payment = $order->getPayment();

        foreach ($payload['order']['transactions'] as $transaction) {
            switch ($transaction['type']) {

                case "Capture":

                    if ($this->managerInterface->isTransactionExists($transaction['transactionId'], $payment->getId(), $order->getId()))
                        return;

                    $this->setPaymentData($payment, $payload);
                    
                    $payment
                        ->setPreparedMessage("Capture")
                        ->setTransactionId($transaction['transactionId'])
                        ->setCurrencyCode($transaction['currency'])
                        ->setIsTransactionClosed(0)
                        ->setParentTransactionId($payment->getAuthorizationTransaction()->getId())
                        ->setShouldCloseParentTransaction(true)
                        ->registerCaptureNotification($transaction['amount'], true);
                    break;
                default:
                    break;
            }
        }

        $this->orderRepository->save($order);

        $invoice = $payment->getCreatedInvoice();

        if ($invoice && !$order->getEmailSent()) {
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(__('You notified customer about invoice #%1.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true);
                
            $this->orderRepository->save($order);
        }
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

            if (!$merchantReferenceId)
                throw new \Exception('`merchantReferenceId` is null');

            $order = $this->orderFactory->create()->loadByIncrementId($merchantReferenceId);

            if (!$order->getId())
                throw new \Exception(sprintf('the "%s" order ID is incorrect. Verify the ID and try again.', $merchantReferenceId));

            if (round($order->getBaseGrandTotal(), 2) > $payload['order']['amount'])
                throw new \Exception('incorrect amount');

            if (mb_strtolower($payload['order']["status"]) != "success")
                throw new \Exception('incorrect order state');
            
            if (mb_strtolower($payload['order']["detailedStatus"]) == "authorized")
                $this->processAuthorization($order, $payload);
            elseif (mb_strtolower($payload['order']["detailedStatus"]) == "captured")
                $this->processCapture($order, $payload);
            else
                throw new \Exception('incorrect detailed state');

        } catch (\Exception $exception) {
            $this->logger->critical($exception);

            $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request');

            $response['success'] = false;
            $response['error_message'] = $exception->getMessage();
        }

        return $result->setData($response);
    }
}
