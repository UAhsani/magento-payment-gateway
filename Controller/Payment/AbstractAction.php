<?php

namespace Geidea\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

abstract class AbstractAction extends AppAction implements
    HttpPostActionInterface
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    abstract public function execute();
}
