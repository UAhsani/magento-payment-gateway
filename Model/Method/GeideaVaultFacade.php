<?php

namespace Geidea\Payment\Model\Method;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Serialize\Serializer\Json;

class GeideaVaultFacade extends \Magento\Vault\Model\Method\Vault
{
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }
}
