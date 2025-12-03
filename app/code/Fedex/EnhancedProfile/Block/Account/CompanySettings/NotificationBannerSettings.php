<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnhancedProfile\Block\Account\CompanySettings;

use Magento\Framework\View\Element\Template\Context;
use Fedex\Company\Model\Config\Source\IconographyOptions;
use Magento\Framework\View\Element\Template;

/**
 * NotificationBannerSettings Block class
 */
class NotificationBannerSettings extends Template
{

    /**
     * @param Context $context
     * @param IconographyOptions $iconographyOptions
     */
    public function __construct(
        Context $context,
        protected IconographyOptions $iconographyOptions
    ) {
        parent::__construct($context);
    }

    /**
     * Get iconographyOptions
     *
     * @return array
     */
    public function getIconographyOptions()
    {
        return $this->iconographyOptions->toOptionArray();
    }
    
}

