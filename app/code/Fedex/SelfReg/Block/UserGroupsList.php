<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Block;

use Magento\Framework\View\Element\Template\Context;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Fedex\SelfReg\Model\CustomerGroupPermissionManager;

class UserGroupsList extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        private Context $context,
        private CustomerGroupPermissionManager $customerGroupPermissionManager,
        public MvpHelper $viewModel,
        array $data = []
    ) {
        $this->viewModel = $viewModel;
        parent::__construct($context, $data);
    }

    /**
     * Expose the ViewModel method
     *
     * @return bool
     */
    public function getAddEditFolderAccess(): bool
    {
        return $this->viewModel->getAddEditFolderAccess();
    }

    /**
     * Get customer groups of type folder_permissions.
     *
     * @return array
     */
    public function getCustomerGroupsList(): array
    {
        return $this->customerGroupPermissionManager->getCustomerGroupsList();
    }

    /**
     * Get customer group codes by category.
     *
     * @param int $categoryId The ID of the category to filter permissions by.
     * @return array The customer groups list with permissions for the specified category.
     */
    public function getUserGroups(int $categoryId): array
    {
        return $this->customerGroupPermissionManager->getUserGroupsByCategory($categoryId);
    }

    /**
     * Get user groups of current user
     *
     * @return array
     */
    public function getCurrentUserGroupsList(): array
    {
        return $this->customerGroupPermissionManager->getCurrentUserGroupsList();
    }

    /**
     * Check if folder is restricted when opening modal
     *
     * @param int $categoryId
     *
     * @return bool
     */
    public function doesDenyAllPermissionExist(int $categoryId): bool
    {
        return $this->customerGroupPermissionManager->doesDenyAllPermissionExist($categoryId);
    }
}
