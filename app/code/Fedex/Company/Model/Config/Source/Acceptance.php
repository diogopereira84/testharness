<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

class Acceptance implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        return [
                [
                    'value' => '',
                    'label' => __('Select Rule Type')
                ],
                [
                    'value' => 'extrinsic',
                    'label' => __('Extrinsic')
                ],
                [
                    'value' => 'contact',
                    'label' => __('Contact')
                ],
                [
                    'value' => 'both',
                    'label' => __('Extrinsic & Contact')
                ]
            ];
    }
}
