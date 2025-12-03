<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Controller\Adminhtml\Order;

use \Psr\Log\LoggerInterface;

class Email extends \Magento\Backend\App\Action
{
    /**
     * @var \Fedex\Shipment\Helper\ShipmentEmail
     */
    protected $shipmentEmail;
    private \Fedex\Shipment\Helper\ShipmentEmail $_shipmentEmail;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Fedex\Shipment\Helper\ShipmentEmail $shipmentEmail
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Fedex\Shipment\Helper\ShipmentEmail $shipmentEmail,
        protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        protected LoggerInterface $logger
    ) {
        $this->_shipmentEmail = $shipmentEmail;
        parent::__construct($context);
    }

    /**
     * Send confirmation email of order to customer
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('order_id');
        if ($id) {
            try {
                $order = $this->orderRepository->get($id);
                if ($order) {
                    $shipmentCollection = $order->getShipmentsCollection();
                    $shipmentId = 0;
                    foreach ($shipmentCollection as $shipment) {
                        $shipmentId = $shipment->getId();
                    }
                    $result = $this->_shipmentEmail->sendEmail("confirmed", $order->getEntityId(), $shipmentId);

                    if ($result=="sent") {
                        $this->messageManager->addSuccessMessage(__('You sent the order email.'));
                        $this->logger->info(__METHOD__.':'.__LINE__.':'.$shipmentId.' sent the order email');
                    } else {
                        $this->messageManager->addErrorMessage(__('We can\'t send the email order right now.'));
                        $this->logger->error(__METHOD__.':'.__LINE__.':'.$shipmentId.' can\'t send the email order');
                    }
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->critical(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t send the email order right now.'));
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'sales/order/view',
                [
                    'order_id' => $id
                ]
            );
        }
        return $this->resultRedirectFactory->create()->setPath('sales/*/');
    }
}
