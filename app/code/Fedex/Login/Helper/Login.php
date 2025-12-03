<?php

namespace Fedex\Login\Helper;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\CIDPSG\Helper\Email;
use Fedex\Customer\Helper\Customer;
use Fedex\Customer\Model\CustomerStatus;
use Fedex\EmailVerification\Model\EmailVerification;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Login\Model\Config;
use Fedex\Login\Model\LoginType;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SDE\Model\ForgeRock;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\Model\Config as SSOConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\Company\Helper\UserPreferenceHelper;

class Login extends AbstractHelper
{
    private const ID_TOKEN = 'id_token';
    const ADMIN_APPROVAL = 'admin_approval';
    const AUTO_APPROVAL = 'registered_user';
    const DOMAIN_APPROVAL = 'domain_registration';
    const SSO_FCL = 'sso_fcl';

    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    protected $endUrl;
    protected $cookieData;
    public $profileApprovalMessage = "";
    public $errorCode = "";
    public $loginMethod = "";

    public function __construct(
        Context                                   $context,
        protected Session                         $session,
        protected SessionFactory                  $customerSession,
        protected CompanyFactory                  $companyFactory,
        protected StoreManagerInterface           $storeManager,
        protected LoggerInterface                 $logger,
        protected Customer                        $customerHelper,
        protected SSOConfig                       $ssoConfig,
        protected CookieManagerInterface          $cookieManager,
        protected SSOHelper                       $ssoHelper,
        protected CustomerRepositoryInterface     $customerRepositoryInterface,
        protected CustomerInterfaceFactory        $customerInterfaceFactory,
        protected CompanyManagementInterface      $companyManagement,
        protected CustomerFactory                 $customerFactory,
        protected SdeHelper                       $sdeHelper,
        protected ForgeRock                       $forgeRock,
        protected ToggleConfig                    $toggleConfig,
        protected SelfReg                         $selfRegHelper,
        protected CompanyCustomerInterfaceFactory $compCustInterface,
        protected StoreFactory                    $storeFactory,
        protected PunchoutHelper                  $punchoutHelper,
        public CanvaCredentials                   $canvaCredentials,
        protected Email                           $sendEmail,
        protected CookieMetadataFactory           $cookieMetadataFactory,
        protected Config                          $moduleConfig,
        protected EmailVerification               $emailVerification,
        protected AuthHelper                                  $authHelper,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        protected FuseBidViewModel $fuseBidViewModel,
        protected UserPreferenceHelper $userPreferenceHelper
    ) {
        parent::__construct($context);
        $this->fuseBidViewModel = $fuseBidViewModel;
    }

    /**
     * Get Customer Profile Data
     *
     * @param  string $loginType
     * @return array
     */
    public function getProfileData($loginType, $uuid = false)
    {
        $this->endUrl = $this->ssoConfig->getProfileApiUrl();
        if ($loginType == "sso") {
            $this->cookieData = $this->cookieManager->getCookie(SSOHelper::SDE_COOKIE_NAME);
            if (!$this->cookieData) {
                $forgeRockCookie = $this->cookieManager->getCookie(self::ID_TOKEN);
                $idTokenRequest = $this->_request->getParam(self::ID_TOKEN);

                /**
                 * @todo check why id_token is not set in the cookie via PHP
                 */
                if(empty($forgeRockCookie) && ! empty($idTokenRequest)) {
                    $forgeRockCookie = $idTokenRequest;
                }
                // @codeCoverageIgnoreStart
                if ($forgeRockCookie) {
                    $this->cookieData = $forgeRockCookie;
                }
                // @codeCoverageIgnoreEnd
            }

            if ($this->toggleConfig->getToggleConfigValue('techtitans_d_193751')) {
                if ($this->ssoHelper->getSSOWithFCLToggle()) {
                    $profileData = $this->ssoHelper->getProfileByProfileApi($this->endUrl, $this->cookieData, false, $loginType);
                } else {
                    $profileData = $this->ssoHelper->getProfileByProfileApi($this->endUrl, $this->cookieData);
                }
                if (empty($profileData['address']['uuId']) && !isset($profileData['company_url_extension'])) {
                    $customerId = $profileData['address']['customerId'] ?? null;
                    if ($customerId) {
                        // Extract IDP ID from customerId (pattern: idp_subject)
                        $idpParts = explode('_', $customerId);
                        if (count($idpParts) >= 2) {
                            $idpId = $idpParts[0];

                            // Look up company by SSO ID
                            $company = $this->lookupCompanyBySsoIdp($idpId);
                            if ($company && isset($company['company_url_extention'])) {
                                $profileData['company_id'] = $company['entity_id'];
                                $profileData['company_url_extension'] = $company['company_url_extention'];
                            }
                        }
                    }
                }
                return $profileData;
            }


        } else {
            if ($this->ssoHelper->getFCLCookieNameToggle()) {
                $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                $this->cookieData = $this->cookieManager->getCookie($cookieName);
            } else {
                $this->cookieData = $this->cookieManager->getCookie('fdx_login');
            }
        }
        if($uuid) {
            $this->endUrl = $this->getWireMockProfileUrl();
        }

        return $this->ssoHelper->getProfileByProfileApi($this->endUrl, $this->cookieData, $uuid, $loginType);
    }

    /**
     * Look up company by SSO ID
     *
     * @param string $ssoId
     * @return array|null
     */
    private function lookupCompanyBySsoIdp(string $ssoId): ?array
    {
        try {
            $companyCollection = $this->companyFactory->create()
                ->getCollection()
                ->addFieldToFilter('sso_idp', $ssoId)
                ->setPageSize(1);

            return $companyCollection?->getFirstItem()?->getData() ?: null;
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    '%s in %s on line %d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
            return null;
        }
    }

    /**
     * Create SSO Account
     *
     * @param  array $profileDetails
     * @return bool|array
     */
    public function createSSOAccount($profileDetails)
    {
        try {
            $companyId = $this->getCompanyId();
            if (!$companyId) {
                $companyId = $profileDetails['company_id'] ?? null;
            }

            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $firstName = $profileDetails['address']['firstName'] ?? '';
            $lastName = $profileDetails['address']['lastName'] ?? '';
            $secondaryEmail = $profileDetails['address']['email'] ?? '';
            $customerEmail = $profileDetails['address']['customerEmail'] ?? '';
            // this value always null as SSO not return any uuuid
            $uuid = $profileDetails['address']['uuId'] ?? '';

            if ($websiteId && !empty($firstName) && !empty($lastName) && !empty($customerEmail)) {
                $customer = $this->customerInterfaceFactory->create();
                $customer->setWebsiteId($websiteId);
                $customer->setFirstname(ucfirst($firstName));
                $customer->setLastname(ucfirst($lastName));
                $customer->setEmail($customerEmail);
                $customer->setStoreId($this->storeManager->getStore()->getStoreId());
                if ($companyId) {
                    $customerGroupId = $this->ssoHelper->getCompanyCustomerGroupId($companyId);
                    if ($customerGroupId) {
                        $customer->setGroupId($customerGroupId);
                    }
                }
                if ($this->toggleConfig->getToggleConfigValue('techtitans_d_193751')) {
                    $customer->setCustomAttribute('customer_status', 1);
                }

                $customerId = $this->customerRepositoryInterface->save($customer)->getId();
                // Assign company
                if ($companyId) {
                    $this->companyManagement->assignCustomer($companyId, $customerId);
                }
                $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($customerEmail);
                // Save Secondary Email if avilable in profile data
                $customer->setSecondaryEmail($secondaryEmail);
                $customer->save();
                $responseData['customer'] = $customer;
                $responseData['uuidEmail'] = $customerEmail;
                $responseData['companyId'] = $companyId;
                $responseData['uuid'] = $uuid;
                return $responseData;
            } else {
                $this->customerSession->unsFclFdxLogin();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Profile API Issue');
            }
        } catch (Exception $e) {
            $this->customerSession->create()->unsFclFdxLogin();
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ':Customer is not created with error: ' . $e->getMessage()
            );
        }

        return false;
    }

    /**
     * Check if user is company admin user
     *
     * @param  array  $profileData
     * @param  string $loginType
     * @param  int    $companyId
     * @return bool
     */
    public function isCompanyAdminUser($profileData, $loginType, $companyId, $uuidEmail = null, $existingCustomer = null)
    {
        if ($companyId) {
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $profileEmail = $profileData['address']['email'] ?? "";
            $isCompanyAdmin = $this->selfRegHelper->checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId, $uuidEmail);


            if ($isCompanyAdmin) {
                if($uuidEmail && $existingCustomer) {
                    $this->customerHelper->updateExternalIdentifier($uuidEmail, $existingCustomer->getId(), $profileEmail, $existingCustomer);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Get company login method
     *
     * @return array|bool
     */
    public function getCommercialFCLApprovalType($companyId = null)
    {
        if (!$companyId) {
            $companyId = $this->getCompanyId();
        }
        if ($companyId) {
            $approvalSetting = $this->selfRegHelper->getSettingByCompanyId($companyId);
            $loginMethod = $approvalSetting['self_reg_login_method'] ?? 'registered_user';
            $errorMsg = $approvalSetting['error_message'] ?? '';
            $domains = $approvalSetting['domains'] ?? null;
            $emailVerificationDisplayMessage = $approvalSetting['fcl_user_email_verification_user_display_message'] ?? '';


            return ['login_method' => $loginMethod, 'error_msg' => $errorMsg, 'domains' => $domains, 'fcl_user_email_verification_user_display_message' => $emailVerificationDisplayMessage];
        }

        return false;
    }

    /**
     * Create commercial/Retail Customer
     *
     * @param  array $profileData
     * @return array|bool
     */
    public function createFclCustomer($profileData)
    {
        try {
            $userApprovelData = "";
            $companyId = $this->getCompanyId();
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $firstName = $profileData['address']['firstName'] ?? '';
            $lastName = $profileData['address']['lastName'] ?? '';
            $uuid = $profileData['address']['uuId'] ?? '';

            if (!empty($companyId)) { // For Commercial Customer
                $customer = $this->customerInterfaceFactory->create();
                $customerGroupId = $this->ssoHelper->getCompanyCustomerGroupId($companyId);
                if ($customerGroupId) {
                    $customer->setGroupId($customerGroupId);
                }
            } else {
                $customer = $this->customerFactory->create();
            }

            $customer->setWebsiteId($websiteId);
            $customer->setFirstname(ucfirst($firstName));
            $customer->setLastname(ucfirst($lastName));
            $uuidEmail = $uuid . "@fedex.com";
            $customer->setEmail($uuidEmail);
            $customer->setStoreId($this->storeManager->getStore()->getStoreId());

            if (!empty($companyId)) {
                $userApprovelData = $this->getCommercialFCLApprovalType();
                if (is_array($userApprovelData) && isset($userApprovelData['login_method'])
                    && ($userApprovelData['login_method'] == self::DOMAIN_APPROVAL
                    || $userApprovelData['login_method'] == self::AUTO_APPROVAL)
                ) {
                    $customer->setCustomAttribute('customer_status', 3);
                } else {
                    $customer->setCustomAttribute('customer_status', 1);
                }

                if (is_array($userApprovelData) && isset($userApprovelData['login_method'])
                    && $userApprovelData['login_method'] == "admin_approval"
                ) {
                    $customerExtensionAttributes = $customer->getExtensionAttributes();
                    /**
                     * @var CompanyCustomerInterface $companyCustomerAttributes
                     */
                    $companyCustomerAttributes = $customerExtensionAttributes->getCompanyAttributes();
                    if (!$companyCustomerAttributes) {
                        $companyCustomerAttributes = $this->compCustInterface->create();
                    }
                    $companyCustomerAttributes->setStatus(0);
                    $customer->setCustomAttribute('customer_status', 2);
                    $customerExtensionAttributes->setCompanyAttributes($companyCustomerAttributes);
                    $customer->setExtensionAttributes($customerExtensionAttributes);

                    $secondaryEmail = $profileData['address']['email'];
                    $firstName = $profileData['address']['firstName'];
                    $lastName = $profileData['address']['lastName'];
                    $fullName = $firstName . ' ' . $lastName;
                    $baseUrl = $this->getOndemandStoreUrl();
                    $this->selfRegHelper->sendEmailToAdmin($baseUrl, $fullName, $secondaryEmail, $companyId);
                }
                $customerId = $this->customerRepositoryInterface->save($customer)->getId();

                $this->companyManagement->assignCustomer($companyId, $customerId);

                $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->load($customerId);
            } else {
                $customer->save();
            }

            // For both commercial/retail customers
            $customer = $this->updateCustomerBasicInfo($customer, $profileData);
            $this->ssoHelper->saveAddress($customer, $profileData['address'], 'address');
            $this->logger->info(
                __METHOD__ . ':' . __LINE__
                . ':Customer FCL Type is created sucessfully.'
            );
            $responseData['customer'] = $customer;
            $responseData['uuidEmail'] = $uuidEmail;
            $responseData['companyId'] = $companyId;
            $responseData['uuid'] = $uuid;

            return $responseData;
        } catch (\Exception $e) {
            $this->customerSession->create()->unsFclFdxLogin();
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ':Customer is not created with error: ' . $e->getMessage()
            );
        }
        return false;
    }

    /**
     * Update cutomer basic information
     *
     * @param  object $customer
     * @param  array  $profileDetails
     * @return object
     */
    public function updateCustomerBasicInfo($customer, $profileDetails)
    {
        try {
            $customer->setFirstname($profileDetails['address']['firstName']);
            $customer->setLastname($profileDetails['address']['lastName']);
            $customer->setSecondaryEmail($profileDetails['address']['email']);
            $customer->setFclProfileContactNumber($profileDetails['address']['contactNumber']);
            if (strlen($profileDetails['address']['contactNumber']) < 10) {
                $profileDetails['address']['contactNumber'] = '1111111111';
            }
            $customer->setContactNumber($profileDetails['address']['contactNumber']);
            $customer->setContactExt($profileDetails['address']['ext']);
            $customer->setCustomerUuidValue($profileDetails['address']['uuId']);

            return $customer->save();
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ':Customer is not updated with error: ' . $e->getMessage()
            );
        }

        return $customer;
    }

    /**
     * Create customer account
     *
     * @param  string $loginType
     * @param  array  $profileData
     * @return int
     */
    public function createCustomerAccount($loginType, $profileData)
    {
        try {
            $responseData = "";
            if ($loginType == "sso" ||
            ($this->ssoHelper->getSSOWithFCLToggle() && $loginType == self::SSO_FCL)) {
                $responseData = $this->createSSOAccount($profileData);
            }
            if ($loginType == "fcl") {
                $responseData = $this->createFclCustomer($profileData);
            }
            if (is_array($responseData)) {
                $customer = $responseData['customer'];
                $companyId = $responseData['companyId'];
                $uuid = $responseData['uuid'];
                $customerId = $customer->getId();
                $existingCustomer = $this->customerFactory->create()->load($customerId);
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ .
                    ': Customer created successfully from globalize code: '
                );

                $this->setCustomerSession($loginType, $existingCustomer, $companyId, $uuid);

                return $customerId;
            }
        } catch (\Exception $e) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ': Error While creating a customer : ' . $e->getMessage()
            );
        }
    }

    /**
     * Set profile login error message
     */
    public function setProfileApprovalMessage()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSession->create();
        }
        $customerSession->setLoginErrorCode($this->errorCode);
        $customerSession->setSelfRegLoginError($this->profileApprovalMessage);
    }

    /**
     * Check customer login
     *
     * @param  int    $companyId
     * @param  object $customer
     * @return bool
     */
    public function checkIsValidLogin($companyId, $customer)
    {
        $validLogin = true;
        $validCustomerStatus = true;
        try {
            $commercialApproval = $this->getCommercialFCLApprovalType($companyId);
            $customerId = $customer->getId();
            $this->loginMethod = $commercialApproval['login_method'];
            /** B-2008404 Inactive Admin Error Messaging */
            if ($this->moduleConfig->isSgcInactiveErrorMessageEnabled()) {
                $isCustomerStatusActive = $this->isCustomerStatusActive($customer->getId());
                if (! $isCustomerStatusActive ) {
                $this->updateInactiveUserStatus(true);
                $this->profileApprovalMessage = "Your customer status has been marked inactive. Please contact your FedEx Administrator for assistance.";
                return false;
                }
            }

            if (
                !$this->punchoutHelper->isActiveCustomer($customer) &&
                isset($commercialApproval['login_method']) &&
                $commercialApproval['login_method'] != "admin_approval"
            ) {
                if ($this->isEmailVerificationPending($customerId)) {
                    $this->profileApprovalMessage = $commercialApproval['fcl_user_email_verification_user_display_message'] ?? "";
                } else {
                    /**
                     * B-1805640
                     */
                    $this->profileApprovalMessage = "The account sign-in was incorrect or your account is disabled temporarily.";
                    /**
                     * B-1805640
                     */
                }
                return false;
            }
            if (is_array($commercialApproval)
                && isset($commercialApproval['login_method'])
                && $commercialApproval['login_method'] == "admin_approval"
            ) {
                $validLogin = $this->punchoutHelper->isActiveCustomer($customer);
                $this->profileApprovalMessage = $commercialApproval['error_msg'] ?? "";
            } elseif (is_array($commercialApproval)
                && isset($commercialApproval['login_method'])
                && $commercialApproval['login_method'] == "domain_registration"
                && isset($commercialApproval['domains'])
            ) {
                $allowedDomains = $commercialApproval['domains'];
                $secondaryEmail = $customer->getSecondaryEmail();
                $validLogin = $this->selfRegHelper->validateDomain($secondaryEmail, $allowedDomains);
                $this->profileApprovalMessage = $this->determineProfileApprovalMessage($customerId, $commercialApproval);
            }
            if (isset($commercialApproval['login_method'])
                && $commercialApproval['login_method'] != self::ADMIN_APPROVAL
            ) {
                $customerStatus = $this->isCustomerStatusActive($customer->getId());
                $validCustomerStatus = $customerStatus ?? $validLogin;
                if (!$validCustomerStatus) {
                    $this->profileApprovalMessage =
                        $this->determineProfileApprovalMessage($customerId, $commercialApproval);
                }
            } else {
                $validCustomerStatus = $validLogin;
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ':Error while validating customer approval type: ' . $e->getMessage()
            );
        }

        return $validLogin && $validCustomerStatus;
    }

    /**
     * Set customer session
     *
     * @param string $loginType|$uuid
     * @param object $customer
     * @param bool   $isCompanyAdminUser
     * @param int    $companyId
     */
    public function setCustomerSession($loginType, $customer, $companyId, $uuid = "", $isCompanyAdminUser = false)
    {
        try {
            $validLogin = true;
            if ($companyId && !$isCompanyAdminUser) {
                // Check admin user
                $validLogin = $this->checkIsValidLogin($companyId, $customer);
            }

            if ($validLogin) {
                if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                    $customerSession = $this->getOrCreateCustomerSession();
                } else {
                    $customerSession = $this->customerSession->create();
                }
                $customerSession->unsSelfRegLoginError();
                $customerSession->unsLoginErrorCode();
                $customerSession->unsInvalidLoginCustomerId();
                $customerSession->unsLoginMethod();
                $customerId = $customer->getId();

                $customerSession->setCustomerAsLoggedIn($customer);

                $this->session->setLoginValidationKey((string)rand(10000, 50000));
                if ($companyId) {
                    $customerSession->setCustomerCompany($companyId);
                }
                if ($loginType == "sso" ||
                ($this->ssoHelper->getSSOWithFCLToggle() && $loginType == self::SSO_FCL)) {
                    $this->sdeHelper->setCustomerActiveSessionCookie();
                } else {
                    if (empty($companyId)) { // Only for retail
                        $this->ssoHelper->setFclMetaDataCookies();
                        $this->canvaCredentials->fetch();
                        $this->setCustomerCanva($loginType, $customerSession, $uuid, $customerId, $customer);
                    }
                }
            } else {
                if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                    $customerSession = $this->getOrCreateCustomerSession();
                } else {
                    $customerSession = $this->customerSession->create();
                }
                $customerSession->setInvalidLoginCustomerId($customer->getId());
                $customerSession->setLoginMethod($this->loginMethod);
                $this->setProfileApprovalMessage();
            }

            if ($this->toggleConfig->getToggleConfigValue('mazegeeks_user_preference_import_fields')) {
                $this->userPreferenceHelper->updateProfileResponse();
                $profileSession = $this->session->getProfileSession();
                if ($profileSession) {


                        $profile = $profileSession->output->profile;
                        if(property_exists($profile, "preferences")) {
                            $isProfileHasInvoiceNumber = $this->ssoHelper->isProfileHasInvoiceValid($profileSession->output->profile->preferences);
                            if ($isProfileHasInvoiceNumber) {
                                $newAccounts = $this->ssoHelper->getFedexAccounts($profileSession->output->profile);
                                if ($newAccounts) {
                                    $profileSession->output->profile->accounts = $newAccounts;
                                }
                                $this->session->setProfileSession($profileSession);
                            }
                        }

                }
            }

        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ':Error while creating customer session: ' . $e->getMessage()
            );
        }
    }

    /**
     * Set canva customer data
     *
     * @param string $loginType|$uuid
     * @param object $customerSession|$customer
     * @param int    $customerId
     */
    public function setCustomerCanva($loginType, $customerSession, $uuid, $customerId, $customer)
    {
        $this->ssoHelper->updateCustomerCanvaId($customerId, $uuid, $customer);

        if ($this->ssoHelper->isCanvaIdMigrationEnabled()
            && (!null == $customerSession->getcustomerCanvaId())
            && (!null == $customerSession->getUserProfileId())
        ) {
            $magentoCustomerCanvaId = $this->ssoHelper->getCustomerCanvaIdByUuid($uuid);
            if ((!null == $magentoCustomerCanvaId) && (                $customerSession->getcustomerCanvaId() != $magentoCustomerCanvaId)
            ) {
                $this->ssoHelper->setCanvaIdByProfileApi(
                    $this->endUrl, $this->cookieData,
                    $customerSession->getUserProfileId(), $magentoCustomerCanvaId
                );
                $customerSession->setCustomerCanvaId($magentoCustomerCanvaId);
                // update customer canvaid in pod 2.0 db to null
                $this->ssoHelper->setCustomerCanvaIdAfterMigration($uuid);
            } elseif ((!null == $magentoCustomerCanvaId) && (                $customerSession->getcustomerCanvaId() == $magentoCustomerCanvaId)
            ) {
                $this->ssoHelper->setCustomerCanvaIdAfterMigration($uuid);
            }
        }
    }

    /**
     * Login a customer in POD2.0 (retail and commercial both)
     *
     * @param string $loginType
     */
    public function doLogin($loginType, $uuidParam = false)
    {
        $companyId = $uuidEmail = $uuid = "";
        if ($uuidParam) {
            $profileData = $this->getProfileData($loginType, $uuidParam);
        } else {
            $profileData = $this->getProfileData($loginType);
        }

        if (is_array($profileData)) {
            if ($loginType == "sso" ||
            ($this->ssoHelper->getSSOWithFCLToggle() && $loginType == self::SSO_FCL)) {
                $uuidEmail = $this->customerHelper->getCustomerEmail($profileData);
                $profileData['address']['customerEmail'] = $uuidEmail;
            } else {
                $uuid = $profileData['address']['uuId'] ?? "";
                $uuidEmail = $uuid . "@fedex.com";
            }
            $existingCustomer = $this->customerHelper->getCustomerByUuid($uuidEmail);
            if (is_object($existingCustomer) && $existingCustomer->getId()) {
                $customerId = $existingCustomer->getId();
                $companyId = $this->getCompanyId($existingCustomer->getId());
                if ($companyId) {
                    $companyObj = $this->companyFactory->create()->load($companyId);
                    $urlExtension = $companyObj->getData('company_url_extention');
                    $this->setUrlExtensionCookie($urlExtension);
                }

                $isCompanyAdminUser = $this->isCompanyAdminUser($profileData, $loginType, $companyId, $uuidEmail, $existingCustomer);
                $this->setCustomerSession($loginType, $existingCustomer, $companyId, $uuid, $isCompanyAdminUser);
            } else {
                $ssoLoginFixToggle = $this->toggleConfig->getToggleConfigValue('explorers_sso_login_fix');
                if ($loginType == "fcl" || ($ssoLoginFixToggle && $loginType == "sso") ||
                ($this->ssoHelper->getSSOWithFCLToggle() && $loginType == self::SSO_FCL)) {
                    $profileEmail = $profileData['address']['email'] ?? "";
                    $existingCustomer = $this->customerHelper->getCustomerByEmail($profileEmail);

                    // seprate toggle
                    if ($this->toggleConfig->getToggleConfigValue('explorers_d196142_fix') && !empty($existingCustomer->getCustomerUuidValue()) && $existingCustomer->getCustomerUuidValue() != $uuid) {
                        $this->logger->info(
                            __METHOD__ . ':' . __LINE__ . ':Existing Retail user was found with email: ' . $profileEmail .
                            ' and uuid: '. $existingCustomer->getCustomerUuidValue() .' Profile UUID: ' . $uuid);
                        $existingCustomer = null;
                    }

                    if (is_object($existingCustomer) && $existingCustomer->getId()) {
                        $customerId = $existingCustomer->getId();
                        $companyId = $this->getCompanyId($customerId);

                        $requestCompanyId = $this->getCompanyId();
                        if (!isset($requestCompanyId) || ($companyId == $requestCompanyId)) {
                            if ($companyId) {
                                $companyObj = $this->companyFactory->create()->load($companyId);
                                $urlExtension = $companyObj->getData('company_url_extention');
                                $this->setUrlExtensionCookie($urlExtension);
                            }
                            $isCompanyAdminUser = $this->isCompanyAdminUser($profileData, $loginType, $companyId, $uuidEmail, $existingCustomer);
                            $this->setCustomerSession($loginType, $existingCustomer, $companyId, $uuid, $isCompanyAdminUser);
                        } else {
                            $customerId = $this->createCustomerAccount($loginType, $profileData);
                        }
                    } else {
                        $customerId = $this->createCustomerAccount($loginType, $profileData);
                    }
                } else {
                    // To be removed with explorers_sso_login_fix toggle
                    $customerId = $this->createCustomerAccount($loginType, $profileData);
                }
            }
            // Update External Identifier
            if (is_object($existingCustomer) && !$existingCustomer->getIsIdentifierExist()) {
                $secondaryEmail = $profileData['address']['email'] ?? "";
                $this->customerHelper->updateExternalIdentifier($uuidEmail, $customerId, $secondaryEmail, $existingCustomer);

            }
        } else {
            $storeCode = $this->getStoreCode();
            if ($storeCode == 'default') {
                $this->errorCode = 'retail_login_error';
            } else {
                /**
                 * B-1805640
                 */
                $this->profileApprovalMessage = "profile_error";
                /**
                 * B-1805640
                 */
                $this->errorCode = 'ondemand_login_error';
            }
            $this->setProfileApprovalMessage();
        }

        if (
            $this->toggleConfig->getToggleConfigValue('techtitans_d_196604')
        ) {
            if ($this->ssoHelper->getFCLCookieNameToggle()) {
                $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
            } else {
                $cookieName = 'fdx_login';
            }
            $fdxLogin = $this->cookieManager->getCookie($cookieName);
            if ($fdxLogin) {
                $customerSession = $this->customerSession->create();
                $customerSession->setFdxLogin(
                    $fdxLogin
                );
            }
        }

    }

    /**
     * Get Company Id
     *
     * @param  int $companyId
     * @return int
     */
    public function getCompanyId($customerId = null)
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $companyId = false;
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSession->create();
        }
        if (!$customerId && $this->authHelper->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
        }
        if ($customerId) {
            $companyData = $this->companyManagement->getByCustomerId($customerId);
            if ($companyData) {
                $companyId = $companyData->getId();
            }
        } else {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $companyData = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
            } else {
                $companyData = $this->customerSession->create()->getOndemandCompanyInfo();
            }

            if (is_array($companyData) && !empty($companyData)) {
                $companyId = $companyData['company_id'] ?? "";
            }
        }

        $return = $companyId;
        return $return;
    }

    /**
     * Handle customer session
     *
     * @return array|bool
     */
    public function handleCustomerSession($uuid = false)
    {
        try {

            if ($uuid) {
                $loginType = $this->getLoginType();
                $this->doLogin($loginType, $uuid);
                if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                    $customerSessionObj = $this->getOrCreateCustomerSession();
                } else {
                    $customerSessionObj = $this->customerSession->create();
                }

            } else {
                $ssoLoginCookie = $this->cookieManager->getCookie(SSOHelper::SDE_COOKIE_NAME);
                $forgeRockCookie = $this->forgeRock->getCookie();
                // @codeCoverageIgnoreStart
                if ($forgeRockCookie) {
                    $ssoLoginCookie = $forgeRockCookie;
                }
                // @codeCoverageIgnoreEnd
                if ($this->ssoHelper->getFCLCookieNameToggle()) {
                    $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                    $fclLoginCookie = $this->cookieManager->getCookie($cookieName);
                } else {
                    $fclLoginCookie = $this->cookieManager->getCookie('fdx_login');
                }

                if ((!$fclLoginCookie || $fclLoginCookie == 'no') && !$ssoLoginCookie) {
                    return [];
                }
                // Check Login Type
                if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                    $customerSessionObj = $this->getOrCreateCustomerSession();
                } else {
                    $customerSessionObj = $this->customerSession->create();
                }

                if ($this->authHelper->isLoggedIn()) {
                    $customerSessionObj->unsSelfRegLoginError();
                    $returnData['status'] = 'success';
                    $returnData['code'] = null;
                    $returnData['msg'] = 'Customer Login Success';
                    return $returnData;
                }
                $loginType = $this->getLoginType();
                $this->doLogin($loginType);
            }

            $returnData = [];
            if ($customerSessionObj->getSelfRegLoginError() || $customerSessionObj->getLoginErrorCode()) {
                $returnData['status'] = 'error';
                $returnData['code'] = $customerSessionObj->getLoginErrorCode();
                $returnData['msg'] = $customerSessionObj->getSelfRegLoginError();
            } else {
                $customerSessionObj->unsSelfRegLoginError();
                $returnData['status'] = 'success';
                $returnData['code'] = null;
                $returnData['msg'] = 'Customer Login Success';
            }
        } catch (\Exception $e) {
            $returnData['status'] = 'error';
            $returnData['msg'] = $e->getMessage();
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__
                . ': An Error has occured while handling customer session: ' . $e->getMessage()
            );
        }

        return $returnData;
    }

    public function setUuidCookie($uuid)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath("/")
            ->setHttpOnly(true)
            ->setDuration(time() + 86400)
            ->setSecure(true)
            ->setSameSite("None");
        $this->cookieManager->setPublicCookie(
            'fcl_uuid',
            $uuid,
            $publicCookieMetadata
        );
    }

    /**
     * Get store redirect URL based on user login
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSession->create();
        }

        $redirectUrl = "";
        if ($this->authHelper->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $companyId = $this->getCompanyId($customerId);
            if ($companyId) {
                $redirectUrl = $this->getOndemandStoreUrl();
                $companyData = $this->getOndemandCompanyData($companyId);
                $customerSession->setOndemandCompanyInfo($companyData);
                $customerSession->setCustomerCompany($companyId);
            } else {
                $customerSession->unsOndemandCompanyInfo();
                $customerSession->unsCustomerCompany();
                $redirectUrl = $this->getRetailStoreUrl();
            }
        }

        return $redirectUrl;
    }

    /**
     * Get Retail Store URL.
     *
     * @return string
     */
    public function getRetailStoreUrl()
    {
        $store = $this->storeFactory->create();
        return $store->load('default', 'code')->getUrl();
    }

    /**
     * Get OnDemand Store Url
     *
     * @return string
     */
    public function getOndemandStoreUrl()
    {
        $store = $this->storeFactory->create();

        return $store->load('ondemand', 'code')->getUrl();
    }

    /**
     * Get company data
     *
     * @param  Int $companyId
     * @return array|bool
     */
    public function getOndemandCompanyData($companyId)
    {
        if ($companyId) {
            $companyData = $this->companyFactory->create()->load($companyId);
            if ($companyData && isset($companyData['entity_id'])) {
                $companyId = $companyData['entity_id'];
                $returnData['company_id'] = $companyId;
                $returnData['company_data'] = $companyData;
                $returnData['ondemand_url'] = true;
                $returnData['url_extension'] = true;
                if ($companyData['is_sensitive_data_enabled']) {
                    $returnData['company_type'] = 'sde';
                } elseif ($companyData['storefront_login_method_option'] == 'commercial_store_epro') {
                    $returnData['company_type'] = 'epro';
                } else {
                    $returnData['company_type'] = 'selfreg';
                }
            } else {
                $returnData = ['ondemand_url' => true, 'url_extension' => false];
            }
            return $returnData;
        }

        return false;
    }

    /**
     * Check User Login Type
     *
     * @return string
     */
    public function getLoginType(): string
    {
        if ($this->authHelper->isLoggedIn()) {
            return $this->authHelper->getCompanyAuthenticationMethod();
        } else {
            $loginType = "fcl";
            if ($this->toggleConfig->getToggleConfigValue('techtitans_d_193751')) {
                $idTokenCookie = $this->cookieManager->getCookie(self::ID_TOKEN);
                $idTokenRequest = $this->_request->getParam(self::ID_TOKEN);

                if ($idTokenCookie || $idTokenRequest) {
                    $loginType = "sso";
                }
            }
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $companyData = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
            } else {
                $companyData = $this->customerSession->create()->getOndemandCompanyInfo();
            }
            if (!empty($companyData) && is_array($companyData)) {
                $loginMethod = ($companyData['company_data']['storefront_login_method_option'] != null) ?
                    $companyData['company_data']['storefront_login_method_option'] : '';
                if ($loginMethod == 'commercial_store_sso') {
                        $loginType = "sso";
                } elseif ($this->ssoHelper->getSSOWithFCLToggle() && $loginMethod == 'commercial_store_sso_with_fcl') {
                    $loginType = self::SSO_FCL;
                }
            }

            return $loginType;
        }
    }

    /**
     * Get Store Code.
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    public function setUrlExtensionCookie($urlExtension)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath("/")
            ->setHttpOnly(true)
            ->setDuration(time() + 86400)
            ->setSecure(true)
            ->setSameSite("None");
        $this->cookieManager->setPublicCookie(
            'url_extension',
            $urlExtension,
            $publicCookieMetadata
        );
    }

    /**
     * Get Url Extension from Cookie
     */
    public function getUrlExtensionCookie()
    {
        return $this->cookieManager->getCookie(
            'url_extension'
        );
    }

    /**
     * Check if customer email should be verified in order to login
     *
     * @return bool
     */
    public function isEmailVerificationRequired()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSession->create();
        }
        $customerId = $customerSession->getInvalidLoginCustomerId();
        $loginMethod = $customerSession->getLoginMethod();
        $loginType = $this->getLoginType();
        $customerStatus = null;
        if ($customerId) {
            $customerStatus = $this->customerHelper->getCustomerStatus($customerId);
        }
        $verifyEmail = false;

        if ($loginType == LoginType::FCL->value && $customerStatus
            && $customerStatus == CustomerStatus::EMAIL_VERIFICATION_PENDING->value
            && $this->moduleConfig->isConfirmationEmailRequired()
            && $loginMethod && ($loginMethod == self::AUTO_APPROVAL
            || $loginMethod == self::DOMAIN_APPROVAL)
        ) {
            $verifyEmail = true;
        }

        return $verifyEmail;
    }

    /**
     * Send user verification email
     *
     * @return null
     */
    public function sendUserVerificationEmail()
    {
        try {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customerSession = $this->getOrCreateCustomerSession();
            } else {
                $customerSession = $this->customerSession->create();
            }
            $customerId = $customerSession->getInvalidLoginCustomerId();
            $customer = $this->customerFactory->create()->load($customerId);
            if ($customer) {
                $customerEmail = $customer->getSecondaryEmail() ?
                    $customer->getSecondaryEmail() : $customer->getEmail();
                $customerName = $customer->getName();
            } else {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ .
                    ' Unable to send verification email because of invalid customer.'
                );

                return false;
            }

            $customerEmailUuid = $this->emailVerification->generateCustomerEmailUuid($customerId);
            $emailVerificationLink = $this->emailVerification->getEmailVerificationLink($customerEmailUuid);
            if ($emailVerificationLink) {
                $emailData = [
                    'customer_name' => $customerName,
                    'link_expiration_time' => $this->moduleConfig->getLinkExpirationTime(),
                    'email_subject' => $this->moduleConfig->getVerificationEmailSubject(),
                    'email_link' => $emailVerificationLink
                ];
            } else {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ .
                    ' Unable to send verification email because of invalid email verification link.'
                );

                return false;
            }

            $fclUserVerificationEmailTemplateId = $this->moduleConfig->getEmailVerificationTemplate();
            $fclUserVerificationEmailTemplateContent = $this->sendEmail
                ->loadEmailTemplate($fclUserVerificationEmailTemplateId, 0, $emailData);

            $genericEmailData["toEmailId"] = $customerEmail;
            $genericEmailData["fromEmailId"] = $this->moduleConfig->getVerificationEmailFrom();
            $genericEmailData["templateSubject"] = $this->moduleConfig->getVerificationEmailSubject();
            $genericEmailData["templateData"] = " $fclUserVerificationEmailTemplateContent ";
            $genericEmailData["attachment"] = "";

            $sendEmailResult = $this->sendEmail->sendEmail($genericEmailData);

            if ($sendEmailResult) {
                $this->emailVerification->updateEmailVerificationCustomer($customerId, $customerEmailUuid);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Verification email successfully sent to customer.');
            } else {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ .
                    ' Error in sending verification email to customer.'
                );
            }

            return $sendEmailResult;
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .
                ' Generic email send error ', ['exception' => $e->getMessage()]
            );
        }

        return false;
    }

    /**
     * Check if customer status is active
     *
     * @param  int $customerId
     * @return bool|null
     */
    public function isCustomerStatusActive($customerId)
    {
        $customerStatus = $this->customerHelper->getCustomerStatus($customerId);
        $isActive = null;

        if ($customerStatus && $customerStatus == 'Active') {
            $isActive = true;
        } elseif ($customerStatus) {
            $isActive = false;
        }

        return $isActive;
    }

    /**
     * Check if customer email verification is pending
     *
     * @param  int $customerId
     * @return bool|null
     */
    public function isEmailVerificationPending($customerId)
    {
        $customerStatus = $this->customerHelper->getCustomerStatus($customerId);
        $isEmailVerificationPending = null;

        if ($customerStatus && $customerStatus == 'Email Verification Pending') {
            $isEmailVerificationPending = true;
        } elseif ($customerStatus) {
            $isEmailVerificationPending = false;
        }

        return $isEmailVerificationPending;
    }

    /**
     * Determine the profile approval message based on email verification status
     *
     * @param  int   $customerId
     * @param  array $commercialApproval
     * @return string
     */
    public function determineProfileApprovalMessage($customerId, $commercialApproval)
    {
        $customerStatus = $this->customerHelper->getCustomerStatus($customerId);
        if ($this->isEmailVerificationPending($customerId)) {
            return $commercialApproval['fcl_user_email_verification_user_display_message'] ?? "";
        } else if ($customerStatus && ($customerStatus == 'Inactive' || $customerStatus == 'Pending For Approval')) {
            return __('The account temporarily '.$customerStatus. ' please contact to administrator.');
        } else {
            return $commercialApproval['error_msg'] ?? "";
        }
    }
    /**
     * Check if Wire Mock Enable
     */
    public function isWireMockLoginEnable()
    {
        return $this->ssoConfig->isWireMockLoginEnable();
    }

    /**
     * Get Wire Lock Profile Login Url
     */
    public function getWireMockProfileUrl()
    {
        return $this->ssoConfig->getWireMockProfileUrl();
    }

    /**
     * Get Logging Toggle Value
     * once toggle gets removed no need of this function
     */
    public function isLoggingToggleEnable()
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_cart_items_logging');
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     *
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     *
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->authHelper->isLoggedIn()) {
            $this->session = $this->customerSession->create();
        }
        return $this->session;
    }

    /**
     * Set inactive user status in session
     */
    public function updateInactiveUserStatus($status)
    {
        $customerSession = $this->customerSession->create();
        $customerSession->setInactiveUserStatus($status);
    }

    /**
     * Get fuse bid quote url.
     *
     * @param string $redirectUrl
     * @return string
     */
    public function getFuseBidQuoteUrl($redirectUrl)
    {
        $urlParam = parse_url($redirectUrl);
        if (isset($urlParam['query'])) {
            parse_str($urlParam['query'], $data);
            if (!empty($data['bidquote'])) {
                if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                    $customerSession = $this->getOrCreateCustomerSession();
                } else {
                    $customerSession = $this->customerSession->create();
                }
                $customerId = $customerSession->getId();
                if ($customerId) {
                    $retailStoreUrl = $this->getRetailStoreUrl();
                    $customer = $this->customerFactory->create()->load($customerId);
                    $redirectUrl = $this->fuseBidViewModel->validateCustomerQuote(
                        $customer,
                        $data['bidquote']
                    );

                    return $retailStoreUrl.$redirectUrl;
                }
            }
        }

        return $redirectUrl;
    }
}
