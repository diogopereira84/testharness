<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Block\Adminhtml\Group\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('group_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('GROUP INFORMATION'));
    }
    /**
     * Tab Creation
     *
     * @return string
     */
    public function _beforeToHtml()
    {
        //Tab for Group Information
        $this->addTab(
            'customergroup_general',
            [
                'label' => __('Group Information'),
                'title' => __('Group Information'),
                'active' => true,
            ]
        );
        //Tab for Folder Level Permission
        $this->addTab(
            'customergroup_catalog_permission',
            [
                'label' => __('Folder Level Permissions'),
                'title' => __('Folder Level Permissions'),
                'active' => true,
            ]
        );

        return parent::_beforeToHtml();
    }
}
