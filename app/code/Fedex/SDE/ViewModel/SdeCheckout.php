<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\SDE\Helper\SdeHelper;

/**
 * SdeConfiguration ViewModel class
 */
class SdeCheckout implements ArgumentInterface
{
   
    /**
     * SsoConfiguration constructor.
     *
     * @param SdeHelper $SdeHelper
     * @return void
     */
    public function __construct(
        protected SdeHelper $sdeHelper
    )
    {
    }

    /**
     * Checks if the store is a SDE store
     *
     * @return bool
     */
    public function getIsSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }
}
