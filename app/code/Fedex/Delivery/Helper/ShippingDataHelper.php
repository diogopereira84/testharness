<?php
/**
 * Copyright © Fedex Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Delivery\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * ShippingDataHelper Class for 1P Configurable shipping methods
 */
class ShippingDataHelper
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * Get configurable retail 1p shipping methods
     *
     * @return array
     */
    public function getRetailOnePShippingMethods()
    {   
        $fieldPath = 'shipping/one_p_shipping_methods/allowed_delivery_options';
        $deliveryOptions = $this->scopeConfig->getValue(
            $fieldPath,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
        $arrDeliveryOptions = explode(",", $deliveryOptions);
        $allowedDeliveryOptions = [];
        foreach($arrDeliveryOptions as $deliveryName) {
            $allowedDeliveryOptions[$deliveryName] = 1;
        }

        return ['allowStore' => 1, 'allowedDeliveryOptions' => $allowedDeliveryOptions];
    }
}
