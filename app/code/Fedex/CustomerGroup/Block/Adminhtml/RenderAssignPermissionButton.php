<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerGroup\Block\Adminhtml;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Backend\Block\Widget\Context;

/**
 * Block that renders Assign Permission Button in customer admin
 */
class RenderAssignPermissionButton extends \Magento\Backend\Block\Widget\Container
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
        if ($this->toggleConfig->getToggleConfigValue('sgc_b_2256325')) {
            $addButtonProps = [
                'id' => 'assign-permission-button',
                'label' => __('Assign Permissions'),
                'class' => 'primary',
                'button_class' => '',
            ];
            $this->buttonList->add('add_new', $addButtonProps);
        }

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
            $this->setTemplate('Fedex_CustomerGroup::assign_permissions_modal.phtml');
        }
        parent::_construct();
    }
}
