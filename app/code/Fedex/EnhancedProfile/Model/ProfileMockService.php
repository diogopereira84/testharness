<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Model;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;

class ProfileMockService
{
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param EnhancedProfile $enhancedProfile
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected EnhancedProfile $enhancedProfile
    )
    {
    }

    /**
     * Get profile mock service data
     *
     * @return json
     */
    public function getProfileMockService()
    {
        try {
            $customerProfile = $this->enhancedProfile
            ->getConfigValue('enhancedprofile/enhancedprofile_group/mock_data_api');
            $customerProfile = str_replace(["\r\n  ", ' '], ['', ''], $customerProfile);
            return $customerProfile;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Profile API is not working with error : '
            . $e->getMessage());
        }
    }
}
