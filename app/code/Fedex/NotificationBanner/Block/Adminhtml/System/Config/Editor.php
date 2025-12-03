<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\NotificationBanner\Block\Adminhtml\System\Config;
 
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\Form\Element\AbstractElement;
 
/**
 * Editor Block Class
 *
 */
class Editor extends \Magento\Config\Block\System\Config\Form\Field
{
    public $_wysiwygConfig;
    /**
     * @var Context $context
     */
    protected $context;

    /**
     * @var WysiwygConfig $wysiwygConfig
     */
    protected $wysiwygConfig;
 
    /**
     * @param Context       $context
     * @param WysiwygConfig $wysiwygConfig
     * @param array         $data
     */
    public function __construct(
        Context $context,
        WysiwygConfig $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
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
        // set wysiwyg for element
        $element->setWysiwyg(true);
        // set configuration values
        $element->setConfig($this->_wysiwygConfig->getConfig([
                'add_variables' => false,
                'add_widgets'   => false,
                'add_images'    => false,
                'height'        => 350
            ]));
        return parent::_getElementHtml($element);
    }
}
