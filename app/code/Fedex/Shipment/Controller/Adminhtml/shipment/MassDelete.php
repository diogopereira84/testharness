<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Controller\Adminhtml\shipment;

use Fedex\Shipment\Model\Shipment;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use \Psr\Log\LoggerInterface;

class MassDelete implements ActionInterface
{
    /** @var string */
    private const SHIPMENT = 'shipment';

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param Shipment $shipment
     */
    public function __construct(
        private RedirectFactory $resultRedirectFactory,
        private RequestInterface $request,
        private ManagerInterface $messageManager,
        private LoggerInterface $logger,
        private Shipment $shipment
    ) {
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $itemIds = $this->request->getParam(self::SHIPMENT);
        if (!is_array($itemIds) || empty($itemIds)) {
            $this->messageManager->addError(__('Please select item(s).'));
            $this->logger->info(__METHOD__.':'.__LINE__.':'.'No items selected');
        } else {
            try {
                foreach ($itemIds as $itemId) {
                    $this->shipment->load($itemId)->delete();
                }
                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been deleted.', count($itemIds))
                );
                $this->logger->info(__METHOD__.':'.__LINE__.':'.
                'A total of '.count($itemIds).' record(s) have been deleted.');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('shipment/*/index');
    }
}
