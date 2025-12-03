<?php

/**
 * Copyright © By infogain All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Plugin\Customer;

use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;

/**
 * Class CustomerData
 *
 * This class will be responsible for changing cookie expire time for SDE
 */
class CustomerData
{
    /**
     * CustomerData Construct
     *
     * @param SdeHelper $sdeHelper
     * @return void
     */
    public function __construct(
        private SdeHelper $sdeHelper,
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * Since cookie time is zero for SDE store,
     * expire time is changed to SDE idle timeout
     *
     * @param $subject
     * @param array $result
     * @return array
     */
    public function afterGetCookieLifeTime($subject, $result)
    {
        if ($this->sdeHelper->getIsSdeStore()) {
            return $this->scopeConfig->getValue(
                SsoConfiguration::XML_PATH_FEDEX_SSO_SESSION_IDLE_TIMEOUT,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $result;
    }
}
