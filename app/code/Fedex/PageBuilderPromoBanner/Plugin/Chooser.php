<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Plugin;

class Chooser
{
    /**
     * Add remove element in widget static block
     *
     * @param \Magento\Cms\Block\Adminhtml\Block\Widget\Chooser $subject
     * @param object $result
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function afterPrepareElementHtml(\Magento\Cms\Block\Adminhtml\Block\Widget\Chooser $subject, $result)
    {
        $element = $result->getAfterElementHtml();

        $element .= '<span class="widget-remove-static-block action-default scalable btn-chooser" onClick="
        jQuery(this).parent().find(\''.'.widget-option-label'.'\').text(\'Not Selected\');
        jQuery(this).parent().find(\''.'.widget-option-label'.'\').val(\'\');
        jQuery(jQuery(\''.'button.scalable.btn-chooser'.'\')[1]).prev().val(\'\');
        ">Remove</span>';
        
        $result->setData('after_element_html', $element);

        return $result;
    }
}
