<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
namespace Fedex\Cart\Plugin;

use Fedex\Cart\Helper\Data;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;

class CartPlugin
{
    public function __construct(
        protected Data $dataHelper,
        private HandleMktCheckout $handleMktCheckout,
        private readonly CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
    )
    {
    }

    /**
     * Set cart max limit value in checkout config
     *
     * @return array
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        $maxLimitArray = $this->dataHelper->getMaxCartLimitValue();
        $minCartItemThreshold = $maxLimitArray['minCartItemThreshold'] ?? '';
        $maxCartItemLimit = $maxLimitArray['maxCartItemLimit'] ?? '';
        $result['cartThresholdLimit'] = (int)$minCartItemThreshold;
        $result['maxCartLimit'] = (int)$maxCartItemLimit;
        $result['checkCartHaveUnavailbleProduct'] = $this->checkProductAvailabilityDataModel->checkCartHaveUnavailbleProduct();

        return $result;
    }
}
