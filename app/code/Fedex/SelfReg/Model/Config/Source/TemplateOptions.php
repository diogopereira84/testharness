<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Model\Config\Source;

use Magento\Email\Model\Template;

class TemplateOptions implements \Magento\Framework\Option\ArrayInterface
{
    private array $options = [];

    public function __construct(
        private Template $emailTemplateConfig
    )
    {
    }
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $templateData = $this->emailTemplateConfig->getCollection();
            foreach ($templateData as $_templateData) {
                $this->options[] = [
                    'value' => $_templateData->getId(), 'label' => $_templateData->getTemplateCode()
                ];
            }
            array_unshift($this->options, ['value' => '0', 'label' => __('Select email template')]);
        }
        return $this->options;
    }
}
