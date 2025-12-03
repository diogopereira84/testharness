<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Block\Adminhtml;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Backend\Block\Widget\Context;

/**
 * @api
 * @since 100.0.2
 */
class RenderButton extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Prepare button and grid
     *
     * @return \Fedex\CustomerGroup\Block\Adminhtml\RenderButton
     */
    protected function _prepareLayout()
    {
        $addButtonProps = [
            'id' => 'group-model-popup-button',
            'label' => __('Assign Customer Group'),
            'class' => 'primary',
            'button_class' => '',
        ];
        $this->buttonList->add('add_new', $addButtonProps);
        return parent::_prepareLayout();
    }

    /**
     * Define block template
     *
     * @return void
     */
    protected function _construct()
    {
        if (!$this->hasTemplate()) {
            $this->setTemplate('Fedex_CustomerGroup::customergroup_modalpopup.phtml');
        }
        parent::_construct();
    }
}
