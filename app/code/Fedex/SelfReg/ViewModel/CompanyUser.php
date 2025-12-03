<?php
declare(strict_types=1);

namespace Fedex\SelfReg\ViewModel;

use Fedex\SelfReg\Helper\SelfReg;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogDocumentUserSettings\Helper\Data as CatalogSettingsHelper;


class CompanyUser implements ArgumentInterface
{

    /**
     * @var helper
     */
    private $helper;

    /**
     * @param ToggleConfig $toggleConfig
     * @param SessionFactory $customerSessionFactory
     * @param CompanyManagementInterface $companyRepository
     * @param SelfReg $selfRegHelper
     * @param Data $companyHelper
     * @param AuthHelper $authHelper
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected SessionFactory $customerSessionFactory,
        protected CompanyManagementInterface $companyRepository,
        private SelfReg $selfRegHelper,
        protected Data $companyHelper,
        private CatalogMvp $catalogMvp,
        protected AuthHelper $authHelper,
        CatalogSettingsHelper $helper
    ) {
        $this->helper = $helper;

    }

    /**
     * Is customer is global commercial customer
     *
     * @return boolean
     */
    public function isSharedCatalogEnabled()
    {
        return $this->catalogMvp->isMvpSharedCatalogEnable();
    }


    /**
     * Toggle for Roles and Permission
     *
     * @return boolean
     */
    public function toggleCustomerRolesAndPermissions()
    {
        return $this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions');
    }

    /**
     * Top Menu speed up
     *
     * @return bool
     */
    public function toggleTopMenuSpeedUp(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue('techTitans_B_2279275_topmenu');
    }

    /**
    * Toggle for Roles and Permission
    *
    * @return boolean
    */
    public function toggleUserGroupAndFolderLevelPermissions()
    {
       return $this->toggleConfig->getToggleConfigValue('sgc_user_group_and_folder_level_permissions');
    }

    /**
     * D-134554: EPRO_Unable remove the already saved Ext value in profile
     *
     * @return boolean
     */
    public function isEproCustomer()
    {
        return $this->companyHelper->isEproCustomer();
    }


    /**
     * Checks if the customer is SelfReg
     */
    public function isSelfRegCustomerWithFclEnabled()
    {
            $companyId = "";
            $customerSession = $this->customerSessionFactory->create();
            if ($this->authHelper->isLoggedIn()) {
                $currentWebComId = $this->selfRegHelper->getCompanyIdByWebsiteUrl();
                $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
                if ($companyData) {
                    $companyId = $companyData->getId();
                    $loginMethod = $companyData->getData('storefront_login_method_option');
                    // check if customer belongs to right company
                    if ($companyId == $currentWebComId && $loginMethod === 'commercial_store_wlgn') {
                        return $this->selfRegHelper->checkSelfRegEnable($companyId);
                    }
                }
            }
        return false;
    }

    /**
     * Get toggle value for Millionaires - E-414626: Commercial - User Group Order Approvers
     * @return boolean
     */
    public function getUserGroupOrderApproversToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('commercial_user_group_order_approvers');
    }

    /**
    * Toggle for Company Settings
    *
    * @return boolean
    */
    public function getCompanySettingsToggle()
    {
       return $this->toggleConfig->getToggleConfigValue('explorers_company_settings_customer_admin');
    }

    /**
     * Toggle for spinnerToggle
     *
     * @return boolean
     */
    public function isLoaderRemovedEnable()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('techtitans_bugfix_spinner');
    }

    /**
     * Code to fetch Company Level   Allow Users to Order Documents from their Shared Catalog
     *
     * @return boolean
     */
    public function isAllowedSharedCatalog(): bool {
        $companyConfiguration = $this->helper->getCompanyConfiguration();
        if($companyConfiguration) {
            return (bool) $companyConfiguration->getAllowSharedCatalog();
        }
        return false;
    }

    /**
     * Code to fetch Company Level  B2B Order Approval Workflow
     *
     * @return boolean
     */
    public function isB2BOrderAprovalEnable(): bool {
        $customerSession = $this->customerSessionFactory->create();
        $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
        if ($companyData) {
            $companyAdditionalData = $companyData->getExtensionAttributes()->getCompanyAdditionalData();
            return (bool)$companyAdditionalData->getIsApprovalWorkflowEnabled();
        }
        return false;
    }

    public function displayGroupTypeSection(){
        if($this->isB2BOrderAprovalEnable() && $this->isAllowedSharedCatalog() && $this->getUserGroupOrderApproversToggle() && $this->toggleUserGroupAndFolderLevelPermissions()){
             return true;
        }
        return false;
    }

}
