<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Cron;

use Exception;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use \Psr\Log\LoggerInterface;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Fedex\Shipment\ViewModel\ShipmentConfig;
use \Magento\Store\Model\StoreManagerInterface;

/**
 * Class use to send OMS new status order email
 */
class OrderCollectionCron
{
    const RECEIVER_EMAIL = "oms_email_configuration/config/email_id";

    const SENDER_EMAIL = "trans_email/ident_general/email";

    const EMAIL_TEMPLATE_ID = "oms_email_configuration/config/email_template";
    
    /**
     * Order Collection Cron constructor
     *
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     * @param CollectionFactory $orderCollectionFactory
     * @param ShipmentConfig $shipmentConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected DateTime $dateTime,
        protected LoggerInterface $logger,
        protected CollectionFactory $orderCollectionFactory,
        protected ShipmentConfig $shipmentConfig,
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * Get Current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Used to send email of OMS new status order
     *
     * @return this
     */
    public function getNewStatusOrderCollection()
    {
        try {
            $time = $this->dateTime->date("Y-m-d H:i:s", (time() - 3600));
            $orders = $this->orderCollectionFactory->create()
                            ->addFieldToSelect(['created_at', 'increment_id', 'status'])
                            ->addFieldToFilter('status', ['in' => ['new']])
                            ->addFieldToFilter('created_at', ['lteq' => $time]);
            $orders->getSelect()->where('CHAR_LENGTH(increment_id) = 16');
            $receiverEmail = $this->shipmentConfig->getConfigValue(self::RECEIVER_EMAIL, $this->getStoreId());
            $senderEmail = $this->shipmentConfig->getConfigValue(self::SENDER_EMAIL, $this->getStoreId());
            $templateId = $this->shipmentConfig->getConfigValue(self::EMAIL_TEMPLATE_ID, $this->getStoreId());

            $emailTemplateVariables = [
                'orders' => $orders
            ];
            $senderInfo = [
                'name' => 'Fedex',
                'email' => $senderEmail
            ];
            $receiverInfo = [
                'name' => 'FedEx',
                'email' => $receiverEmail
            ];
            $this->shipmentConfig
            ->sendOrderStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo, $templateId);
            $this->logger->info(__METHOD__.':'.__LINE__.':'.'OMS new order status email has been sent');
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.'Issue in send new order status email '. $e->getMessage());
        }

        return $this;
    }
}
