<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Controller\Adminhtml\shipment;

use Fedex\Shipment\Model\Shipment;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Psr\Log\LoggerInterface;

class Save implements ActionInterface
{
    /** @var string */
    private const ID_PARAM = 'id';

    /** @var string */
    private const BACK_PARAM = 'back';

    /**
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param Shipment $shipment
     * @param Session $session
     */
    public function __construct(
        private RequestInterface $request,
        private RedirectFactory $resultRedirectFactory,
        private ManagerInterface $messageManager,
        private LoggerInterface $logger,
        private Shipment $shipment,
        private Session $session
    ) {
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->request->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->updateShipmentData($data);
            $this->messageManager->addSuccess(__('The Shipment has been saved.'));
            $this->session->setFormData(false);
            if ($this->request->getParam(self::BACK_PARAM)) {
                return $resultRedirect->setPath(
                    '*/*/edit',
                    [self::ID_PARAM => $this->shipment->getId(), '_current' => true]
                );
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while saving the Shipment.'));
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
        }

        $this->session->setFormData($data);
        return $resultRedirect->setPath('*/*/edit', [self::ID_PARAM => $this->request->getParam(self::ID_PARAM)]);
    }

    /**
     * @param $data
     * @return void
     */
    private function updateShipmentData($data): void
    {
        $id = $this->request->getParam(self::ID_PARAM);
        if ($id) {
            $this->shipment->load($id);
            $this->shipment->setCreatedAt(date('Y-m-d H:i:s'));
        }
        $this->shipment->setData($data);
        $this->shipment->save();
    }
}
