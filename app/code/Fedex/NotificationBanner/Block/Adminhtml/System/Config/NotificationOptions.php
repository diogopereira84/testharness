<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\NotificationBanner\Block\Adminhtml\System\Config;
 
use Magento\Framework\Option\ArrayInterface;

/**
 * NotificationOptions Block Class
 */
class NotificationOptions implements ArrayInterface
{
    /**
     * NotificationOptions array
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => null,
                'label' => 'Please select banner icon...',
            ],
            [
                'value' => 'warning',
                'label' => 'Warning',
            ],
            [
                'value' => 'information',
                'label' => 'Information',
            ]
        ];
    }
}
