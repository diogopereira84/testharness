<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

class IconographyOptions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        return [
                [
                    'value' => '',
                    'label' => __('Please select banner icon...')
                ],
                [
                    'value' => 'warning',
                    'label' => __('Warning')
                ],
                [
                    'value' => 'information',
                    'label' => __('Information')
                ]
            ];
    }
}
