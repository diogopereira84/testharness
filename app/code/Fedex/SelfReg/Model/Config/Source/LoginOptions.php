<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Model\Config\Source;

class LoginOptions implements \Magento\Framework\Option\ArrayInterface

{
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'registered_user', 'label' => __('Auto Approve')],
            ['value' => 'domain_registration', 'label' => __('Domain Approve')],
            ['value' => 'admin_approval', 'label' => __('Admin Approve')],
        ];

    }
}
