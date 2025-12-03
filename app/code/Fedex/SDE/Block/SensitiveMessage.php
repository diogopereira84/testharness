<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Block;

use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;

/**
 * SensitiveMessage Block class
 */
class SensitiveMessage extends Template
{
    /**
     * SensitiveMessage constructor
     *
     * @param Context $context
     * @param SdeHelper $sdeHelper
     */
    public function __construct(
        Context $context,
        protected SdeHelper $sdeHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Check if sensitive message block can be shown
     *
     * @return bool
     */
    public function canShowSensitiveMessageBlock()
    {
        return $this->sdeHelper->isFacingMsgEnable() && $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Get Sde secure Image path
     *
     * @return string
     */
    public function getSdeSecureImagePath()
    {
        return $this->sdeHelper->getSdeSecureImagePath();
    }

    /**
     * Get Sde message block title
     *
     * @return string
     */
    public function getSdeSecureTitle()
    {
        return $this->sdeHelper->getSdeSecureTitle();
    }

    /**
     * Get Sde message block content
     *
     * @return string
     */
    public function getSdeSecureContent()
    {
        return $this->sdeHelper->getSdeSecureContent();
    }
}
