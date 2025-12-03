<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnhancedProfile\Block\Account\CompanySettings;

use Magento\Framework\View\Element\Template\Context;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile as EnhancedProfileViewModel;
use Fedex\Company\Model\Source\ShippingOptions;

/**
 * DeliveryOptions Block class
 */
class DeliveryOptions extends \Magento\Framework\View\Element\Template
{

    /**
     * Enhanced Profile Preferred Delivery
     *
     * @param Context $context
     * @param EnhancedProfileViewModel $enhancedProfileViewModel
     * @param ShippingOptions $shippingOptions
     */
    public function __construct(
        Context $context,
        protected EnhancedProfileViewModel $enhancedProfileViewModel,
        protected ShippingOptions $shippingOptions
    ) {
        parent::__construct($context);
    }

    /**
     * Get InfoIconUrl
     *
     * @return string
     */
    public function getInfoIconUrl()
    {
        return $this->enhancedProfileViewModel->getMediaUrl()."wysiwyg/information.png";
    }

    /**
     * Get DeliveryOptions
     *
     * @return array
     */
    public function getDeliveryOptions()
    {
        return $this->shippingOptions->toOptionArray();
    }
}

