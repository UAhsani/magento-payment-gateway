<?php

namespace Geidea\Payment\Controller\Payment;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;

abstract class AbstractAction extends AppAction implements
    HttpPostActionInterface
{
    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartRepositoryInterface
     */
    protected $GuestCartRepositoryInterface;

    /**
     * @var GenericSession
     */
    protected $genericSession;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param UserContextInterface $userContext
     * @param CartRepositoryInterface $cartRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param GenericSession $genericSession
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        GenericSession $genericSession,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->userContext = $userContext;
        $this->cartRepository = $cartRepository;
        $this->guestCartRepository = $guestCartRepository;
        $this->genericSession = $genericSession;
        $this->checkoutSession = $checkoutSession;
    }
    
    /**
     * Get quote by quoteId
     *
     * @param string $quoteId
     * @return Quote
     */
    protected function getQuote($quoteId)
    {
        if ($quoteId) {
            $quote = $this->userContext->getUserId()
                ? $this->cartRepository->get($quoteId)
                : $this->guestCartRepository->get($quoteId);

            if ((int)$quote->getCustomer()->getId() === (int)$this->userContext->getUserId()) {
                return $quote;
            }
        }

        $quoteId = $this->genericSession->getQuoteId();
        
        if (quoteId) {
            $quote = $this->cartRepository->get(quoteId);
            $this->checkoutSession->replaceQuote($quote);

            return $quote;
        }
        
        return $this->checkoutSession->getQuote();
    }

    /**
     * Check quote
     *
     * @param Quote $quote
     */
    protected function checkQuote($quote)
    {
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new LocalizedException(__('We can\'t initialize Checkout.'));
        }

        if (!$quote->getGrandTotal()) {
            throw new LocalizedException(
                __(
                    'Geidea can\'t process orders with a zero balance due. '
                    . 'To finish your purchase, please go through the standard checkout process.'
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    abstract public function execute();
}
