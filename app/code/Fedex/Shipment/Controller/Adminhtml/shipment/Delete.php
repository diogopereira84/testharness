<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Controller\Adminhtml\shipment;

use Fedex\Shipment\Model\Shipment;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use \Psr\Log\LoggerInterface;

class Delete implements ActionInterface
{
    /** @var string  */
    private const ID_PARAM = 'id';

    /**
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param Shipment $shipment
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private RedirectFactory $resultRedirectFactory,
        private ManagerInterface $messageManager,
        private Shipment $shipment,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->request->getParam(self::ID_PARAM);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                // init model and delete
                $this->shipment->load($id)->delete();
                // display success message
                $this->messageManager->addSuccess(__('The item has been deleted.'));
                $this->logger->info(__METHOD__.':'.__LINE__.':'.$id.' The item has been deleted.');
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', [self::ID_PARAM => $id]);
            }
        }
        // display error message
        $this->logger->error(__METHOD__.':'.__LINE__.': We can\'t find a item to delete.');
        $this->messageManager->addError(__('We can\'t find a item to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
