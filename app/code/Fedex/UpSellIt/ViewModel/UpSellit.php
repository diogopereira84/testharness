<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UpSellIt\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;

class UpSellit implements ArgumentInterface
{
    /**
     * UpSellit Constructor
     *
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        protected SsoConfiguration $ssoConfiguration
    )
    {
    }

    /**
     * To identify the retail store
     *
     * @return boolean true|false
     */
    public function getIsRetail()
    {
        return $this->ssoConfiguration->isRetail();
    }
}
