<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Fedex\Shipment\Model\ShipmentFactory;

/**
 * Class Options for Listing Column Status
 */
class StatusOption extends AbstractHelper
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ShipmentFactory $shipmentStatusFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        protected ShipmentFactory $shipmentStatusFactory
    ) {
        parent::__construct($context);
    }

    /*
     * Get shipment status based on value to show in shipment
     * admin grid
     * @return string
    */
    public function shipmentStatus($value)
    {
        $collection = $this->shipmentStatusFactory->create()->getCollection();
        $statusdata = [];
        foreach ($collection as $shipmentvalue) {
            $statusdata[strtolower(trim($shipmentvalue->getValue()))] = $shipmentvalue->getLabel();
        }
        if (isset($statusdata[$value])) {
            return $statusdata[$value];
        } else {
            return;
        }
    }

    /**
     * @param $order
     * @return bool
     */
    public function hasShipmentCreated($order): bool
    {
        $hasShipments = false;
        foreach ($order->getShipmentsCollection() as $shipment) {
            if (!$shipment->getMiraklShippingReference()) {
                $hasShipments = true;
            }
        }

        return $hasShipments;
    }
}
