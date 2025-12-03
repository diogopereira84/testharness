<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Tax\Block\Adminhtml\System\Config;
 
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
 
/**
 * Editor Block Class
 *
 */
class Editor extends Field
{
    /**
     * @var Context $context
     */
    protected $context;
 
    /**
     * @param Context       $context
     * @param WysiwygConfig $wysiwygConfig
     * @param array         $data
     */
    public function __construct(
        Context $context,
        protected WysiwygConfig $wysiwygConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Element Html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $element->setWysiwyg(true);

        $element->setConfig($this->wysiwygConfig->getConfig([
            'add_variables' => false,
            'add_widgets'   => false,
            'height'        => '200px',
            'isModalEditor'   => true
        ]));

        return parent::_getElementHtml($element);
    }
}
