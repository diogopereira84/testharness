<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOCMConfigurator\Model\Adminhtml\System\Config\Source;

/**
 * Use to add system configuration IntegrationType class
 */
class IntegrationType implements \Magento\Framework\Data\OptionSourceInterface
{
  /**
   * Return option value
   *
   * @return array
   */
    public function toOptionArray()
    {
        return [
          ['value' => 'IFRAME', 'label' => __('IFRAME')],
          ['value' => 'URL_REDIRECT', 'label' => __('URL_REDIRECT')]
        ];
    }
}
