<?php

namespace Fedex\SelfReg\Block\User;

use Fedex\SelfReg\Model\EnhanceRolePermission;
use Magento\Framework\View\Element\Template\Context;
use Fedex\CatalogDocumentUserSettings\Helper\Data as HelperData;
use Fedex\Login\Helper\Login;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Customer\Model\CustomerIdProvider;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Search extends \Magento\Framework\View\Element\Template
{
    const ADMIN_APPROVAL = 'admin_approval';

    /**
     * @param Context $context
     * @param EnhanceRolePermission $rolePermissions
     * @param HelperData $helperData
     * @param Login $loginHelper
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param SdeHelper $sdeHelper
     * @param CustomerIdProvider $customerIdProvider
     * @param EnhanceUserRoles $enhancedUserRoles
     * @param CompanyManagementInterface $companyRepository
     * @param ToggleConfig $toggleConfig
     */

    public function __construct(
        Context $context,
        protected EnhanceRolePermission $rolePermissions,
        protected HelperData $helperData,
        protected Login $loginHelper,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        protected SdeHelper $sdeHelper,
        protected CustomerIdProvider $customerIdProvider,
        protected EnhanceUserRoles $enhancedUserRoles,
        protected CompanyManagementInterface $companyRepository,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    public function isAllowSharedCatalog()
    {
        $companyConfiguration = $this->helperData->getCompanyConfiguration();
        return $companyConfiguration->getAllowSharedCatalog();
    }
    public function getRolePermission()
    {
        $collection = $this->rolePermissions->getCollection()->addFieldToFilter("sort_order", ['gteq' => "1"]);
        if (!$this->isAllowSharedCatalog()) {
            $collection->addFieldToFilter('label', ['nlike' => '%::manage_catalog']);
        }
        if (!$this->orderApprovalViewModel->isOrderApprovalB2bEnabled()) {
            $collection->addFieldToFilter('label', ['nlike' => '%::review_orders']);
        }
        $collection->setOrder('sort_order', 'ASC');
        return $collection;
    }

    public function getAllRolePermission()
    {
        $collection = $this->rolePermissions->getCollection();
        if (!$this->isAllowSharedCatalog()) {
            $collection->addFieldToFilter('label', ['nlike' => '%::manage_catalog']);
        }
        if (!$this->orderApprovalViewModel->isOrderApprovalB2bEnabled()) {
            $collection->addFieldToFilter('label', ['nlike' => '%::review_orders']);
        }
        $collection->setOrder('sort_order', 'ASC');
        return $collection;
    }

    public function getManageruserEmailAllow($collection)
    {
        foreach ($collection as $permission) {
            if ($permission->getLabel() == "Yes::email_allow::manage_users") {
                return $permission->getId();
            }
        }
        return false;
    }

    public function getManageruserEmailDeny($collection)
    {
        foreach ($collection as $permission) {
            if ($permission->getLabel() == "No::email_deny::manage_users") {
                return $permission->getId();
            }
        }
        return false;
    }

    /**
     * Check User Registration approval is admin or not
     * @return boolean
     */
    public function isShowEmailSendingSection()
    {
        $loginMethod = $this->loginHelper->getCommercialFCLApprovalType();
        $approvalType = isset($loginMethod['login_method']) ? $loginMethod['login_method'] : "";
        return ($approvalType == self::ADMIN_APPROVAL) ? true : false;
    }

    /**
     * Check for sde store
     *
     * @return boolean
     */
    public function isSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Get enhanced user role permissions for the current customer
     *
     * @return array
     */
    public function getCustomerPermissions()
    {
        $customerPermissions = [];
        $customerId = $this->customerIdProvider->getCustomerId();
        $customerCompany = $this->companyRepository->getByCustomerId($customerId);
        if ($customerCompany) {
            $companyId = $customerCompany->getId();
        } else {
            $companyId = null;
        }

        if ($customerId && $companyId) {
            $collection = $this->enhancedUserRoles->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('company_id', $companyId);
            $customerPermissions = array_fill_keys($collection->getColumnValues('permission_id'), true);
        }

        return $customerPermissions;
    }

    /**
     * Get customer role permissions when accessing from admin
     *
     * @return array
     */
    public function getCustomerRolePermissions()
    {
        $enhancedRolePermissioncollection = $this->rolePermissions->getCollection();
        $isCompanyOrderApproverEnabled = false;
        $isSharedCatalogAllowed = false;
        $isGlobalOrderApproverEnabled = (bool) $this->toggleConfig->getToggleConfigValue('xmen_order_approval_b2b');
        $customerId = $this->customerIdProvider->getCustomerId();
        $customerCompany = $this->companyRepository->getByCustomerId($customerId);
        if ($customerCompany) {
            $isSharedCatalogAllowed = (bool) $customerCompany->getAllowSharedCatalog();
            $companyAdditionalData = $customerCompany->getExtensionAttributes()->getCompanyAdditionalData();
            $isCompanyOrderApproverEnabled = (bool) $companyAdditionalData->getIsApprovalWorkflowEnabled() &&
                $isGlobalOrderApproverEnabled;
        }
        if (!$isCompanyOrderApproverEnabled) {
            $enhancedRolePermissioncollection->addFieldToFilter('label', ['nlike' => '%::review_orders']);
        }
        if (!$isSharedCatalogAllowed) {
            $enhancedRolePermissioncollection->addFieldToFilter('label', ['nlike' => '%::manage_catalog']);
        }
        $enhancedRolePermissioncollection->setOrder('sort_order', 'ASC');
        return $enhancedRolePermissioncollection;
    }
}
