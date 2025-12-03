<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Block\Adminhtml\Group;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

/**
 * Customer group edit block
 */
class Edit extends \Magento\Customer\Block\Adminhtml\Group\Edit
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupManagementInterface $groupManagement
     * @param array $data
     */
    public function __construct(
        protected Context $context,
        protected Registry $registry,
        GroupRepositoryInterface $groupRepository,
        GroupManagementInterface $groupManagement,
        array $data = []
    ) {
        parent::__construct($context, $registry, $groupRepository, $groupManagement, $data);
    }

    /**
     * Update Save and Delete buttons. Remove Delete button if group can't be deleted.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_group';
        $this->_blockGroup = 'Magento_Customer';

        $this->buttonList->update('save', 'label', __('Save Customer Group'));
        $this->buttonList->update('delete', 'label', __('Delete Customer Group'));

        $groupId = $this->registry->registry(RegistryConstants::CURRENT_GROUP_ID);
        if (!$groupId || $this->groupManagement->isReadonly($groupId)) {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve the header text, either editing an existing group or creating a new one.
     *
     * @return string
     */
    public function getHeaderText()
    {
        $groupId = $this->registry->registry(RegistryConstants::CURRENT_GROUP_ID);
        if ($groupId === null) {
            return __('New Customer Group');
        } else {
            $group = $this->groupRepository->getById($groupId);
            return __('Edit Customer Group "%1"', $this->escapeHtml($group->getCode()));
        }
    }

    /**
     * Retrieve CSS classes added to the header.
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-customer-groups';
    }
}
