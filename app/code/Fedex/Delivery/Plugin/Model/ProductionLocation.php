<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Plugin\Model;

use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Api\Data\ShippingMethodInterfaceFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data;

class ProductionLocation
{
    /**
     * ProductionLocation constructor.
     * @param ShippingMethodInterfaceFactory $extensionFactory
     * @param ToggleConfig                   $toggleConfig
     * @param Data                           $helper
     */
    public function __construct(
        private ShippingMethodInterfaceFactory $extensionFactory,
        private ToggleConfig                   $toggleConfig,
        private Data                           $helper
    ) {
    }

    /**
     * Set production location with extension attribute
     *
     * @param ShippingMethodConverter $subject
     * @param ShippingMethodConverter $result
     * @param \Magento\Quote\Model\Quote\Address\Rate $rateModel
     * @return mixed
     */
    public function afterModelToDataObject($subject, $result, $rateModel)
    {
        if (
            $this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') &&
            $this->helper->isCommercialCustomer()
        ) { 
            $extensionAttribute = $result->getExtensionAttributes() ?
            $result->getExtensionAttributes() : $this->extensionFactory->create();
            $extensionAttribute->setProductionLocation(
                $rateModel->getProductionLocation()
            );
            $result->setExtensionAttributes($extensionAttribute);
        }

        return $result;
    }
}
