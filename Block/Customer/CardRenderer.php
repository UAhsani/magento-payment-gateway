<?php
namespace Geidea\Payment\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

use Geidea\Payment\Model\Ui\GeideaConfigProvider;

class CardRenderer extends AbstractCardRenderer
{
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === GeideaConfigProvider::CODE;
    }

    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    public function getExpDate()
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    public function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }
}