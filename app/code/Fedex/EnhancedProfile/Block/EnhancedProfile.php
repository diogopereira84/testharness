<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Block;

use Magento\Framework\View\Element\Template\Context;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile as EnhancedProfileViewModel;

/**
 * EnhanceProfile Block class
 */
class EnhancedProfile extends \Magento\Framework\View\Element\Template
{

    /**
     * Enhanced Profile Preferred Delivery
     *
     * @param Context $context
     * @param EnhancedProfileViewModel $enhancedProfileViewModel
     */
    public function __construct(
        Context $context,
        protected EnhancedProfileViewModel $enhancedProfileViewModel
    ) {
        parent::__construct($context);
    }

    /**
     * Set Profile session
     *
     * @return void
     */
    public function setProfileSession()
    {
        $this->enhancedProfileViewModel->setProfileSession();
    }

    /**
     * Get Loggedin profile Info
     *
     * @return array
     */
    public function getLoggedInProfileInfo()
    {
        return $this->enhancedProfileViewModel->getLoggedInProfileInfo();
    }

    /**
     * Get Preferred Delivery
     *
     * @return boolean|string false
     * @param string $locationId
     * @param string $hoursOfOperation
     */
    public function getPreferredDelivery($locationId, $hoursOfOperation = false)
    {
        return $this->enhancedProfileViewModel->getPreferredDelivery($locationId, $hoursOfOperation);
    }

    /**
     * Get opening hours
     *
     * @param object $workingHours
     * @return array
     */
    public function getOpeningHours($workingHours)
    {
        return $this->enhancedProfileViewModel->getOpeningHours($workingHours);
    }
}
