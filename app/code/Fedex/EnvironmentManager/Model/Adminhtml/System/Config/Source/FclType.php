<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Model\Adminhtml\System\Config\Source;

/**
 * Use to add system configuration FclType class
 */
class FclType implements \Magento\Framework\Data\OptionSourceInterface
{
  /**
   * Return option value
   *
   * @return array
   */
    public function toOptionArray()
    {
        return [
          ['value' => 'module', 'label' => __('Module')],
          ['value' => 'feature', 'label' => __('Feature')]
        ];
    }
}
