<?php
namespace Geidea\Payment\Controller\Adminhtml\Token;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class MassDelete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Geidea_Payment::customer_tokens';

    private $objectManager;
    private $filter;
    private $repository;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Filter $filter,
        PaymentTokenRepositoryInterface $repository
    ) {
        parent::__construct($context);

        $this->objectManager = $objectManager;
        $this->filter = $filter;
        $this->repository = $repository;
    }

    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }
        
        $collection = $this->filter->getCollection(
            $this->objectManager->create(\Magento\Vault\Model\ResourceModel\PaymentToken\Collection::class)
        );
        
        $tokenDeleted = 0;
        foreach ($collection->getItems() as $token) {
            $this->repository->delete($token);
            $tokenDeleted++;
        }

        if ($tokenDeleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $tokenDeleted)
            );
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('geidea_tokens/index/index');
    }
}
