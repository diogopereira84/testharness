<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\ViewModel;

use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * B-1316868 : Fix the estimated total and estimated Shipping total on Order history and print order screens
 */
class OrderView implements ArgumentInterface
{
    /**
     * OrderView constructor
     *
     * @param SdeHelper $sdeHelper
     * @return void
     */
    public function __construct(
        protected SdeHelper $sdeHelper
    )
    {
    }

    /**
     * Check current store is SDE or not
     *
     * @return boolean
     */
    public function getIsSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /** Get image path for masking SDE product image
     *
     * @return string|boolean
     */
    public function getSdeMaskSecureImagePath()
    {
        return $this->sdeHelper->getSdeMaskSecureImagePath();
    }

    /**
     * Get Shipping Account Number from order
     *
     * @return string
     */
    public function getShippingAccountNumberFromOrder($order)
    {
        $shipment = $order->getShipmentsCollection();
        if ($shipment && $shipmentItem = $shipment->getFirstItem()) {
            return $shipmentItem->getShippingAccountNumber();
        }

        return '';
    }
    
    /**
     * Get Sorted Discounts
     *
     * @return array
     */
    public function getSortedDiscounts($data)
    {
        usort($data, fn($a, $b) => $b['price'] <=> $a['price']);
        
        return $data;
    }
}
