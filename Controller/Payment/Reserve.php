<?php

namespace Geidea\Payment\Controller\Payment;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Psr\Log\LoggerInterface;

class Reserve extends AbstractAction
{
    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        GenericSession $genericSession,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $userContext,
            $cartRepository,
            $guestCartRepository,
            $genericSession,
            $checkoutSession
        );
        $this->logger = $logger;
    }
    
    public function execute() : ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = [
            'success' => true,
            'error_message' => '',
        ];

        try {

            $quote = $this->getQuote($this->getRequest()->getParam('quote_id'));

            $this->checkQuote($quote);
            
            if ($quote->getIsMultiShipping()) {
                $quote->setIsMultiShipping(0);
                $quote->removeAllAddresses();
            }

            $quote->collectTotals();

            $quote->reserveOrderId();
            $this->cartRepository->save($quote);

            $response['amount'] = number_format(round($quote->getBaseGrandTotal(), 2), 2);
            $response['currency'] = $quote->getBaseCurrencyCode();
            $response['orderId'] = $quote->getReservedOrderId();

        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);

            $response['success'] = false;
            $response['error_message'] = $exception->getMessage();
        } catch (\Exception $exception) {
            $this->logger->critical($exception);

            $response['success'] = false;
            $response['error_message'] = __('Sorry, but something went wrong');
        }

        if (!$response['success']) {
            $this->messageManager->addErrorMessage($response['error_message']);
        }

        return $result->setData($response);
    }
}
