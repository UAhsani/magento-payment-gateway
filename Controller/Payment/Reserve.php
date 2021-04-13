<?php

namespace Geidea\Payment\Controller\Payment;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Psr\Log\LoggerInterface;

class Reserve extends AbstractAction
{

    private $userContext;
    private $cartRepository;
    private $guestCartRepository;
    private $genericSession;
    private $checkoutSession;
    private $logger;

    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        GenericSession $genericSession,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->userContext = $userContext;
        $this->cartRepository = $cartRepository;
        $this->guestCartRepository = $guestCartRepository;
        $this->genericSession = $genericSession;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    private function getQuote($quoteId) {
        if ($quoteId) {
            $quote = $this->userContext->getUserId()
                ? $this->cartRepository->get($quoteId)
                : $this->guestCartRepository->get($quoteId);

            if ((int)$quote->getCustomer()->getId() === (int)$this->userContext->getUserId())
                return $quote;
        }

        $quoteId = $this->genericSession->getQuoteId();
        
        if (quoteId) {
            $quote = $this->cartRepository->get(quoteId);
            $this->checkoutSession->replaceQuote($quote);

            return $quote;
        }
        
        return $this->checkoutSession->getQuote();
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

            $quote->collectTotals();

            if (!$quote->getGrandTotal()) {
                throw new LocalizedException(
                    __(
                        'Geidea can\'t process orders with a zero balance due. '
                        . 'To finish your purchase, please go through the standard checkout process.'
                    )
                );
            }

            $quote->reserveOrderId();
            $this->cartRepository->save($quote);

            $response['amount'] = round($quote->getBaseGrandTotal(), 2);
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
