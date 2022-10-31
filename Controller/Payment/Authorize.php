<?php

namespace Geidea\Payment\Controller\Payment;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Helper\Data as CheckoutData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Psr\Log\LoggerInterface;

class Authorize extends AbstractAction
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    
    /**
     * @var CheckoutData
     */
    private $checkoutData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param UserContextInterface $userContext
     * @param CartRepositoryInterface $cartRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param GenericSession $genericSession
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param CartManagementInterface $cartManagement
     * @param CheckoutData $checkoutData
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        GenericSession $genericSession,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CartManagementInterface $cartManagement,
        CheckoutData $checkoutData,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        parent::__construct(
            $context,
            $userContext,
            $cartRepository,
            $guestCartRepository,
            $genericSession,
            $checkoutSession
        );
        $this->customerSession = $customerSession;
        $this->cartManagement = $cartManagement;
        $this->checkoutData = $checkoutData;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }
    
    /**
     * @param Quote $quoteId
     * @return string
     */
    private function getCheckoutMethod($quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutData->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $quote->getCheckoutMethod();
    }

    /**
     * @param Quote $quote
     */
    private function ignoreAddressValidation($quote)
    {
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }
    }

    /**
     * @return ResultInterface
     */
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

            if ($this->getCheckoutMethod($quote) == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
                $quote->setCustomerId(null)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
            }
            
            $this->ignoreAddressValidation($quote);

            $payment = $quote->getPayment();
            $payment->setMethod(\Geidea\Payment\Model\Ui\GeideaConfigProvider::CODE);
            $quote->collectTotals();
            $this->cartRepository->save($quote);

            $order = $this->cartManagement->submit($quote);

            $this->checkoutSession->start();

            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());

            $response['redirectUrl'] = $this->urlBuilder->getUrl('checkout/onepage/success/');
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
