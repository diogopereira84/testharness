<?php
/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Customer\Block\Adminhtml\Edit\Tab;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Phrase;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Customer\Model\CustomerIdProvider;

/**
 * Class for User Permissions Tab
 */
class UserPermissionsTab extends Template implements TabInterface
{
    /**
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param CustomerIdProvider $customerIdProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        protected CustomerIdProvider $customerIdProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return Tab Label
     *
     * @return Phrase
     */
    public function getTabLabel()
    {
        return __('User Permissions');
    }

    /**
     * Return Tab Title
     *
     * @return Phrase
     */
    public function getTabTitle()
    {
        return __('User Permissions');
    }

    /**
     * Determine if tab is visible to user
     *
     * @return bool
     */
    public function canShowTab()
    {
        $customerId = $this->customerIdProvider->getCustomerId();
        if (!$this->toggleConfig->getToggleConfigValue('sgc_b_2256325') || !$customerId) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determine if tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }
}
