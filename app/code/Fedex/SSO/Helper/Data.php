<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Helper;

use Exception;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Model\ProfileManagement;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Fedex\MarketplaceRates\Helper\Data As MarketplaceHelperData;
use Fedex\SSO\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\Company\Helper\UserPreferenceHelper;

/**
 * Data Helper class
 */
class Data extends AbstractHelper
{
    public const COOKIEDESTROYDOMAIN = '.fedex.com';
    public const CONFIG_BASE_PATH = 'sso/general/';
    public const FCL_COOKIE_CONFIG_FIELD = 'fcl_cookie_name';

    /**
     * SDE SSO cookie name
     */
    const SDE_COOKIE_NAME = 'SMDEFAULT';

    /**
     * Forge Rock SSO cookie name
     */
    const FORGE_ROCK_COOKIE_NAME = 'id_token';

    /**
     * Configuration path to enable/disable CanvaId Migration
     */
    const XML_PATH_ENABLE_CanvaId_Migration = 'sgc_b1324662_migrate_canvaid_to_retail_profile';
    private ToggleConfig $_toggleConfig;

    /**
     * Data Helper constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param Customer $customerModel
     * @param Session $customerSession
     * @param Collection $customerCollection
     * @param Curl $curl
     * @param AddressFactory $addressDataFactory
     * @param RegionFactory $regionFactory
     * @param PunchoutHelper $punchoutHelper
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ToggleConfig $toggleConfig
     * @param SdeHelper $sdeHelper
     * @param AdditionalDataFactory $additionalDataFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CookieManagerInterface $cookieManager
     * @param ResourceConnection $resourceConnection
     * @param AttributeRepositoryInterface $attributeRepositoryInterface
     * @param ConfigInterface $configInterface
     * @param HeaderData $headerData
     * @param AuthHelper $authHelper
     * @param MarketplaceHelperData $purpleGatewayToken
     * @param Config $ssoConfig
     */
    public function __construct(
        Context $context,
        protected StoreManagerInterface $storeManager,
        protected LoggerInterface $logger,
        protected CustomerFactory $customerFactory,
        protected CustomerRepositoryInterface $customerRepositoryInterface,
        protected Customer $customerModel,
        protected Session $customerSession,
        protected Collection $customerCollection,
        protected Curl $curl,
        private AddressFactory $addressDataFactory,
        private RegionFactory $regionFactory,
        private PunchoutHelper $punchoutHelper,
        private CookieMetadataFactory $cookieMetadataFactory,
        protected ToggleConfig $toggleConfig,
        protected SdeHelper $sdeHelper,
        protected AdditionalDataFactory $additionalDataFactory,
        protected CompanyRepositoryInterface $companyRepository,
        protected CompanyManagementInterface $companyManagement,
        protected CustomerInterfaceFactory $customerInterfaceFactory,
        protected CookieManagerInterface $cookieManager,
        private ResourceConnection $resourceConnection,
        private AttributeRepositoryInterface $attributeRepositoryInterface,
        private ConfigInterface $configInterface,
        protected HeaderData $headerData,
        protected AuthHelper $authHelper,
        private MarketplaceHelperData $purpleGatewayToken,
        private Config $ssoConfig,
        protected UserPreferenceHelper $userPreferenceHelper
    ) {
        parent::__construct($context);
        $this->purpleGatewayToken = $purpleGatewayToken;
        $this->ssoConfig = $ssoConfig;
    }

    /**
     * Get store base url
     *
     * @return  string
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get customer data from profile response and create customer
     * B-1325041 : SDE SSO refactor
     *
     * @param string $endUrl
     * @param string $loginCookieValue
     * @return bool
     */
    public function getCustomerProfile($endUrl, $loginCookieValue)
    {
        try {
            $isSdeStore = $this->sdeHelper->getIsSdeStore() && !$this->sdeHelper->getIsRequestFromSdeStoreFclLogin();
            $currentCustomer = $this->customerSession->getCustomer();

            // Get customer profile using API
            $profileDetails = $this->getProfileByProfileApi($endUrl, $loginCookieValue);

            // Return 401 status from profile api response
            if ($profileDetails === 401) {
                return $profileDetails;
            }

            $isSSOlogin = $this->isSSOlogin();
            if ($isSSOlogin) {
                return $this->getSSOLoginCustomer($profileDetails, $isSSOlogin);
            }

            $companyId = $customerId = 0;
            $uuidEmail = '';
            $customer = null;
            $newCustomer = false;
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $firstName = $profileDetails['address']['firstName'] ?? '';
            $lastName = $profileDetails['address']['lastName'] ?? '';
            $email = $profileDetails['address']['email'] ?? '';
            $uuid = $profileDetails['address']['uuId'] ?? '';
            $contactNumber = $profileDetails['address']['contactNumber'] ?? '';
            $contacExt = $profileDetails['address']['ext'] ?? '';
            $customerEmail = $email;
            if ($websiteId && !empty($firstName) && !empty($lastName) && !empty($email)) {
                if (!$isSdeStore && !empty($uuid)) {
                    $uuidEmail = (string) $uuid . "@fedex.com";
                    $customerId = $this->getCustomerIdByUuid($uuid);
                    if ($currentCustomer && $currentCustomer->getId()) {
                        $customerId = $currentCustomer->getId() != $customerId?$currentCustomer->getId():$customerId;
                    }
                } elseif ($isSdeStore) {
                    if ($customer = $this->getCustomerByEmail($customerEmail, $websiteId)) {
                        $customerId = $customer->getId();
                    }
                } else {
                    throw new Exception(__('Profile API issue.'));
                }

                if (!$isSdeStore) {
                    if ($currentCustomer && $currentCustomer->getId()) {
                        $customerEmail = $currentCustomer->getEmail() != $uuidEmail
                            ? $currentCustomer->getEmail()
                            : $uuidEmail;
                    } else {

                        $customerEmail = $uuidEmail;
                    }
                }

                if (!$customerId) {
                    $newCustomer = true;
                }

                // Create customer object
                // We are using separate methods for SDE and other stores because for SDE company is
                // not being saved for customer when factory method is used and for other stores
                // email is not saved correctly when interface is used.
                //
                // @todo Find reason for why email is not being saved properly when interface is used
                // then remove save() method and use repository for all
                if ($isSdeStore) {
                    if (!$customerId) {
                        $customer = $this->customerInterfaceFactory->create();
                    }
                } else {
                    // D-103864
                    if (!$customerId) {
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Customer Id is null' . $email);
                        $customer = $this->customerFactory->create();
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Customer created successfully' . $email);
                    } else {
                        $customer = $this->customerFactory->create()->load($customerId);

                    }
                }

                // Load customer
                if ($customerId && !$isSdeStore) {
                    $customer->setWebsiteId($websiteId)->load($customerId);
                }

                $customer->setWebsiteId($websiteId);
                $customer->setFirstname(ucfirst($firstName));
                $customer->setLastname(ucfirst($lastName));
                $customer->setEmail($customerEmail);

                $this->updateCustomerCanvaId($customerId, $uuid, $customer);

                // Set FCL profile contact number
                if (!$isSdeStore) {
                    $customer->setFclProfileContactNumber($contactNumber);
                    if (strlen($contactNumber) < 10) {
                        $contactNumber = '1111111111';
                    }
                }

                // Set other profile details for FCL customer
                if (!$isSdeStore) {
                    $customer->setSecondaryEmail($email);
                    $customer->setContactNumber($contactNumber);
                    $customer->setContactExt($contacExt);
                    $customer->setCustomerUuidValue($uuid);
                }

                // Assign customer group to SDE customer
                if ($isSdeStore) {
                    $companyId = $this->getCustomerCompanyIdByStore();
                    $customerGroupId = $this->getCompanyCustomerGroupId($companyId);
                    $customer->setStoreId($this->storeManager->getStore()->getStoreId());
                    if ($customerGroupId) {
                        $customer->setGroupId($customerGroupId);
                    }
                }

                // Save customer
                // @todo Find reason for why email is not being saved properly when interface is used
                // then remove save() method and use repository for all
                if ($isSdeStore) {
                    $customerId = $this->customerRepositoryInterface->save($customer)->getId();
                } else {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Before customer save: ' . $email. ':' .$uuid);
                    $customer->save();
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ':After customer save: ' . $email. ':' .$uuid);
                }

                // Assign company to SDE customer
                if ($isSdeStore && $companyId && $newCustomer) {
                    // Assign company
                    $this->companyManagement->assignCustomer($companyId, $customerId);
                }

                // Save customer address
                if (!$isSdeStore) {
                    $customer = $this->getCustomerByEmail($customerEmail, $websiteId);
                    $this->saveAddress($customer, $profileDetails['address'], 'address');
                }

                $customerInfo = $this->customerModel->setWebsiteId($websiteId)->loadByEmail($customerEmail);

                // Login customer
                $this->customerSession->setCustomerAsLoggedIn($customerInfo);
                if($this->isCanvaIdMigrationEnabled()) {
                    if((!null == $this->customerSession->getcustomerCanvaId()) &&
                    (!null == $this->customerSession->getUserProfileId())) {
                        $magentoCustomerCanvaId = $this->getCustomerCanvaIdByUuid($uuid);
                        if((!null == $magentoCustomerCanvaId) && (
                            $this->customerSession->getcustomerCanvaId() != $magentoCustomerCanvaId)) {
                            $this->setCanvaIdByProfileApi($endUrl, $loginCookieValue,
                            $this->customerSession->getUserProfileId(), $magentoCustomerCanvaId);
                            $this->customerSession->setCustomerCanvaId($magentoCustomerCanvaId);
                            // update customer canvaid in pod 2.0 db to null
                            $this->setCustomerCanvaIdAfterMigration($uuid);
                        } else if((!null == $magentoCustomerCanvaId) && (
                            $this->customerSession->getcustomerCanvaId() == $magentoCustomerCanvaId)) {
                            $this->setCustomerCanvaIdAfterMigration($uuid);
                        }
                    }
                }
                // Set customer company id in session
                if ($isSdeStore && $companyId) {
                    $this->customerSession->setCustomerCompany($companyId);
                }
                if ($this->authHelper->isLoggedIn()) {
                    if($isSdeStore) {
                        $this->sdeHelper->setCustomerActiveSessionCookie();
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->customerSession->unsFclFdxLogin();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Profile API Issue');
            }
        } catch (Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $uuId = $profileDetails['address']['uuId'] ?? '';
            $email = $profileDetails['address']['email'] ?? '';
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .':Customer is not created with UUID : '
            . $uuId . ' and Email : ' . $email . ' and error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Update customer canva id
     *
     * @param   int $customerId
     * @param   string $uuid
     * @param   object $customer
     * @return  void
     */
    public function updateCustomerCanvaId($customerId, $uuid, $customer)
    {
        $customerSessionCanvaId = $this->customerSession->getCustomerCanvaId();

        if (!$customerId) {
            if ($customerSessionCanvaId) {
                $customer->setCustomerCanvaId($customerSessionCanvaId);
            } else {
                $canvaId = $this->generateUniqueCanvaId();
                $customer->setCustomerCanvaId($canvaId);
            }
        } else {
            $dbCustomerCanvaId = $this->getCustomerCanvaIdByUuid($uuid);
            if ($dbCustomerCanvaId != $customerSessionCanvaId) {
                $customer->setCustomerCanvaId($customerSessionCanvaId);
            }
        }
    }

    /**
     * getSSOLoginCustomer
     * @param  array $profileDetails
     * @param  boolean $isSSOlogin
     * @return boolean
     */
    public function getSSOLoginCustomer($profileDetails,$isSSOlogin)
    {
        try{
            $companyId = $customerId = 0;
            $customer = null;
            $newCustomer = false;
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $firstName = $profileDetails['address']['firstName'] ?? '';
            $lastName = $profileDetails['address']['lastName'] ?? '';
            $email = $profileDetails['address']['email'] ?? '';
            $uuid = $profileDetails['address']['uuId'] ?? '';
            $customerEmail = $email;

            if ($websiteId && !empty($firstName) && !empty($lastName) && !empty($email)) {
                if ($customer = $this->getCustomerByEmail($customerEmail, $websiteId)) {
                    $customerId = $customer->getId();
                }

                $companyId = $this->getCustomerCompanyIdByStore();

                if (!$customerId) {
                    $newCustomer = true;
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Customer Id is null' . $email);
                    $customer = $this->customerInterfaceFactory->create();
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Customer created successfully' . $email);
                    $customer->setWebsiteId($websiteId);
                    $customer->setFirstname(ucfirst($firstName));
                    $customer->setLastname(ucfirst($lastName));
                    $customer->setEmail($customerEmail);
                    $customerGroupId = $this->getCompanyCustomerGroupId($companyId);
                    $customer->setStoreId($this->storeManager->getStore()->getStoreId());
                    if ($customerGroupId) {
                        $customer->setGroupId($customerGroupId);
                    }
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Before customer save: ' . $email. ':' .$uuid);
                    $customerId = $this->customerRepositoryInterface->save($customer)->getId();
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ':After customer save: ' . $email. ':' .$uuid);

                    // Assign company
                    if ($companyId && $newCustomer) {
                        $this->companyManagement->assignCustomer($companyId, $customerId);
                    }
                }
                $customerInfo = $this->customerModel->setWebsiteId($websiteId)->loadByEmail($customerEmail);

                // Login customer
                $this->customerSession->setCustomerAsLoggedIn($customerInfo);

                // Set customer company id in session
                if ($companyId) {
                    $this->customerSession->setCustomerCompany($companyId);
                }
                if ($this->authHelper->isLoggedIn()) {
                    $this->sdeHelper->setCustomerActiveSessionCookie();
                    return true;
                } else {
                    return false;
                }

            } else {
                $this->customerSession->unsFclFdxLogin();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Profile API Issue');
            }
        } catch (Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $this->logger->critical(__METHOD__ . ':' . __LINE__
                . ':Customer is not created with error: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Get data from profile response and create customer
     *
     * @param   string $endUrl
     * @param   string $fdxLogin
     * @return  boolean true|false
     */
    public function getFCLProfile($endUrl, $fdxLogin)
    {
        try {
            if (!$this->customerSession->getFclFdxLogin()) {
                $profileDetails = $this->getProfileByProfileApi($endUrl, $fdxLogin);
                /*Return 401 status from profile api response*/
                if ($profileDetails === 401) {
                    return $profileDetails;
                }
                $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                if (!empty($profileDetails['address']['firstName']) && !empty($profileDetails['address']['lastName']
                    && !empty($profileDetails['address']['uuId']))
                    && !empty($profileDetails['address']['email']) && !empty($websiteId)) {
                    $fclUuid = $profileDetails['address']['uuId'];
                    $fclUuidEmail = (string) $fclUuid . "@fedex.com";
                    $customerId = $this->getCustomerIdByUuid($fclUuid);

                    $customer = $this->customerFactory->create();
                    // It will update existing customer data
                    if ($customerId) {
                        $customer->setWebsiteId($websiteId)->load($customerId);
                    }
                    $customer->setWebsiteId($websiteId);
                    $customer->setFirstname($profileDetails['address']['firstName']);
                    $customer->setLastname($profileDetails['address']['lastName']);

                    if (!$customerId) {
                        $canvaId = $this->generateUniqueCanvaId();
                        $customer->setCustomerCanvaId($canvaId);
                        $customer->setEmail($fclUuidEmail);
                    }
                    $customer->setSecondaryEmail($profileDetails['address']['email']);

                    $customer->setFclProfileContactNumber($profileDetails['address']['contactNumber']);
                    if (strlen($profileDetails['address']['contactNumber']) < 10) {
                        $profileDetails['address']['contactNumber'] = '1111111111';
                    }

                    $customer->setContactNumber($profileDetails['address']['contactNumber']);
                    $customer->setContactExt($profileDetails['address']['ext']);
                    $customer->setCustomerUuidValue($fclUuid);
                    $customer->save();
                    $customer = $this->customerRepositoryInterface
                        ->get($fclUuidEmail, $websiteId);
                    $this->saveAddress($customer, $profileDetails['address'], 'address');

                    $customerInfo = $this->customerModel
                        ->setWebsiteId($websiteId)
                        ->loadByEmail($fclUuidEmail);
                    $this->customerSession->setCustomerAsLoggedIn($customerInfo);
                    if ($this->authHelper->isLoggedIn()) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    $this->customerSession->unsFclFdxLogin();
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Profile API Issue');
                }
            } else {
                return true;
            }
        } catch (Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $uuId = '';
            if (isset($profileDetails['address']['uuId'])) {
                $uuId = $profileDetails['address']['uuId'];
            }
            $email = '';
            if (isset($profileDetails['address']['email'])) {
                $email = $profileDetails['address']['email'];
            }
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ':Customer is not created with UUID : ' . $uuId . ' and Email : '
            . $email . ' and error: ' . $e->getMessage());

        }

        return false;
    }

    /**
     * getTokenData
     *
     * @param  array $companyData
     * @param  string $loginCookieValue
     * @param  boolean $isSdeStore
     * @return array
     */
    public function getTokenData($companyData, $loginCookieValue, $isSdeStore)
    {
        $idTokenCookie = $this->cookieManager->getCookie(self::FORGE_ROCK_COOKIE_NAME);
        $idTokenRequest = $this->_request->getParam(self::FORGE_ROCK_COOKIE_NAME);
        $techtitans_d_193751 = $this->toggleConfig->getToggleConfigValue('techtitans_d_193751');

        $tokenData = [];
        if ($this->isSSOlogin() || ($techtitans_d_193751 && ($idTokenCookie || $idTokenRequest))) {
            $tokenData['gatewayToken'] = $this->punchoutHelper->getAuthGatewayToken();
            $tokenData['environmentCookieName'] = self::SDE_COOKIE_NAME;
            $idTokenCookie = $this->cookieManager->getCookie(self::FORGE_ROCK_COOKIE_NAME);
            $idTokenRequest = $this->_request->getParam(self::FORGE_ROCK_COOKIE_NAME);
            /**
             * @todo check why id_token is not set in the cookie via PHP
             */
            if (empty($idTokenCookie) && !empty($idTokenRequest)) {
                $idTokenCookie = $idTokenRequest;
            }

            if ($idTokenCookie) {
                $tokenData['environmentCookieName'] = self::FORGE_ROCK_COOKIE_NAME;
            }
            $tokenData['dataString'] = '';
        } else {
            $tokenData['gatewayToken'] = $this->punchoutHelper->getAuthGatewayToken();
            $tokenData['dataString'] = json_encode($loginCookieValue);
            $this->customerSession->setFclFdxLogin($loginCookieValue);
            $tokenData['environmentCookieName'] = $this->getFCLCookieNameToggle() ?
            $this->getFCLCookieConfigValue() : ProfileManagement::LOGIN_COOKIE_NAME;
        }


        return $tokenData;
    }

    /**
     * Get customer's profile details by api
     *
     * @param string $endUrl
     * @param string $loginCookieValue
     * @return array
     */
    public function getProfileByProfileApi(
        $endUrl,
        $loginCookieValue,
        $uuid = false,
        $loginType = ''
    ){
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__;
        try {
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDomain(".fedex.com")
                ->setPath("/")
                ->setHttpOnly(false)
                ->setSecure(true)
                ->setSameSite("None");

            $tazToken = $this->punchoutHelper->getTazToken();

            // B-1325041 : SDE SSO refactor

            $companyData = $this->customerSession->getOndemandCompanyInfo();
            $isSdeStore = $this->sdeHelper->getIsSdeStore() && !$this->sdeHelper->getIsRequestFromSdeStoreFclLogin();


            $tokenData = $this->getTokenData($companyData, $loginCookieValue, $isSdeStore);
            $gatewayToken = $tokenData['gatewayToken'];
            $environmentCookieName = $tokenData['environmentCookieName'];
            $dataString = $tokenData['dataString'];

            $authHeaderVal = $this->headerData->getAuthHeaderValue();

            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "X-clientid: ISHP",
                $authHeaderVal . $gatewayToken,
                "Cookie: Bearer=" . $tazToken,
                "Cookie: " . $environmentCookieName . "=" . $loginCookieValue,
            ];

            if ($this->ssoConfig->isWireMockLoginEnable() && !$uuid) {
                $uuid = $this->cookieManager->getCookie('fcl_uuid');
                $endUrl = $this->ssoConfig->getWireMockProfileUrl();
            }

            if ($uuid) {
                $headers = [
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Accept-Language: json",
                    "X-clientid: ISHP",
                    "uuid: " . $uuid,
                    $authHeaderVal . $gatewayToken

                ];
            }

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );

            $this->curl->post($endUrl, '');

            /* Return 401 from profile api response */
            $profileResponseStatus = $this->curl->getStatus();
            $this->logger->info($logHeader . ' Line:' . __LINE__
            . "Profile API Response Status: " . $profileResponseStatus);

            $response = $this->curl->getBody();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Profile API Response: ' . var_export($response, true));

            $profileResponse = json_decode($response, true);
            if ($this->toggleConfig->getToggleConfigValue('remove_expired_fedex_login_cookie') &&
            isset($profileResponse['errors'][0]['code']) &&
            $profileResponse['errors'][0]['code'] == "REQUEST.UNAUTHORIZED") {
                $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDomain(".fedex.com")
                ->setPath("/")
                ->setHttpOnly(false)
                ->setSecure(true);

                if ($this->getFCLCookieNameToggle()) {
                    $cookieName = $this->getFCLCookieConfigValue();
                    $this->cookieManager->deleteCookie($cookieName, $metadata);
                }
                $this->cookieManager->deleteCookie(self::FORGE_ROCK_COOKIE_NAME, $metadata);
                $this->cookieManager->deleteCookie(ProfileManagement::LOGIN_COOKIE_NAME, $metadata);
            }

            if ($profileResponseStatus === 401) {
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ':Profile API Request: ' . var_export($dataString, true)
                );
                $this->customerSession->unsFclFdxLogin();
                return $profileResponseStatus;
            }
            if ($profileResponseStatus !== 200) {
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ':Profile API Request: ' . var_export($dataString, true)
                );
                return $profileResponseStatus;
            }

            //check if login type sso with fcl
            if ($this->getSSOWithFCLToggle() && $loginType == 'sso') {
                $isCustomerGroup = $this->checkCustomerGroup($profileResponse);
                if (!$isCustomerGroup) {
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ . ' Customer Group not matched'
                    );
                    $this->customerSession->unsFclFdxLogin();
                    return 401;
                }
            }

            $replaceStr = ['\"', '"{', '}"', '\r\n', '\t'];
            $replaceByStr = ['"', '{', '}', '', ''];

            $profileResponse = str_replace($replaceStr, $replaceByStr, $response);

            $profileInfo = json_decode($profileResponse);

            if ($this->toggleConfig->getToggleConfigValue('mazegeeks_user_preference_import_fields')) {
                if (isset($profileInfo->output) && $profileInfo->output->profile->userProfileId) {
                    $updateProfileData = $this->userPreferenceHelper->updateProfileResponse($profileInfo);

                    if ($updateProfileData && $this->customerSession->getProfileSession()) {
                        $profileInfo = $this->customerSession->getProfileSession();
                    }
                }
            }


                $profile = $profileInfo->output->profile;
                if(property_exists($profile, "preferences")) {
                    $isProfileHasInvoiceNumber = $this->isProfileHasInvoiceValid($profileInfo->output->profile->preferences);
                    if ($isProfileHasInvoiceNumber) {
                        $newAccounts = $this->getFedexAccounts($profileInfo->output->profile);
                        if ($newAccounts) {
                            $profileInfo->output->profile->accounts = $newAccounts;
                        }
                    }
                }

            if (isset($profileInfo->output) && $profileInfo->output->profile->userProfileId) {
                $this->customerSession->setProfileSession($profileInfo);
            }
            if (isset($profileInfo->output->profile->creditCards)) {
                $this->customerSession->setCreditCardList($profileInfo->output->profile->creditCards);
            }
            if (isset($profileInfo->output->profile->accounts)) {
                $this->customerSession->setFedexAccountsList($profileInfo->output->profile->accounts);
            }

            // B-1325041 : SDE SSO refactor
            $uuId = $firstName = $lastName = $addressEmail = $addressCompany =
            $addressContactNumber = $addressExt = $addressCity = $addressStateOrProvinceCode =
            $addressPostalCode = $addressRegionId = "";
            $addressCountryCode = "US";
            $addressStreetLines = [];

            if (isset($profileInfo->output->profile->uuId)) {
                $uuId = $profileInfo->output->profile->uuId;
            }

            if (isset($profileInfo->output->profile->contact->personName->firstName)) {
                $firstName = $profileInfo->output->profile->contact->personName->firstName;
            }

            if (isset($profileInfo->output->profile->contact->personName->lastName)) {
                $lastName = $profileInfo->output->profile->contact->personName->lastName;
            }

            if (isset($profileInfo->output->profile->contact->emailDetail->emailAddress)) {
                $addressEmail = $profileInfo->output->profile->contact->emailDetail->emailAddress;
            }

            if (isset($profileInfo->output->profile->contact->company->name)) {
                $addressCompany = $profileInfo->output->profile->contact->company->name;
            }

            if (isset($profileInfo->output->profile->contact->phoneNumberDetails[0]->phoneNumber->number)) {
                $addressContactNumber = $profileInfo->output->profile->contact
                    ->phoneNumberDetails[0]->phoneNumber->number;
            }

            if (isset($profileInfo->output->profile->contact->phoneNumberDetails[0]->phoneNumber->extension)) {
                $addressExt = $profileInfo->output->profile->contact->phoneNumberDetails[0]->phoneNumber->extension;
            }

            if (isset($profileInfo->output->profile->contact->address->streetLines[0])) {
                $addressStreetLines[0] = $profileInfo->output->profile->contact->address->streetLines[0];
            }

            if (isset($profileInfo->output->profile->contact->address->streetLines[1])) {
                $addressStreetLines[1] = $profileInfo->output->profile->contact->address->streetLines[1];
            }

            if (isset($profileInfo->output->profile->contact->address->city)) {
                $addressCity = $profileInfo->output->profile->contact->address->city;
            }

            if (isset($profileInfo->output->profile->contact->address->stateOrProvinceCode)) {
                $addressStateOrProvinceCode = $profileInfo->output->profile->contact->address->stateOrProvinceCode;
            }

            if (isset($profileInfo->output->profile->contact->address->postalCode)) {
                $addressPostalCode = $profileInfo->output->profile->contact->address->postalCode;
            }

            if (isset($profileInfo->output->profile->contact->address->countryCode)) {
                $addressCountryCode = $profileInfo->output->profile->contact->address->countryCode;
            }

            if (!empty($profileInfo->output->profile->customerId)) {
                $customerId = $profileInfo->output->profile->customerId;
            }

            //update customer external user ID if not match with profile api loginUserId
            if (isset($profileInfo->output->profile->loginUserId) && $uuId) {
                $loginUserId = $profileInfo->output->profile->loginUserId;
                $this->updateLoginUserId($loginUserId, $uuId);
            }

            if (isset($addressStateOrProvinceCode) && isset($addressCountryCode)) {
                $addressRegionId = $this->regionFactory
                    ->create()
                    ->loadByCode($addressStateOrProvinceCode, $addressCountryCode)->getId();


                if (($addressRegionId == 0 || !$addressRegionId)) {
                    $addressRegionId = $this->regionFactory
                    ->create()
                    ->loadByName(strtolower($addressStateOrProvinceCode), $addressCountryCode)->getId();
                }
            }
            if (isset($profileInfo->output->profile->canvaId)) {
                $this->customerSession->setCustomerCanvaId($profileInfo->output->profile->canvaId);
            }
            if (isset($profileInfo->output->profile->userProfileId)) {
                $this->customerSession->setUserProfileId($profileInfo->output->profile->userProfileId);
            }

            $addressDetails = [
                'uuId' => $uuId,
                'customerId' => $customerId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $addressEmail,
                'countryCode' => $addressCountryCode,
                'postalCode' => $addressPostalCode,
                'city' => $addressCity,
                'stateOrProvinceCode' => $addressRegionId,
                'contactNumber' => $addressContactNumber,
                'ext' => $addressExt,
                'company' => $addressCompany,
                'streetLines' => $addressStreetLines,
            ];

            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Address info: ' . var_export($addressDetails, true));

            return ['address' => $addressDetails];
        } catch (Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ':Error While Geting Profile By Profile Api URL ' . $endUrl
                . ' cookie: ' . $loginCookieValue . ' : ' . $e->getMessage());
        }
    }

    /**
     * Update Login User ID
     *
     * @param $loginUserId
     * @param $uuId
     * @return void
     */
    public function updateLoginUserId($loginUserId, $uuId)
    {
        try {
            $customerModelId = $this->getCustomerIdByUuid($uuId);
            $customerObj = $this->customerFactory->create()->load($customerModelId);
            $externalUserId = $customerObj->getExternalUserId();
            if ($loginUserId != $externalUserId) {
                $customerObj->setExternalUserId($loginUserId);
                $customerObj->save();
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ':Error to save customer data : ' . $e->getMessage());
        }
    }

    /**
     * Get customer id from FCL UUID
     *
     * @param int $fclUuid
     * @return int $customerId
     */
    public function getCustomerIdByUuid($fclUuid)
    {
        try {
            $fclUuidEmail = (string) $fclUuid . "@fedex.com";
            $customerId = 0;
            $customer = $this->customerRepositoryInterface->get($fclUuidEmail);
            $customerId = $customer->getId();

            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Customer retrieved ' . $customerId);
            return $customerId;
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ':Error to get customer data from UUID ' . $fclUuid . ' : ' . $e->getMessage());
        }
    }

    /**
     * Generate unique canva id
     *
     * @param int $length
     * @return string
     */
    public function generateUniqueCanvaId($length = 14)
    {
        $canvaId = $this->guidv4($length);
        return $canvaId;
    }

    /**
     * Generate unique id of 14 characters
     *
     * @param int $length
     * @param void $data
     * @return string
     */
    public function guidv4($length, $data = null)
    {
        $data = $data ?? random_bytes($length);
        $data[6] = chr(ord($data[6])&0x0f | 0x40);
        $data[8] = chr(ord($data[8])&0x3f | 0x80);
        return vsprintf('%s%s-%s%s-%s%s-%s', str_split(bin2hex($data), 2));
    }

    /**
     * Get customer id from canvaid
     *
     * @param string $canvaId
     * @return int
     */
    public function getCustomerIdByCanvaId($canvaId)
    {
        try {
            $collection = $this->customerCollection->addAttributeToSelect('*')
                ->addAttributeToFilter('customer_canva_id', $canvaId)
                ->load();

            $customerId = 0;
            if ($collection->getData()) {
                $customerInfo = $collection->getData();
                $customerId = $customerInfo[0]['entity_id'];
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Customer retrieved' . $customerId);
            return $customerId;
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ':Error to Get Customer from Canva Id ' . $canvaId . ' : ' . $e->getMessage());
        }
    }

    /**
     * Saving Address
     *
     * @param object $customer
     * @param array $adressDetails
     * @param string $addressType
     * @return voids
     */
    public function saveAddress($customer, $adressDetails, $addressType)
    {
        try {
            $adressId = $customer->getDefaultShipping();
            $address = $this->addressDataFactory->create();
            if ($adressId) {
                $address->load($adressId);
            }
            $address->setCustomerId($customer->getId())
                ->setFirstname($adressDetails['firstName'])
                ->setLastname($adressDetails['lastName'])
                ->setEmailId($adressDetails['email'])
                ->setCountryId($adressDetails['countryCode'])
                ->setRegionId($adressDetails['stateOrProvinceCode'])
                ->setCity($adressDetails['city'])
                ->setPostcode($adressDetails['postalCode'])
                ->setExt($adressDetails['ext'])
                ->setTelephone($adressDetails['contactNumber'])
                ->setCompany($adressDetails['company'])
                ->setStreet($adressDetails['streetLines']);
            if (!$adressId) {
                $address->setSaveInAddressBook('1')
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1');
            }
            $address->save();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':address saved for customer' . $adressDetails['email']);
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ':Error in saving ' . $addressType . ' address for customer Id: ' .
            $this->customerSession->getCustomerId() . ' ' . $e->getMessage());
        }
    }

    /**
     * Get customer by email
     * B-1325041 : SDE SSO refactor
     *
     * @return Customer|null
     */
    public function getCustomerByEmail($email, $websiteId = null)
    {
        try {
            if (is_null($websiteId)) {
                $websiteId = $this->storeManager->getWebsite()->getId();
            }
            return $this->customerRepositoryInterface->get($email, $websiteId);
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get customer company id by using store id
     * B-1325041 : SDE SSO refactor
     *
     * B-1598912
     * @return int|null
     */
    public function getCustomerCompanyIdByStore()
    {
        $companyAdditionalDataCollection = $this->additionalDataFactory->create()->getCollection();
        $companyAdditionalDataCollection->addFieldToSelect(AdditionalData::COMPANY_ID);
        $companyAdditionalData = $companyAdditionalDataCollection->getFirstItem();
        $companyId = $companyAdditionalData->getCompanyId();
            $companyData = $this->customerSession->getOndemandCompanyInfo();
            if ($companyData
                && is_array($companyData)
                    && !empty($companyData['company_data']['entity_id'])
            ) {
                $companyId = $companyData['company_data']['entity_id'];
            }
        return $companyId;
    }

    /**
     * Get company customer group id
     * B-1325041 : SDE SSO refactor
     *
     * @return int|null
     */
    public function getCompanyCustomerGroupId($companyId)
    {
        $company = $this->companyRepository->get($companyId);

        return $company->getCustomerGroupId();
    }

    /**
     * Set customer's CanvaId by profile api
     *
     * @param string $endUrl
     * @param string $loginCookieValue
     * @param string $profileId
     * @param string $canvaId
     * @return boolean true|false
     */
    public function setCanvaIdByProfileApi($endUrl, $loginCookieValue, $profileId, $canvaId)
    {
        try {
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDomain(".fedex.com")
                ->setPath("/")
                ->setHttpOnly(false)
                ->setSecure(true)
                ->setSameSite("None");

            $tazToken = $this->punchoutHelper->getTazToken();

            $isSdeStore = $this->sdeHelper->getIsSdeStore();
            $arr = array('canvaId' => $canvaId);
            $dataString = json_encode($arr);
            if ($isSdeStore) {
                $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
                $environmentCookieName = self::SDE_COOKIE_NAME;
            } else {
                $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
                $this->customerSession->setFclFdxLogin($loginCookieValue);
                $environmentCookieName = $this->getFCLCookieNameToggle() ?
                $this->getFCLCookieConfigValue() : ProfileManagement::LOGIN_COOKIE_NAME;
            }
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "X-clientid: ISHP",
                $authHeaderVal . $gatewayToken,
                "Cookie: Bearer=" . $tazToken,
                "Cookie: " . $environmentCookieName . "=" . $loginCookieValue,
            ];

            $this->curl->setOptions(
                [
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );

            if (null !== $this->customerSession->getUserProfileId()) {
                $this->curl->post($endUrl.'/'.$profileId.'/canvaid', '');
            }

            $profileResponseStatus = $this->curl->getStatus();
            $profileResponse = $this->curl->getBody();

            if($profileResponseStatus >= 200 && $profileResponseStatus <=299) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' CanvaId set for user '. $profileId);
                return true;
            } else {
                $response = json_decode($this->curl->getBody());
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Error setting CanvaId for user ' . $profileId . ' ' . $response->errors->message);
                return false;
            }
        } catch(Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Error setting CanvaId for user ' . $profileId . ' ' . $e->getMessage());
        }
    }

    /**
     * Get customer canva id from FCL UUID
     *
     * @param int $fclUuid
     * @return string $customerCanvaId
     */
    public function getCustomerCanvaIdByUuid($fclUuid)
    {
        try {
            $customerCanvaId = '';
            $fclUuidEmail = (string) $fclUuid . "@fedex.com";
            $customer = $this->customerRepositoryInterface->get($fclUuidEmail);
            $customerId = $customer->getId();
            $attribute = $this->attributeRepositoryInterface->get('customer', 'customer_canva_id');
            $connection = $this->resourceConnection->getConnection();
            $query = $connection->select()
                ->from('customer_entity_varchar')
                ->where('attribute_id=?', $attribute->getAttributeId())
                ->where('entity_id=?', $customerId);
            $rowData = $connection->fetchRow($query);
            $customerCanvaId = $rowData['value'] ?? '';

            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':Customer Canva Id retrieved ' . $customerCanvaId);
            return $customerCanvaId;
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ':Error to get customer Canva Id data from UUID ' . $fclUuid . ' : ' . $e->getMessage());
        }
    }

    /**
     * Set customer canva id in POD 2.0 DB to null after migrating canva id to profile service
     *
     * @param string $fclUuid
     * @return boolean true|false
     */
    public function setCustomerCanvaIdAfterMigration($fclUuid)
    {
        try {
            $fclUuidEmail = (string) $fclUuid . "@fedex.com";
            $customer = $this->customerRepositoryInterface->get($fclUuidEmail);
            $customerData = $this->customerFactory->create()->load($customer->getId());
            $customerData->setData('customer_canva_id', null);
            $customerData->save();

            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            ':Customer Canva Id migrated to provile service for uuid ' . $fclUuid);
            return true;
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ':Error getting customer Canva Id data from UUID ' . $fclUuid . ' : ' . $e->getMessage());
        }
    }

    /**
     * Check if CanvaId Migration configuration is enabled or not
     * B-1324662 : Migrate Canva IDs from POD2.0 database to Retail Profile
     *
     * @return bool
     */
    public function isCanvaIdMigrationEnabled()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::XML_PATH_ENABLE_CanvaId_Migration);
    }

    /**
     * isSSOlogin
     * @return boolean
     */
    public function isSSOlogin(): bool
    {
        return $this->authHelper->getCompanyAuthenticationMethod() == AuthHelper::AUTH_SSO;
    }

    /**
     * Get customer's profile details by api
     *
     * @param string $endUrl
     * @param string $loginCookieValue
     * @return array
     */
    public function setFclMetaDataCookies()
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDomain(".fedex.com")
            ->setPath("/")
            ->setHttpOnly(false)
            ->setSecure(true)
            ->setSameSite("None");
        $this->cookieManager->setPublicCookie("fcl_customer_login", true, $metadata);
        $this->cookieManager->setPublicCookie("fcl_customer_login_success", true, $metadata);
    }

    /**
     * @return void
     */
    public function callFclLogoutApi(): void
    {
        $gatewayToken = $this->purpleGatewayToken->getFedexRatesToken();
        if ($this->getFCLCookieNameToggle()) {
            $cookieName = $this->getFCLCookieConfigValue();
            $fdxLoginCookie = $this->cookieManager->getCookie($cookieName);
            $cookieHeader = "Cookie:".$cookieName."= ".$fdxLoginCookie;
        } else {
            $fdxLoginCookie = $this->cookieManager->getCookie("fdx_login");
            $cookieHeader = "Cookie:fdx_login= ".$fdxLoginCookie;
        }
        $endUrl = $this->ssoConfig->getFclLogoutApiUrl();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "X-clientid: INET",
            "Authorization: Bearer ".$gatewayToken,
            $cookieHeader
        ];

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            ]
        );
        try {
            $this->curl->post($endUrl,null);
            $output = $this->curl->getBody();
            $response = json_decode($output,true);
            if ((isset($response['errors']) && $response['errors']) || !isset($response['output'])) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .'LOGIN.REAUTHENTICATE.ERROR');
            } else {
                $encondedResponse =  !empty($response) ? json_encode($response) : '';
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' LOGOUT API SUCCESSFUL ' . $encondedResponse);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . $e->message());
        }
    }

    /**
     * Get FCL cookie name toggle value
     *
     * @return boolean
     */
    public function getFCLCookieNameToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_enable_fcl_cookie_name');
    }

    /**
     * Get FCL cookie name toggle value
     *
     * @return boolean
     */
    public function getSSOWithFCLToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method');
    }

    /**
     * To get the FCL cookie config value
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFCLCookieConfigValue($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_BASE_PATH . self::FCL_COOKIE_CONFIG_FIELD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * check profile has the invoice number or not
     *
     * @return bool
     */
    public function isProfileHasInvoiceValid($preferences)
    {
        if($preferences)
        {

            foreach ($preferences as $preference) {
                if (
                    (strtoupper($preference->name) == "INVOICE_NUMBER") &&
                    property_exists($preference, "values") &&
                    $preference->values[0]->name == 'defaultValue' &&
                    !empty($preference->values[0]->value)
                ) {
                    $summary =  $this->getAccountSummary($preference->values[0]->value);
                    if($summary && array_key_exists('account_status',$summary) && $summary['account_status'] != 'inactive')
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * get Account Number
     *
     */
    public function getAccountNumber($preferences)
    {
        $accounNumber = '';
        if($preferences)
        {
            foreach ($preferences as $preference) {
                if (
                    strtoupper($preference->name) == "INVOICE_NUMBER" &&
                    property_exists($preference, "values") &&
                    $preference->values[0]->name == 'defaultValue' &&
                    !empty($preference->values[0]->value)
                ) {
                    $summary =  $this->getAccountSummary($preference->values[0]->value);
                    if($summary && array_key_exists('account_status',$summary) && $summary['account_status'] != 'inactive')
                    {
                        $accounNumber = $preference->values[0]->value;
                    }
                }
            }
        }
        return $accounNumber;
    }

    /**
     * get accounts from the profile api
     *
     */
    public function getFedexAccounts($profile)
    {
        if (!property_exists($profile, "accounts")) {
            return [];
        }

        $isAccountFound = false;
        $accountNumber = $this->getAccountNumber($profile->preferences);
        $accountSummary = $this->getAccountSummary($accountNumber);
        if($accountSummary)
        {
            $paymnetType = $accountSummary['account_type'];
            if($accountSummary['account_type'] == 'PAYMENT') {
                $paymnetType = "PRINTING";
            }
            $newAccount = [];
            $newAccount['profileAccountId'] = $accountSummary['account_number'];
            $newAccount['accountNumber'] = $accountSummary['account_number'];
            $newAccount['maskedAccountNumber'] = substr($accountSummary['masked_account_number'], -5);
            $newAccount['accountLabel'] = $accountSummary['account_name'];
            $newAccount['accountType'] = $paymnetType;
            $newAccount['billingReference'] = "NULL";
            $newAccount['primary'] = 1;
            $accounts = $profile->accounts;
            $i = 0;
            $accountArray = [];
            foreach ($accounts as $account) {
                $accountClone = [];
                $accountClone = clone $account;
                $accountArray[$i] = $accountClone;
                $accountArray[$i]->primary = false;
                if(substr($accountSummary['masked_account_number'], -4) == substr($account->maskedAccountNumber,-4)) {
                    $isAccountFound = true;
                }
                $i++;
            }

            if (!$isAccountFound && $newAccount) {
                $accountArray[] = (object) $newAccount;
                return $accountArray;
            }

            return $profile->accounts;
        }
    }

    /**
     * Call Api to check the account status
     *
     * @param string $accountNumber
     * @return object
     */
    public function getAccountSummary($accountNumber)
    {

        $search = ['{accountNumber}'];
        $replace = [$accountNumber];
        $endPointUrl = str_replace($search, $replace, (string)$this->getConfigValue(EnhancedProfile::ACCOUNT_SUMMARY));
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $tazToken = $this->punchoutHelper->getTazToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "X-clientid: ISHP",
            $authHeaderVal . $gatewayToken,
            "Cookie: Bearer=" . $tazToken,
            "Cookie: fxo_ecam_userid=user;",
        ];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]
        );

        $accountInfo = [];
        try {
            $this->curl->post($endPointUrl, '');
            $response = json_decode($this->curl->getBody());
            if(($response && (
                    isset($response->status) && $response->status === 500 ||
                    isset($response->message) && $response->message == 'Account Not Found')
                )) {
                $accountInfo['account_status'] = 'inactive';
                $accountInfo['account_type'] = '';
                $accountInfo['account_tax_certificates'] =  '';

                return $accountInfo;
            }
            $accountStatus = '';
            $accountType = '';
            $accountTaxCertificates = '';
            if (isset($response->output->accounts[0]->accountUsage)) {
                $accountUsage = $response->output->accounts[0]->accountUsage;
                $accountNumber = $response->output->accounts[0]->accountNumber;
                $maskedAccountNumber = $response->output->accounts[0]->maskedAccountNumber;
                $accountName = $response->output->accounts[0]->accountName;
                if (isset($accountUsage->print->status)) {
                    $accountStatus = $accountUsage->print->status;
                }
                if (isset($accountUsage->originatingOpco) && $accountUsage->originatingOpco == "FXK") {
                    if (isset($accountUsage->print->payment->allowed)
                        && $accountUsage->print->payment->allowed == "Y") {
                        $accountType = "PAYMENT";
                    } elseif (isset($accountUsage->print->payment->allowed)
                        && $accountUsage->print->payment->allowed == "N") {
                        $accountType = "DISCOUNT";
                    }
                } elseif (isset($accountUsage->originatingOpco) && $accountUsage->originatingOpco == "FX") {
                    if (isset($accountUsage->ship->status)
                        && $accountUsage->ship->status == "true") {
                        $accountType = "SHIPPING";
                    }
                }
                if (isset($accountUsage->print->taxCertificates)
                    && $accountUsage->print->taxCertificates != "null") {
                    $accountTaxCertificates = "TAX";
                }
            }
            $accountInfo['account_status'] = $accountStatus;
            $accountInfo['account_type'] = $accountType;
            $accountInfo['account_number'] = $accountNumber;
            $accountInfo['masked_account_number'] = $maskedAccountNumber;
            $accountInfo['account_name'] = $accountName;
            $accountInfo['account_tax_certificates'] = $accountTaxCertificates;
        } catch (\Exception $e) {
            $this->logger->critical("Payment Account API is not working: " . $e->getMessage());
        }

        return $accountInfo;
    }

    /**
     * Get config value
     *
     * @param string $code
     * @return string
     */
    public function getConfigValue($code)
    {
        return $this->scopeConfig->getValue($code, ScopeInterface::SCOPE_STORE);
    }

    /**
     * To check customer group in profile API response.
     *
     * @param array $profileResp
     * @return boolean
     */
    public function checkCustomerGroup($profileResp)
    {
        $companyId = $this->getCustomerCompanyIdByStore();
        $company = $this->companyRepository->get($companyId);

        $this->logger->info(
            __METHOD__ . ':' . __LINE__ .
            ' Company Id ('.$companyId.') SSO Group set in admin : '. $company->getSsoGroup()
        );
        $companySSOGroupId = $company->getSsoGroup() ?? '';
        $profileGroupIds = $profileResp['output']['profile']['customerGroupIds'] ?? [];

        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' Profile Response Group Ids : ' .
            var_export($profileGroupIds, true)
        );

        if (empty($companySSOGroupId) ||
        (!empty($companySSOGroupId) && in_array($companySSOGroupId, $profileGroupIds))) {
            return true;
        }

        return false;
    }
}
