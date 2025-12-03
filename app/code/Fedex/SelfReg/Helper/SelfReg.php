<?php
declare(strict_types=1);

namespace Fedex\SelfReg\Helper;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\Email;
use Fedex\SelfReg\Model\CompanySelfRegData;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Model\EnhanceUserRolesFactory;
use Fedex\SSO\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class SelfReg extends AbstractHelper
{
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';
    public const D201300_TECH_TITAN_SAVE_DOMAIN_IN_LOWER_CASE =  'D201300_tech_titan_save_domain_in_lower_case';
    public const D241275_TECH_TITAN_approval_email_trigger =  'tech_titans_D_241275';

    const WLGN_ERROR_MSG = 'SelfReg WLGN Error';
    const COMMERCIAL_STORE_WLGN = 'commercial_store_wlgn';
    const COMMERCIAL_STORE_SSO = 'commercial_store_sso';
    const ADMIN_APPROVAL = 'admin_approval';
    const SELFREG = 'selfreg';
    const SDE = 'sde';
    const EPRO = 'epro';

    public function __construct(
        Context                                               $context,
        protected LoggerInterface                             $logger,
        protected ToggleConfig                                $toggleConfig,
        protected CompanySelfRegData                          $selfReg,
        protected SessionFactory                              $customerSessionFactory,
        protected CustomerSession                             $customerSession,
        protected StoreManagerInterface                       $storeManagerInterface,
        protected CompanyFactory                              $companyFactory,
        protected Data                                        $ssoHelper,
        protected PunchoutHelper                              $punchoutHelper,
        protected CustomerFactory                             $customerModelFactory,
        protected CompanyManagementInterface                  $companyRepository,
        protected Email                                       $selfRegEmail,
        protected SdeHelper                                   $sdeHelper,
        protected ConfigInterface                             $configInterface,
        protected AuthHelper                                  $authHelper,
        protected EnhanceRolePermissionFactory                $rolePermissionsFactory,
        protected EnhanceUserRolesFactory                     $enhanceUserRolesFactory,
        protected EnhanceUserRoles                            $enhanceUserRoles,
        protected CustomerRepositoryInterface                 $customerRepositoryInterface,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        protected SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
    }

    /**
     * Get company user permission
     *
     * @param int $companyId
     * @param array $permissions
     * @return array
     */
    public function getCompanyUserPermission($companyId, $permissions = [])
    {
        $connection = $this->enhanceUserRoles->getResource()->getConnection();
        // Get currently logged-in customer (user placing the order)
        $toggleD241275 = $this->toggleConfig->getToggleConfigValue(self::D241275_TECH_TITAN_approval_email_trigger);
            if($toggleD241275) {
                $customerId = null;
                try {
                    if ($this->customerSession->isLoggedIn()) {
                        $customerId = $this->customerSession->getCustomerId();
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error fetching customer session: ' . $e->getMessage());
                    $customerId = null;
                }
                // Base collection - only users in this company
                $collection = $this->enhanceUserRoles->getCollection()
                    ->addFieldToFilter('main_table.company_id', ['eq' => $companyId]);
                // Join role permissions and group permissions
                $collection->getSelect()
                    ->join(
                        ['enhanced_role_permissions' => $connection->getTableName('enhanced_role_permissions')],
                        'enhanced_role_permissions.id = main_table.permission_id',
                        ['enhanced_role_permissions.label']
                    )
                    ->join(
                        ['user_groups_permission' => $connection->getTableName('user_groups_permission')],
                        'user_groups_permission.company_id = main_table.company_id',
                        []
                    );

                /**
                 * Step 1: Identify which group(s) this user belongs to
                 * user_groups_permission.user_id holds each member of the group.
                 */
                if ($customerId) {
                    $userGroupSubSelect = $connection->select()
                        ->from(
                            ['ugp' => $connection->getTableName('user_groups_permission')],
                            ['group_id']
                        )
                        ->where('ugp.user_id = ?', $customerId)
                        ->where('ugp.company_id = ?', $companyId);

                    // Restrict results to only these group_id(s)
                    $collection->getSelect()->where('user_groups_permission.group_id IN (?)', $userGroupSubSelect);
                }

                /**
                 * Step 2: Get only users whose IDs are in the order_approval field
                 * These are the designated approvers for those groups.
                 */
                $collection->getSelect()
                    ->where('FIND_IN_SET(main_table.customer_id, user_groups_permission.order_approval)')
                    ->distinct(true);

                /**
                 *  Step 3: Optional permission filtering (unchanged)
                 */
                if (!empty($permissions)) {
                    $permissionLike = [];
                    foreach ($permissions as $permission) {
                        $permissionLike[] = ['like' => '%::' . $permission];
                    }
                    $collection->addFieldToFilter('enhanced_role_permissions.label', $permissionLike);
                }
            }
            else 
            {
                $collection = $this->enhanceUserRoles->getCollection()
                    ->addFieldToFilter("main_table.company_id", ['eq' => $companyId]);
                $collection->getSelect()
                    ->join(
                        ['enhanced_role_permissions' => 'enhanced_role_permissions'],
                        'enhanced_role_permissions.id = main_table.permission_id',
                        ['enhanced_role_permissions.label']
                    )->join(
                        ['user_groups_permission' => 'user_groups_permission'],
                        'user_groups_permission.company_id = main_table.company_id',
                        []
                    )
                    ->where('FIND_IN_SET(main_table.customer_id, user_groups_permission.order_approval)')
                    ->distinct(true);
            
                    $permissionLike = [];
                    foreach ($permissions as $permission) {
                        $permissionLike[] = ['like' => '%::'.$permission];
                    }
            
                    if (!empty($permissionLike)) {
                        $collection->addFieldToFilter('enhanced_role_permissions.label', $permissionLike);
                    }
        
             }
        //  Return list of approver customer IDs
        return $collection->getColumnValues('customer_id');
    }

    /**
     * Checks if the customer is SelfReg
     */
    public function isSelfRegCustomer()
    {
        static $return = null;
        if (
            $return !== null &&
            $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $companyId = "";
        if(!$this->customerSession->isLoggedIn()){
            $this->customerSession = $this->customerSessionFactory->create();
        }
        $customerSession = $this->customerSession;
        if ($this->authHelper->isLoggedIn()) {
            $currentWebComId = $this->getCompanyIdByWebsiteUrl();
            $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
            if ($companyData) {
                $companyId = $companyData->getId();
                // check if customer belongs to right company
                if ($companyId == $currentWebComId) {
                    $return = $this->checkSelfRegEnable($companyId);
                    return $return;
                }
            }
        }
        $return = false;
        return $return;
    }

    /**
     * Checks if the customer is SelfReg
     */
    public function isSelfRegCustomerWithFclEnabled()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSessionFactory->create();
        }

        if ($this->authHelper->isLoggedIn()) {
            $currentWebComId = $this->getCompanyIdByWebsiteUrl();
            $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
            if ($companyData) {
                $companyId = $companyData->getId();
                $loginMethod = $companyData->getData('storefront_login_method_option');
                // check if customer belongs to right company
                if ($companyId == $currentWebComId && $loginMethod === self::COMMERCIAL_STORE_WLGN) {
                    return $this->checkSelfRegEnable($companyId);
                }
            }
        }
        return false;
    }

    /**
     * Checks if the customer is SSO SelfReg
     */
    public function isSelfRegCustomerWithSSOEnabled()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSessionFactory->create();
        }

        if ($this->authHelper->isLoggedIn()) {
            $currentWebComId = $this->getCompanyIdByWebsiteUrl();
            $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
            if ($companyData) {
                $companyId = $companyData->getId();
                $loginMethod = $companyData->getData('storefront_login_method_option');
                // check if customer belongs to right company
                if ($companyId == $currentWebComId && $loginMethod === self::COMMERCIAL_STORE_SSO) {
                    return $this->checkSelfRegEnable($companyId);
                }
            }
        }
        return false;
    }

    /**
     * Get company admin super user id
     *
     * @return int
     */
    public function companyAdminSuperUserId()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSessionFactory->create();
        }
        $companyId = $customerSession->getCustomerCompany();
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_id', $companyId)->getFirstItem();

        return $companyObj->getSuperUserId();
    }

    /**
     * Checks if the customer is SelfReg Admin
     */
    public function isSelfRegCustomerAdmin()
    {
        static $return = null;
        if (
            $return !== null &&
            $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $loginMethod = '';
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSessionFactory->create();
        }
        $companyId = $customerSession->getCustomerCompany();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        if(!$this->authHelper->isLoggedIn()) {
            $return = false;
            return $return;
        }
        $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
        if ($companyData) {
            $loginMethod = $companyData->getStorefrontLoginMethodOption();
        }
        if ($this->isSelfRegCustomer() || $isSdeStore || $this->isAdminApprovedEnabled($companyId) || ($loginMethod == 'commercial_store_epro')) {
            $companyObj = $this->companyFactory->create()
                ->getCollection()
                ->addFieldToFilter('entity_id', $companyId)->getFirstItem();
            $superAdminId = $companyObj->getSuperUserId();
            if ($customerSession->getId() == $superAdminId) {
                $return = true;
                return $return;
            }
        }
        $return = false;
        return $return;
    }

    /**
     * Checks if the company is SelfReg
     */
    public function isSelfRegCompany($landingRequest = false)
    {
        // B-1515570
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $companyData = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
        } else {
            $companyData = $this->customerSessionFactory->create()->getOndemandCompanyInfo();
        }

        if ($companyData && is_array($companyData) &&
            !empty($companyData['url_extension']) &&
            !empty($companyData['company_type']) &&
            $companyData['company_type'] == self::SELFREG
        ) {
            return true;
        } elseif ($companyData && is_array($companyData) &&
            !empty($companyData['url_extension']) &&
            !empty($companyData['company_type']) &&
            $companyData['company_type'] != self::SELFREG &&
            !empty($companyData['company_data']['storefront_login_method_option']) &&
            $companyData['company_data']['storefront_login_method_option'] == self::COMMERCIAL_STORE_WLGN &&
            !empty($companyData['company_data']['is_sensitive_data_enabled']) &&
            $companyData['company_data']['is_sensitive_data_enabled'] == '0'
        ) {
            return true;
        }
        elseif ($companyData && is_array($companyData) &&
            !empty($companyData['url_extension']) &&
            !empty($companyData['company_type']) &&
            $companyData['company_type'] == self::EPRO
        ) {
            return false;
        } elseif ($landingRequest && !empty($companyData['url_extension']) && !empty($companyData['company_type']) &&
            $companyData['company_type'] == self::SDE &&
            !empty($companyData['company_data']['storefront_login_method_option']) &&
            $companyData['company_data']['storefront_login_method_option'] == self::COMMERCIAL_STORE_WLGN &&
            !empty($companyData['company_data']['is_sensitive_data_enabled']) &&
            $companyData['company_data']['is_sensitive_data_enabled'] == '1'
        ) {
            return true;
        }
        $storeObj = $this->storeManagerInterface->getStore();
        $baseUrl = $storeObj->getBaseUrl();
        $baseUrl = rtrim($baseUrl, '/');
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('company_url', ['like' => '%' . $baseUrl . '%'])->getFirstItem();

        if ($companyObj && $companyObj->getId()) {
            $companyId = $companyObj->getId();
            return $this->checkSelfRegEnable($companyId);
        }
        return false;
    }

    /**
     * Get company id from website url
     *
     * @return int|null
     */
    public function getCompanyIdByWebsiteUrl()
    {
        // B-1515570
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $companyData = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
        } else {
            $companyData = $this->customerSessionFactory->create()->getOndemandCompanyInfo();
        }
        if($companyData && is_array($companyData) &&
            !empty($companyData['url_extension']) &&
            !empty($companyData['company_type']) &&
            $companyData['company_type'] == self::SELFREG &&
            !empty($companyData['company_data']['entity_id'])
        ){
            return $companyData['company_data']['entity_id'];
        }

        $storeObj = $this->storeManagerInterface->getStore();
        $baseUrl = $storeObj->getBaseUrl();
        $baseUrl = rtrim($baseUrl, '/');
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('company_url', ['like' => '%' . $baseUrl . '%'])->getFirstItem();

        return $companyObj->getId();
    }

    /**
     * Get company id from website url
     *
     * @return int|null
     */
    public function getCompanyIdforStoreFront()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $companyData = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
        } else {
            $companyData = $this->customerSessionFactory->create()->getOndemandCompanyInfo();
        }

        if ($companyData && is_array($companyData) &&
            !empty($companyData['url_extension']) &&
            !empty($companyData['company_data']['entity_id']) &&
            !empty($companyData['company_data']['storefront_login_method_option']) &&
            $companyData['company_data']['storefront_login_method_option'] == self::COMMERCIAL_STORE_WLGN
        ) {
            return $companyData['company_data']['entity_id'];
        }

        return false;
    }

    /**
     * Check if admin approve set
     *
     * @param int $companyId
     * @return boolean
     */
    public function isAdminApprovedEnabled($companyId)
    {
        $companyData = $this->getSettingByCompanyId($companyId);
        if (
            !empty($companyData) &&
            isset($companyData['self_reg_login_method']) &&
            $companyData['self_reg_login_method'] == self::ADMIN_APPROVAL
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if selfReg Enable for current company
     *
     * @param int $companyId
     * @return boolean
     */

    public function checkSelfRegEnable($companyId)
    {
        $companyData = $this->getSettingByCompanyId($companyId);
        if (
            !empty($companyData) &&
            isset($companyData['enable_selfreg']) &&
            $companyData['enable_selfreg']
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check User Role Permission Toggle
     *
     * @return boolean
     */
    public function toggleUserRolePermissionEnable()
    {
        return $this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions');
    }

    /**
     * B-1320022 - WLGN integration for selfReg customer
     *
     * @param String $endUrl
     * @param String $fdxLogin
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function selfRegWlgnLogin($endUrl, $fdxLogin)
    {
        $response = '';
        try {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customerSession = $this->getOrCreateCustomerSession();
            } else {
                $customerSession = $this->customerSessionFactory->create();
            }
            $profileDetails = $this->ssoHelper->getProfileByProfileApi($endUrl, $fdxLogin);

            $storeObj = $this->storeManagerInterface->getStore();
            $storeId = $storeObj->getId();
            $baseUrl = $storeObj->getBaseUrl();
            $websiteId = $this->storeManagerInterface->getWebsite()->getWebsiteId();

            $companyData = $customerSession->getOndemandCompanyInfo();

            if (
                !empty($profileDetails['address']['firstName']) && !empty($profileDetails['address']['lastName']
                    && !empty($profileDetails['address']['uuId']))
                && !empty($profileDetails['address']['email']) && !empty($websiteId)
            ) {

                // B-1341979 - Check if customer is company admin
                $profileEmail = $profileDetails['address']['email'];

                if ($companyData && is_array($companyData) &&
                    !empty($companyData['company_data']['storefront_login_method_option']) &&
                    $companyData['company_data']['storefront_login_method_option'] == self::COMMERCIAL_STORE_WLGN
                ) {
                    $companyId = $this->getCompanyIdforStoreFront();
                } else {
                    $companyId = $this->getCompanyIdByWebsiteUrl();
                }

                $uuid = $profileData['address']['uuId'] ?? "";
                $uuidEmail = $uuid . "@fedex.com";
                $isCompanyAdmin = $this->checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId, $uuidEmail);

                // login customer
                if ($isCompanyAdmin) {
                    return $this->loginCompanyCustomer(
                        $customerSession,
                        $websiteId,
                        $profileEmail,
                        $baseUrl
                    );
                }

                $fclUuid = $profileDetails['address']['uuId'];
                $fclUuidEmail = (string) $fclUuid . "@fedex.com";
                $customerId = $this->ssoHelper->getCustomerIdByUuid($fclUuid);

                // Check company setting and perform accordingly
                $selfRegSetting = $this->getSettingByCompanyId($companyId);
                $loginMethod = $selfRegSetting['self_reg_login_method'] ?? 'registered_user';
                $errorMsg = $selfRegSetting['error_message'] ?? '';

                if (!$customerId) {
                    // if customer logged in at magento end, logout first
                    $this->isCustomerDactiveAnLogout(true, $customerSession, true, $errorMsg);//D-120897

                    $customerObj = $this->registerCompanyCustomer(
                        $loginMethod,
                        $websiteId,
                        $storeId,
                        $fclUuidEmail,
                        $profileDetails,
                        $customerSession
                    );

                    // in case of error, return array
                    if (is_array($customerObj)) {
                        $errorMsg = $customerObj['msg'] ?? 'Issue in customer registration.';
                        $this->logger->error(self::WLGN_ERROR_MSG . $errorMsg);
                        $customerSession->setSelfRegLoginError($errorMsg);
                        return  ['error' => true, 'msg' => $errorMsg];
                    }

                    $customerId = $this->getCustomerIdByCustomerObject($customerObj);
                    // send notification to admin to user active
                    if ($loginMethod == self::ADMIN_APPROVAL) {
                        $secondaryEmail = $profileDetails['address']['email'];
                        $firstName = $profileDetails['address']['firstName'];
                        $lastName = $profileDetails['address']['lastName'];
                        $fullName = $firstName . ' ' . $lastName;

                        $this->sendEmailToAdmin($baseUrl, $fullName, $secondaryEmail, $companyId);
                    }

                    $customerObj->setCustomerUuidValue($fclUuid)->save();
                    $this->logger->info("SelfReg WLGN Success: Customer is created
                                            successfully with UUID : " . $fclUuid);
                }

                // update basic info
                $customer = $this->updateCustomerBasicInfo($customerId, $websiteId, $profileDetails);

                // save address
                $isSdeStore = $this->sdeHelper->getIsSdeStore();
                if (!$isSdeStore|| $this->sdeHelper->getIsRequestFromSdeStoreFclLogin($customerId)){
                    $this->ssoHelper->saveAddress($customer, $profileDetails['address'], 'address');
                }

                // validate if domain_registration is set in company
                $isValid = $this->isValidateDomainRegistration(
                    $loginMethod,
                    $selfRegSetting,
                    $customer,
                    $customerSession
                );

                $response = $this->getComplaxityResponse(
                    $isValid,
                    $customer,
                    $websiteId,
                    $fclUuidEmail,
                    $baseUrl,
                    $customerSession,
                    $errorMsg
                );
                // check if customer is not active

            } else {
                $this->logger->error(self::WLGN_ERROR_MSG ." Profile API Issue");
                $customerSession->setSelfRegLoginError('Profile API Issue.');
                $errorMsg = 'Profile API issue.';
                $response = ['error' => true, 'msg' => 'Profile API issue.'];
            }
        } catch (\Exception $e) {
            $errorMsg = "SelfReg WLGN Exception: " . $e->getMessage();
            $this->logger->critical($errorMsg);
            $customerSession->setSelfRegLoginError($errorMsg);
            $response = ['error' => true, 'msg' => $e->getMessage()];

        }
        return $response;
    }
    /**
     * getComplaxityResponse
     *
     * @return string
     */
    public function getComplaxityResponse(
        $isValid,
        $customer,
        $websiteId,
        $fclUuidEmail,
        $baseUrl,
        $customerSession,
        $errorMsg
    )
    {
        $response = null;
        if (is_null($isValid)) {
            $isCustActive = $this->punchoutHelper->isActiveCustomer($customer);
            $response = $this->isCustomerDactiveAnLogout($isCustActive, $customerSession, false, $errorMsg);//D-120897
            // login customer
            $response = $response ?? $this->loginCompanyCustomer(
                $customerSession,
                $websiteId,
                $fclUuidEmail,
                $baseUrl
            );
        } else {
            $response = $isValid;
        }
        return $response;
    }
    /**
     * getCustomerIdByCustomerObject
     *
     * @return number
     */
    public function getCustomerIdByCustomerObject($customerObj) {
        if ($customerObj && !is_array($customerObj) && $customerObj->getId()) {
            return $customerObj->getId();
        }
    }
    /**
     * isCustomerDactiveAnLogout
     *
     * @return string
     */
    public function isCustomerDactiveAnLogout($isCustActive, $customerSession, $isLogout, $errorMsg)//D-120897
    {
        if (!$isCustActive && !$isLogout) {

            $errorMsg = !empty($errorMsg) ? $errorMsg : 'Customer is not Active.';
            $this->logger->error(self::WLGN_ERROR_MSG . $errorMsg);
            $customerSession->setSelfRegLoginError($errorMsg);
            return ['error' => true, 'msg' => $errorMsg];

        } elseif ($isLogout && $this->authHelper->isLoggedIn()) {

            $custId = $customerSession->getCustomer()->getId();
            $customerSession->logout()->setLastCustomerId($custId);
        }
        return null;
    }

    /**
     * isValidateDomainRegistration
     *
     * @return string
     */
    public function isValidateDomainRegistration($loginMethod, $selfRegSetting, $customer, $customerSession)
    {

        if ($loginMethod == 'domain_registration') {
            $allowedDomains = $selfRegSetting['domains'] ?? null;
            // validate domain
            $secondaryEmail = $customer->getSecondaryEmail();
            $isValidDomain = $this->validateDomain($secondaryEmail, $allowedDomains);
            $errorMsg = !empty($selfRegSetting['error_message']) ?
                $selfRegSetting['error_message'] : 'Domain validation failed.';
            if (!$isValidDomain) {
                // redirect to error page
                $customerSession->setSelfRegLoginError($errorMsg);
                return ['error' => true, 'msg' => $errorMsg, 'loginMethod' => $loginMethod];
            }
        }
        return null;
    }

    /**
     * B-1320022 - Register selfReg customer
     *
     * @param String $loginMethod
     * @param Int $websiteId
     * @param Int $storeId
     * @param String $fclUuidEmail
     * @param Array $profileDetails
     * @param Object $customerSession
     * @return array
     */
    public function registerCompanyCustomer(
        $loginMethod,
        $websiteId,
        $storeId,
        $fclUuidEmail,
        $profileDetails,
        $customerSession
    )
    {
        $companyData = $customerSession->getOndemandCompanyInfo();
        $isSelfRegReq = true;
        if ($companyData && is_array($companyData) &&
            !empty($companyData['company_data']['storefront_login_method_option']) &&
            $companyData['company_data']['storefront_login_method_option'] == self::COMMERCIAL_STORE_WLGN
        ) {
            $companyId = $this->getCompanyIdforStoreFront();
        } else {
            $companyId = $this->getCompanyIdByWebsiteUrl();
        }

        $verified['company_id'] = $companyId;
        $verified['website_id'] = $websiteId;
        $verified['store_id'] = $storeId;
        $verified['group_id'] = $this->getGroupIdFromCompId($companyId);

        $customerData['email'] = $fclUuidEmail;
        $customerData['firstname'] = $profileDetails['address']['firstName'];
        $customerData['lastname'] = $profileDetails['address']['lastName'];

        if ($loginMethod == self::ADMIN_APPROVAL) {
            $customerData['status'] = 'inactive';
            $customerData['pending_approval_toggle'] = true;
        }
        return  $this->punchoutHelper->autoRegister($customerData, $verified, $isSelfRegReq);
    }

    /**
     * B-1320022 - Login Company customer
     *
     * @param Object $customerSession
     * @param Int $websiteId
     * @param String $fclUuidEmail
     * @param String $baseUrl
     * @return array
     */
    public function loginCompanyCustomer($customerSession, $websiteId, $fclUuidEmail, $baseUrl)
    {
        $companyData = $customerSession->getOndemandCompanyInfo();
        if ($companyData && is_array($companyData) &&
            !empty($companyData['company_data']['storefront_login_method_option']) &&
            $companyData['company_data']['storefront_login_method_option'] == self::COMMERCIAL_STORE_WLGN
        ) {
            $companyId = $this->getCompanyIdforStoreFront();
        } else {
            $companyId = $this->getCompanyIdByWebsiteUrl();
        }
        $customerModel = $this->customerModelFactory->create();
        $customerInfo = $customerModel
            ->setWebsiteId($websiteId)
            ->loadByEmail($fclUuidEmail);

        if ($customerInfo && $customerInfo->getId()) {
            $companyObj = $this->companyFactory->create()
                ->getCollection()
                ->addFieldToFilter('entity_id', $companyId)->getFirstItem();

            $customerSession->setCustomerAsLoggedIn($customerInfo);
            $customerSession->setCustomerCompany($companyId);
            $customerSession->setBackUrl($baseUrl);
            $customerSession->setCommunicationUrl($baseUrl);
            $customerSession->setCompanyName($companyObj->getCompanyName());
            if ($this->authHelper->isLoggedIn()) {
                $this->logger->info("SelfReg WLGN Success: Customer logged-in successfully.");
                $customerData = $customerInfo->getData();
                return [
                    'error' => false, 'msg' => 'Customer logged-in successfully.',
                    'customerData' => $customerData, 'redirectUrl' => $baseUrl
                ];
            } else {
                $customerSession->setSelfRegLoginError(self::WLGN_ERROR_MSG . ' Issue in customer login.');
                $this->logger->error(self::WLGN_ERROR_MSG .'Issue in customer login.');
                return ['error' => true, 'msg' => 'Issue in customer login.'];
            }
        } else {
            $msg = 'Customer not found with given emailID.';
            $this->logger->error(self::WLGN_ERROR_MSG . $msg);
            $customerSession->setSelfRegLoginError($msg);
            return ['error' => true, 'msg' => $msg];
        }
    }

    /**
     * B-1320022 - update customer basic information
     *
     * @param Int $customerId
     * @param Int $websiteId
     * @param String $profileDetails
     * @return Object
     */
    public function updateCustomerBasicInfo($customerId, $websiteId, $profileDetails)
    {
        $customer = $this->customerModelFactory->create();
        $customer->setWebsiteId($websiteId)->load($customerId);
        $customer->setFirstname($profileDetails['address']['firstName']);
        $customer->setLastname($profileDetails['address']['lastName']);
        $customer->setSecondaryEmail($profileDetails['address']['email']);
        $customer->setFclProfileContactNumber($profileDetails['address']['contactNumber']);
        if (strlen($profileDetails['address']['contactNumber']) < 10) {
            $profileDetails['address']['contactNumber'] = '1111111111';
        }
        $customer->setContactNumber($profileDetails['address']['contactNumber']);
        $customer->setContactExt($profileDetails['address']['ext']);
        return $customer->save();
    }

    /**
     * B-1320022 - Get company setting by CompanyId
     *
     * @param Int $companyId
     * @return Object
     */
    public function getSettingByCompanyId($companyId)
    {
        $companyData = [];
        $companyObj = $this->companyFactory->create()->load($companyId);

        if ($companyObj->getData('self_reg_data')) {
            $companyData = json_decode($companyObj->getData('self_reg_data'), true);
        }

        return $companyData;
    }

    /**
     * B-1291439 - Self-registered login using domain registration
     *
     * @param String $customerEmail
     * @param String $allowedDomains
     * @return bool
     */

    public function validateDomain($customerEmail, $allowedDomains)
    {
        if (empty(trim($allowedDomains))) {
            return true;
        }

        $emailDomain = null;
        if ($customerEmail) {
            $email = explode('@', $customerEmail);
            $toggleD201300 = $this->toggleConfig->getToggleConfigValue(self::D201300_TECH_TITAN_SAVE_DOMAIN_IN_LOWER_CASE);
            if ($toggleD201300) {
                $emailDomain = isset($email[1]) ? strtolower($email[1]) : null;
            } else {
                $emailDomain = $email[1] ?? null;
            }

            if ($emailDomain) {
                $domainArray = explode(',', $allowedDomains);

                foreach ($domainArray as $k => $compDomain) {
                    $compDomain = trim($compDomain);
                    if ($toggleD201300) {
                        $compDomain = strtolower($compDomain);
                    }
                    if ($emailDomain == $compDomain) {
                        $this->logger->info("SelfReg WLGN : Customer's Email Domain is allowed to login.");
                        return true;
                    }
                }
            } else {
                $this->logger->error(self::WLGN_ERROR_MSG ." Invalid customer Email.");
            }
        }
        return false;
    }

    /**
     * B-1320022 - WLGN integration for selfReg customer
     *
     * @param Int $companyId
     * @return int
     */
    public function getGroupIdFromCompId($companyId)
    {
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_id', $companyId)->getFirstItem();

        return $companyObj->getCustomerGroupId();
    }

    /**
     * B-1341979 - Check if customer is company admin
     *
     * @param Int $websiteId
     * @param String $profileEmail
     * @param Int $companyId
     * @return bool
     */
    public function checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId, $uuidEmail = null)
    {
        $customerId = null;
        $emailAddresses = [$profileEmail, $uuidEmail];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('email', $emailAddresses, 'in')
            ->create();
        $customerList = $this->customerRepositoryInterface->getList($searchCriteria);

        if ($customerList && $customerList->getTotalCount() > 0) {
            foreach ($customerList->getItems() as $customer) {
                $customerId = $customer->getId();
            }
        }
        //customer admin

        if ($customerId) {
            $companyObj = $this->companyFactory->create()
                ->getCollection()
                ->addFieldToFilter('entity_id', $companyId)
                ->getFirstItem();
            $superUserId = $companyObj->getSuperUserId();

            if ($customerId == $superUserId) {
                return true;
            }
        }
        return false;
    }

    /**
     * B-1351502 - send email notification to admin
     *
     * @param String $baseUrl
     * @param String $customerFullName
     * @param String $secondaryEmail
     * @param Int $companyId
     */
    public function sendEmailToAdmin($baseUrl, $customerFullName, $secondaryEmail, $companyId)
    {
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_id', $companyId)
            ->getFirstItem();

        $superUserId = $companyObj->getSuperUserId();
        $companyName = $companyObj->getCompanyName();

        $companyAdmin = $this->customerModelFactory->create()->load($superUserId);
        $adminName = $adminEmail = null;

        if ($companyAdmin && $companyAdmin->getId()) {
            $adminName = $companyAdmin->getName();
            $adminEmail = $companyAdmin->getEmail();
        }

        if ($companyName && $customerFullName && $adminName && $adminEmail && $secondaryEmail) {
            $customerDataCC = [];
            if ($this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions') && $superUserId > 0) {
                $customer = $this->customerRepositoryInterface->getById($superUserId);

                if ($this->toggleConfig->getToggleConfigValue('mazegeeks_d196314_fix')) {
                    $secondaryCustomAttribute = $customer->getCustomAttribute('secondary_email');
                    if ($secondaryCustomAttribute && $secondaryCustomAttribute->getValue()) {
                        $adminEmail = $secondaryCustomAttribute->getValue();
                    }
                } else {
                    $adminEmail = $customer->getCustomAttribute('secondary_email')->getValue();
                }

                $customerDataCC = $this->getEmailNotificationAllowCustomer($companyId);
            }
            $this->selfRegEmail->sendPendingEmail(
                $baseUrl,
                $companyName,
                $customerFullName,
                $secondaryEmail,
                $adminEmail,
                $adminName,
                $customerDataCC
            );
            $this->logger->info("SelfReg WLGN : Email notification
            to approve customer, has been sent to company admin.");
        }
    }

    /**
     * check permission for a user
     *
     * @param  string $companyId
     * @return array
     */
    public function checkPermission($companyId)
    {
        $permissions=null;
        if($this->customerSession->getCustomer()){
            $customer_id=$this->customerSession->getCustomer()->getId();
            $collection = $this->enhanceUserRoles->getCollection()->addFieldToFilter(
                "company_id",
                ['eq' => $companyId]
            )->addFieldToFilter(
                "customer_id",
                ['eq' => $customer_id]
            );
            $collection->getSelect()
                ->join(
                    ['enhanced_role_permissions' => 'enhanced_role_permissions'],
                    'enhanced_role_permissions.id = main_table.permission_id',
                    ['enhanced_role_permissions.label']
                );
            if(!empty($collection->getData()))
            {
                foreach($collection->getData() as $row)
                {
                    $permissions[]=$row['label'];
                }
            }
        }
        return $permissions;
    }

    /**
     * Get List of sser having email notification permission
     * @param int $companyId
     * @return array
     */
    public function getEmailNotificationAllowCustomer($companyId)
    {
        $collection = $this->enhanceUserRoles->getCollection()->addFieldToFilter(
            "company_id",
            ['eq' => $companyId]
        );
        $collection->getSelect()
            ->join(
                ['enhanced_role_permissions' => 'enhanced_role_permissions'],
                'enhanced_role_permissions.id = main_table.permission_id',
                ['enhanced_role_permissions.label']
            );
        $collection->addFieldToFilter('enhanced_role_permissions.label', ['like' => '%::manage_users']);
        $collection->addFieldToFilter('enhanced_role_permissions.label', ['like' => '%::email_allow::manage_users']);

        $userIds =  $collection->getColumnValues("customer_id");
        $userData = [];
        foreach($userIds as $customerId) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $emailAddress = $customer->getCustomAttribute('secondary_email')->getValue();
            $customerName = $customer->getFirstname() . " ". $customer->getLastname();
            $userData[] = ['name' => $customerName, 'address' => $emailAddress];
        }
        return $userData;
    }

    /**
     * Get List of user having catalog expiration email notification permission
     * @param int $companyId
     * @return array
     */
    public function getEmailNotificationAllowUserList($companyId)
    {
        $collection = $this->enhanceUserRoles->getCollection()->addFieldToFilter(
            "company_id",
            ['eq' => $companyId]
        );
        $collection->getSelect()
            ->join(
                ['enhanced_role_permissions' => 'enhanced_role_permissions'],
                'enhanced_role_permissions.id = main_table.permission_id',
                ['enhanced_role_permissions.label']
            );
        $collection->addFieldToFilter('enhanced_role_permissions.label', ['like' => '%::manage_catalog']);

        $userIds =  $collection->getColumnValues("customer_id");
        $userData = [];
        $emailAddress = null;
        foreach($userIds as $customerId) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            if ($this->toggleConfig->getToggleConfigValue('explorers_d200560_fix')) {
                $secondaryCustomAttribute = $customer->getCustomAttribute('secondary_email');
                if ($secondaryCustomAttribute && $secondaryCustomAttribute->getValue()) {
                    $emailAddress = $secondaryCustomAttribute->getValue();
                } else {
                    $emailAddress = $customer->getEmail();
                }
            } else {
                $emailAddress = $customer->getCustomAttribute('secondary_email')->getValue();
            }
            $customerName = $customer->getFirstname() . " ". $customer->getLastname();
            $userData[] = ['name' => $customerName, 'address' => $emailAddress];
        }
        return $userData;
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->customerSession->isLoggedIn()){
            $this->customerSession = $this->customerSessionFactory->create();
        }
        return $this->customerSession;
    }

}
