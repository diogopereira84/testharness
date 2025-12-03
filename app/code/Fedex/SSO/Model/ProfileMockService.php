<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Model;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;

/**
 * ProfileMockService class
 */
class ProfileMockService
{
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected SsoConfiguration $ssoConfiguration
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
            $customerProfile = $this->ssoConfiguration->getGeneralConfig('profile_mockup_json');
            $customerProfile = str_replace(["\r\n  ", ' '], ['', ''], $customerProfile);
            return $customerProfile;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':Profile API is not working with error : '.$e->getMessage());
        }
    }
}
