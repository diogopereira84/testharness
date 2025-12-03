<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Model\Config\Source\Email;

use Magento\Config\Model\Config\Source\Email\Template;

/**
 * Source for template
 *
 * @api
 * @since 100.0.2
 */
class OrderSuccessEmailTemplate implements \Magento\Framework\Option\ArrayInterface
{
    public function __construct(
        private Template $emailTemplateProvider
    )
    {
    }

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $emailTemplate = $this->emailTemplateProvider->toOptionArray();
        array_unshift($emailTemplate, ['value' => '0', 'label' =>  __('Default')]);
        return $emailTemplate;
    }
}
