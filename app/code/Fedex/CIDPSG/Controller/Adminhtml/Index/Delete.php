<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\CIDPSG\Model\Customer as PsgCustomer;
use Magento\Framework\Message\ManagerInterface;

/**
 * Delete Controller class
 */
class Delete implements ActionInterface
{
    /**
     * @param PsgCustomer $customer
     * @param LoggerInterface $logger
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $requestInterface
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        protected PsgCustomer $customer,
        protected LoggerInterface $logger,
        protected RedirectFactory $resultRedirectFactory,
        protected RequestInterface $requestInterface,
        protected ManagerInterface $messageManager
    )
    {
    }

    /**
     * Delete Psg customer data using entity id
     *
     * @return mixed
     */
    public function execute()
    {
        $id = $this->requestInterface->getParam('entity_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $customerInfo = $this->customer->load($id);
                if ($customerInfo->getClientId() != 'default') {
                    $customerInfo->delete();
                    $this->messageManager->addSuccess(__('Customer has been deleted.'));
                    $this->logger->info(__METHOD__.':'.__LINE__.':'.$id.' Customer has been deleted.');
                } else {
                    $this->messageManager->addError(__('Default customer can not be deleted'));
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());

                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        $this->logger->error(__METHOD__.':'.__LINE__.': We can\'t find a item to delete.');
        $this->messageManager->addError(__('We can\'t find a item to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
