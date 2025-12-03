<?php
/**
 * Copyright &copy; Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\Plugin;

use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Magento\Framework\App\Http\Context;

/**
 * Plugin on \Magento\Framework\App\Http\Context
 */
class CustomerWorkSpacePlugin
{
    public function __construct(
        private UnfinishedProjectNotification $unfinishedProjectNotification
    )
    {
    }

    /**
     * \Magento\Framework\App\Http\Context::getVaryString is used to retrieve unique identifier for selected context,
     * @param object $subject
     */
    public function beforeGetVaryString(Context $subject)
    {
        if ($this->unfinishedProjectNotification->isProjectAvailable()) {
            $subject->setValue('WORKSPACE_EXIST', 1, 0);
        }
    }
}
