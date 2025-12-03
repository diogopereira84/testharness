<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Helper;

use Fedex\Company\Api\Data\ConfigInterface as CompanyConfigInterface;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Jwt\JWT;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CUSTOMER = 'customer';
    const EXTRINSIC = 'extrinsic';
    const CONTACT = 'contact';
    const COMPANY_URL = 'company_url';
    const ACCEPTANCE_OPTION = 'acceptance_option';
    const RULE_CODE_E = 'rule_code_e';
    const RULE_CODE_C = 'rule_code_c';
    const SECRET = 'magento@#$fedex123~!*&&';

    // B-1445896 - improve code coverage and resolve sonarlint issue Punchout module
    const X_ON_BEHALF_OF = "X-On-Behalf-Of: ";
    const INVALID_EMAIL = 'Invalid Email.';
    const PREG_REPLACE_RULE = '/[^A-Za-z0-9\-]/';
    const INSTORE_GTN_PREFIX = '2020';
    const DEFAULT_GTN_PREFIX = '2010';

    const TECH_TITANS_B_2294849 = 'tech_titans_b_2294849';

    public $ruleType;
    public $emailCode = false;

    private $extrinsicDataNewRule;
    private Context $context;
    private Registry $_registry;
    public array $_extrinsicData;
    public array $_data;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param EncryptorInterface $encryptorInterface
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerFactory
     * @param AuthDynamicRowsFactory $ruleFactory
     * @param CompanyInterfaceFactory $companyFactory
     * @param CompanyManagementInterface $companyRepository
     * @param ScopeConfigInterface $configInterface
     * @param TimezoneInterface $timezone
     * @param DateTime $date
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param Curl $curl
     * @param ToggleConfig $toggleConfig
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CompanyCustomerInterfaceFactory $compCustInterface
     * @param RequestQueryValidator $requestQueryValidator
     * @param AdditionalDataFactory $additionalDataFactory
     * @param StoreFactory $storeFactory
     * @param StoreManagerInterface $storeManager
     * @param CompanyConfigInterface $companyConfigInterface
     * @param OndemandConfigInterface $ondemandConfigInterface
     * @Param HeaderData $headerData
     * @param AuthHelper $authHelper
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param array $data
     * @param array $extrinsicData
     * @param array $mappingContact
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private Session $customerSession,
        private CustomerInterfaceFactory $customerInterfaceFactory,
        private EncryptorInterface $encryptorInterface,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerFactory $customerFactory,
        private AuthDynamicRowsFactory $ruleFactory,
        private CompanyInterfaceFactory $companyFactory,
        private CompanyManagementInterface $companyRepository,
        private ScopeConfigInterface $configInterface,
        private TimezoneInterface $timezone,
        private DateTime $date,
        protected LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        private Curl $curl,
        protected \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig,
        private CookieManagerInterface $cookieManager,
        private CookieMetadataFactory $cookieMetadataFactory,
        private CompanyCustomerInterfaceFactory $compCustInterface,
        private RequestQueryValidator $requestQueryValidator,
        private AdditionalDataFactory $additionalDataFactory,
        private StoreFactory $storeFactory,
        private StoreManagerInterface $storeManager,
        private CompanyConfigInterface $companyConfigInterface,
        private OndemandConfigInterface $ondemandConfigInterface,
        protected HeaderData $headerData,
        protected AuthHelper $authHelper,
        private readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        array $data = [],
        array $extrinsicData = [],
        protected array $mappingContact = ['firstname' => 'Name',
        'unique_id' => 'Email',
        'email' => 'Email']
    ) {
        parent::__construct($context);
        $this->_data = $data;
        $this->_extrinsicData = $extrinsicData;
        $this->_registry = $registry;
        $this->context = $context;
    }

    /**
     * Perform AutoLogin
     *
     * @param String $token
     *
     * @return array
     */
    public function autoLogin($token)
    {
        $tokenParts = explode('.', $token);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $signatureValid = ($base64UrlSignature === $signatureProvided);
        $paylodDataUtf8Decoded = mb_convert_encoding($payload, 'ISO-8859-1', 'UTF-8');
        $tokenData = json_decode($paylodDataUtf8Decoded);
        if (($tokenData->exp >= time()) && ($signatureValid)) {
            // clear old session
            $this->customerSession->logout()->setLastCustomerId($tokenData->user_id);
            //generate new session
            $redirectUrl = urldecode($tokenData->punchout_data->extra_data->redirect_url);
            $communicationUrl = urldecode($tokenData->punchout_data->extra_data->response_url);
            $customer = $this->customerFactory->create()->load($tokenData->user_id);
            $this->customerSession->regenerateId();
            $this->customerSession->setCustomerAsLoggedIn($customer);
            $this->customerSession->setCustomerCompany($tokenData->punchout_data->company_id);
            $this->customerSession->setBackUrl($redirectUrl);
            $this->customerSession->setCommunicationUrl($communicationUrl);
            $this->customerSession->setCommunicationCookie($tokenData->punchout_data->extra_data->cookie);
            $this->customerSession->setCompanyName($tokenData->company_name);

            // Epro Iframe Session Issue Fix code start
            $publicCookieMetadata = $this->cookieMetadataFactory
                                ->createPublicCookieMetadata()
                                ->setPath("/")
                                ->setHttpOnly(false)
                                ->setSecure(true)
                                ->setSameSite("None");
                $baseStoreUrl =  $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                $baseStoreUrlWithoutHttps = str_replace("https://","",$baseStoreUrl);
                $explodedStoreUrl = explode("/",$baseStoreUrlWithoutHttps);
                $storeUrl = $explodedStoreUrl[0];
                $cookieDomain = ".".$storeUrl;
                $publicCookieMetadata->setDomain($cookieDomain);



            $this->cookieManager->setPublicCookie(
                'PHPSESSID',
                $this->customerSession->getSessionId(),
                $publicCookieMetadata
            );

            // B-1445896 - improve code coverage and resolve sonarlint issue Punchout module
            return $this->customerLogin($this->customerSession, $customer, $tokenData, $redirectUrl, $communicationUrl);
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Token Expired.');
            return ['error' => 1, 'msg' => 'Token Expired', 'url' => ''];
        }
    }

    /**
     * Customer Login through AutoLogin
     * B-1445896 - improve code coverage and resolve sonarlint issue Punchout module
     *
     * @return array
     */
    public function customerLogin($customerSession, $customer, $tokenData, $redirectUrl, $communicationUrl)
    {
         // Epro Iframe Session Issue Fix code end
         $tazToken = $this->getTazToken();
         $gatewayToken = $this->getAuthGatewayToken();

         if (empty($gatewayToken)) {
             $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Gateway Token not available.');
             return ['error' => 1, 'msg' => 'Gateway Token not available', 'url' => ''];
         }
         $customerSession->setGatewayToken($gatewayToken);
         $customerSession->setApiAccessToken($tazToken);
         $customerSession->setApiAccessType('Bearer');
        if ($this->authHelper->isLoggedIn()) {
             $flag = (!empty($customer->getEmail()) &&
                         !empty($customer->getFirstname()) &&
                             !empty($customer->getLastname()) &&
                                 !empty($customer->getContactNumber()));
             $loginD = ['company_id' => $tokenData->punchout_data->company_id,
             'redirect_url' => $redirectUrl, 'response_url' => $communicationUrl,
             'cookie' => $tokenData->punchout_data->extra_data->cookie,
             'company_name' => $tokenData->company_name,
             'gatewayToken' => $gatewayToken, 'access_token' => $tazToken, 'token_type' => 'Bearer'];

             return ['error' => 0, 'msg' => 'Customer Logged in Successfully',
             'url' => $tokenData->url, 'customer_id' => $customer->getId(),
             'allow' => (int) $flag, 'loginData' => $loginD];
         } else {
             $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Unable to Login.');
             return ['error' => 1, 'msg' => 'Unable to Login', 'url' => ''];
         }
    }

    /**
     * Before Registeration Lookup customer for existing emailID
     *
     * @param array $customerData
     * @param array $verified
     *
     * @return String| array
     */
    public function lookUpDetails($customerData, $verified)
    {
        $customer = $this->customerFactory->create()
            ->setWebsiteId($verified['website_id'])->loadByEmail($customerData['email']);
        if (!empty($customer->getId())) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Customer Email already exists.');
            return ['error' => 1, 'token' => '', 'msg' => 'Customer Email already exist'];
        } else {
            return $this->autoRegister($customerData, $verified);
        }
    }

    /**
     * Auto Register
     *
     * Added $isSelfRegReq = false in B-1320022 - WLGN integration for selfReg customer
     *
     * @param array $customerData
     * @param array $verified
     *
     * @return String|array
     */
    public function autoRegister($customerData, $verified, $isSelfRegReq = false)
    {
        try {
            $customer = $this->customerInterfaceFactory->create();
            $customer->setWebsiteId($verified['website_id']);
            $customer->setStoreId($verified['store_id']);
            $external_identifier = array_key_exists('external_identifier', $customerData) ?
            $customerData['external_identifier'] : $customerData['email'];
            $customer->setEmail($customerData['email']);
            $customer->setCustomAttribute('external_identifier', $external_identifier);
            $customer->setFirstname($customerData['firstname']);
            $customer->setLastname($customerData['lastname']);
            $customer->setGroupId((int) $verified['group_id']);


            //B-1320022 - WLGN integration for selfReg customer
            // code to make customer status inactive, if admin_approval is set in company setting for selfreg
            if ($isSelfRegReq && isset($customerData['status']) && $customerData['status'] == 'inactive') {
                $customerExtensionAttributes = $customer->getExtensionAttributes();
                /** @var CompanyCustomerInterface $companyCustomerAttributes */
                $companyCustomerAttributes = $customerExtensionAttributes->getCompanyAttributes();
                if (!$companyCustomerAttributes) {
                    $companyCustomerAttributes = $this->compCustInterface->create();
                }

                $companyCustomerAttributes->setStatus(0);

                if($customerData['pending_approval_toggle']){
                    $customer->setCustomAttribute('customer_status',2);
                }

                $customerExtensionAttributes->setCompanyAttributes($companyCustomerAttributes);
                $customer->setExtensionAttributes($customerExtensionAttributes);
            }

            $customerId = $this->customerRepository->save($customer)->getId();
            $this->setCustomerNewId($customerId);
            $this->companyRepository->assignCustomer($verified['company_id'], $customerId);
            $customer = $this->customerFactory->create()->load($customerId);

            //B-1320022 - WLGN integration for selfReg customer
            if ($isSelfRegReq) {
                return $customer;
            }

            return $this->getToken($customer, $verified);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return ['error' => 1, 'token' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Verify Company
     *
     * @param Object $cxml
     * @param String $type
     *
     * @return array
     */
    public function verifyCompany($cxml, $type)
    {
        $companyObj = $this->companyFactory->create();
        $company = $this->extractCompanyInformation($companyObj, $cxml);
        if ($company && !empty($company->getId())) {
            $cxmlData = $this->validateHeader($cxml, $type);
            $customer = $this->customerFactory->create()->load($company->getSuperUserId());
            if (!empty($customer->getId()) && $cxmlData['result']) {
                if ($type == self::CUSTOMER) {
                    $rules = $this->getCompanyOauthRules($company);
                    $this->ruleType = $company->getAcceptanceOption();
                    $rType = $this->getRuleType();
                    $verified = ['status' => 'ok', 'website_id' => $customer->getWebsiteId(),
                    'website_url' => $this->getCompanyUrl($company),//$company->getCompanyUrl(),
                    'group_id' => $company->getCustomerGroupId(),
                    'company_id' => $company->getId(),
                    'company_name' => $company->getCompanyName(),'msg' => '',
                    'store_id' => $customer->getStoreId(),
                    'rule' => $rules, 'type' => $rType,
                    'extra_data' => $cxmlData['extra_data'],
                    'legacy_site_name' => $company->getSiteName()];
                } else {
                    $verified = ['status' => 'ok', 'website_id' => $customer->getWebsiteId(),
                    'website_url' => $this->getCompanyUrl($company),//$company->getCompanyUrl(),
                    'group_id' => $company->getCustomerGroupId(),
                    'company_id' => $company->getId(),
                    'company_name' => $company->getCompanyName(), 'msg' => '',
                    'store_id' => $customer->getStoreId(), 'rule' => '',
                    'type' => '', 'extra_data' => $cxmlData['extra_data'],
                    'legacy_site_name' => $company->getSiteName()];
                }
            } else {
		$this->logger->error(__METHOD__ . ':' . __LINE__ . ' Company Owner Not Available.');
                $verified = ['status' => 'error', 'website_id' => '',
                'website_url' => $this->getCompanyUrl($company),//$company->getCompanyUrl(),
                'group_id' => $company->getCustomerGroupId(),
                'company_id' => $company->getId(), 'msg' => 'Company Owner Not Available'];
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Company Not Available.');
            $verified = ['status' => 'error', 'website_id' => '', 'website_url' => '',
            'group_id' => '', 'company_id' => '', 'msg' => 'Company Not Available'];
        }
        return $verified;
    }

    /**
     * Verify Extract Company Details from CXML
     *
     * @param Object $company
     * @param Object $cxml
     *
     * @return array
     */
    public function extractCompanyInformation($company, $cxml)
    {
        $toParts = $cxml->Header->From;
        foreach ($toParts as $toPart) {
            foreach ($toPart as $data) {
                $companyId = $data->Identity;
                $company->load($companyId, 'network_id');
                if (!empty($company->getId())) {
                    foreach ($data->attributes() as $key => $value) {
                        if ($company->getDomainName() == trim($value)) {
                            return $company;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Validate CXML Header
     *
     * @param Object $cxml
     * @param String $type
     *
     * @return array
     */
    public function validateHeader($cxml, $type)
    {
        $responseUrlFlag = 0;
        $backUrl = $responseUrl = $cookie = '';
        $toFlag = $this->validateHeaderTo($cxml->Header->To);
        $senderFlag = $this->validateHeaderSender($cxml->Header->Sender);
        if ($type == self::CUSTOMER) {
            if (isset($cxml->Request->PunchOutSetupRequest->BrowserFormPost->URL) &&
                !empty($cxml->Request->PunchOutSetupRequest->BrowserFormPost->URL)) {
                $responseUrl = ((array) $cxml->Request->PunchOutSetupRequest->BrowserFormPost->URL)[0];
                $responseUrlFlag = 1;
            }
            if (isset($cxml->Request->PunchOutSetupRequest->SupplierSetup->URL) &&
                !empty($cxml->Request->PunchOutSetupRequest->SupplierSetup->URL)) {
                $backUrl = ((array) $cxml->Request->PunchOutSetupRequest->SupplierSetup->URL)[0];
            }
            if (isset($cxml->Request->PunchOutSetupRequest->BuyerCookie) &&
                !empty($cxml->Request->PunchOutSetupRequest->BuyerCookie)) {
                $cookie = ((array) $cxml->Request->PunchOutSetupRequest->BuyerCookie)[0];
            }
        } else {
            $responseUrlFlag = 1;
        }

        return ['result' => $toFlag * $senderFlag * $responseUrlFlag,
        'extra_data' => ['redirect_url' => urlencode($backUrl),
        'response_url' => urlencode($responseUrl), 'cookie' => $cookie]];
    }

    /**
     * Validate CXML Header From Part
     *
     * @param array $toParts
     *
     * @return Boolean
     */
    public function validateHeaderTo($toParts)
    {
        foreach ($toParts as $toPart) {
            foreach ($toPart as $data) {
                foreach ($data->attributes() as $key => $value) {
                    if ($this->getHeaderToDomain() == trim($value) &&
                        $this->getHeaderToIdentity() == trim($data->Identity)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Validate CXML Header From Sender
     *
     * @param array $cxml
     *
     * @return Boolean
     */
    public function validateHeaderSender($cxml)
    {
        $flag = 0;
        foreach ($cxml as $senderPart) {
            foreach ($senderPart as $data) {
                foreach ($data->attributes() as $key => $value) {
                    if (trim((string)$this->getSenderCredential()) == trim($value) &&
                            trim($data->Identity) == trim($this->getSenderIdentity()) &&
                                trim($data->SharedSecret) == trim($this->getSenderSecret())) {
                        $flag = 1;
                    }
                }
            }
            if ($senderPart->UserAgent != $this->getSenderUserAgent()) {
                $flag = 0;
            }
        }

        return $flag;
    }

    /**
     * Get Company Rule Type
     *
     * @return array
     */
    public function getRuleType()
    {
        $rType = '';
        if ($this->ruleType == 'both') {
            $rType = [self::EXTRINSIC, self::CONTACT];
        } else if ($this->ruleType == self::EXTRINSIC) {
            $rType = [self::EXTRINSIC];
        } else if ($this->ruleType == self::CONTACT) {
            $rType = [self::CONTACT];
        }
        return $rType;
    }

    /**
     * Get Company Setup Rules
     *
     * @param Object $company
     *
     * @return array
     */
    public function getCompanyOauthRules($company)
    {
        $acceptance_option = $company->getAcceptanceOption();
        $response = [];
        if ($acceptance_option == 'both') {
            $collection = $this->ruleFactory->create()->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $company->getId()])
                    ->addFieldToFilter('type', ['in' => [self::EXTRINSIC, self::CONTACT]]);
            foreach ($collection as $rule) {
                $type = $rule->getType();
                $response[$type][] = $rule->getRuleCode();
                if (stripos($rule->getRuleCode(), "email") === true) {
                    $this->emailCode = true;
                }
            }
        } else {
            $collection = $this->ruleFactory->create()->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('company_id', ['eq' => $company->getId()])
                ->addFieldToFilter('type', ['in' => [$acceptance_option]]);
            foreach ($collection as $rule) {
                $response[$acceptance_option][] = $rule->getRuleCode();
                if (stripos($rule->getRuleCode(), "email") === true) {
                    $this->emailCode = true;
                }
            }
        }

        return $response;
    }

    /**
     * Validate CXML Rule Data With Company Rule Setup
     *
     * @param Object $xml
     * @param String $type
     * @param array $rule
     *
     * @return boolean
     */
    public function validateXmlRuleData($xml, $type, $rule)
    {
        if ($type == self::EXTRINSIC) {
            // B-1445896
            return $this->prepareExtrinsicData($xml, $type, $rule);
        }

        if ($type == self::CONTACT) {
            $temp = [];
            $json = json_encode($xml->Request->PunchOutSetupRequest->Contact);
            $output = json_decode($json, true);
            foreach ($output as $key => $value) {
                $temp[$key] = $value;
            }
            foreach ($rule as $list) {
                if (isset($temp[$list])) {
                    $this->_data[$list] = $temp[$list];
                } else {
                    return 0;
                }
            }
            return 1;
        }
    }

    /**
     * Validate CXML Rule Data With Company Rule Setup and prepareExtrinsicData
     * // B-1445896
     * @param Object $xml
     * @param String $type
     * @param array $rule
     *
     * @return boolean
     */
    public function prepareExtrinsicData($xml, $type, $rule)
    {
        $c = 0;
        $temp = [];
        $temp2 = [];
        foreach ($xml->Request->PunchOutSetupRequest->Extrinsic as $k => $v) {
            foreach ($xml->Request->PunchOutSetupRequest->Extrinsic[$c++]->attributes() as $a => $b) {
                $b = str_replace('"', '', json_encode((string) $b));
                $temp[$b] = str_replace('"', '', json_encode((string) $v));
            }
        }
        foreach ($rule as $list) {
            if (isset($temp[$list])) {
                $temp2[$list] = $temp[$list];

            } else {
                return 0;
            }
        }

        $this->_extrinsicData = array_merge($temp2, $temp);
        $this->extrinsicDataNewRule = array_keys($temp2);

        return 1;
    }

    /**
     * Throw an exception message
     *
     * @param String $exceptionMessage
     *
     * @return XML
     */
    public function throwError($exceptionMessage)
    {
	$payload = uniqid() . '.' . $this->uniqidReal();
        $timestamp = $this->timezone->date($this->date->gmtDate())->format('Y-m-d H:i:sP');
        // B-1445896
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
				'<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.2.021/cXML.dtd">'.
				'<cXML payloadID="' . $payload .
				'@c0022326.prod.cloud.fedex.com" timestamp="' . $timestamp .
				'" xml:lang="en-us"><Response><Status code="403" text="Internal Server Error">' . $exceptionMessage .
				'</Status></Response></cXML>';
        return $xml;
    }

    /**
     * Extract Customer Data From CXML (Contact high priority | Extrinsic low priority)
     *
     * @param String $company_name
     *
     * @return array
     */
    public function extractCustomerData($company_name, $withNewEproUserIdentiferRule)
    {

        if ($this->ruleType == 'both') {
            return $this->extractCombination($company_name, $withNewEproUserIdentiferRule);
        } else if ($this->ruleType == 'extrinsic') {
            return $this->extractExtrinsic($company_name, $withNewEproUserIdentiferRule);
        } else if ($this->ruleType == 'contact') {
            return $this->extractContactData($company_name, $withNewEproUserIdentiferRule);
        }
    }

    /**
     * Check if Custmer is Active
     *
     * @param Object $customer
     *
     * @return boolean
     */
    public function isActiveCustomer($customer)
    {
        $customer = $this->customerRepository->getById($customer->getId());
        $companyAttributes = null;
        if ($customer->getExtensionAttributes() !== null
            && $customer->getExtensionAttributes()->getCompanyAttributes() !== null
        ) {
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        }
        if (!empty($companyAttributes)) {
            return $companyAttributes->getStatus();
        } else {
            return 0;
        }
    }

    /**
     * Generate JWT Token
     *
     * Generated Token Expired in 30 min
     *
     * @return array
     */
    public function getToken($customer, $company)
    {
        $error = 1;
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]);

        // Create the token payload
        $payload = json_encode([
            'user_id' => $customer->getId(),
            'url' => $company['website_url'],
            'company_name' => $company['company_name'],
            'punchout_data' => ['company_id' => $company['company_id'], 'extra_data' => $company['extra_data']],
            'address' => [],
            'exp' => strtotime("+30 minutes"),
        ]);
        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], mb_convert_encoding(base64_encode($payload), 'UTF-8', 'ISO-8859-1'));

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::SECRET, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Create JWT
        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        if (!empty($token)) {
            $error = 0;
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Empty JWT token.');
        }
        return ['error' => $error, 'token' => $token];
    }

    /**
     * Prepare response XML
     *
     * @param array  $data
     * @param String $token
     *
     * @return XML
     */
    public function sendToken($data, $token)
    {
        //$rand = rand(); //16/32 UUID
        $payload = uniqid() . '.' . $this->uniqidReal();
        // B-1445896
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.2.021/cXML.dtd">'.
                '<cXML payloadID="' . $payload .
                '@shop.fedex.com" timestamp="2020-10-05T06:19:25.320-07:00" xml:lang="en-us">'.
                '<Response><Status code="200" text="OK" /><PunchOutSetupResponse>'.
                '<StartPage><URL>' . $data['website_url'] . '/punchout/autologin/index/token/' .
                $token . '</URL></StartPage></PunchOutSetupResponse></Response></cXML>';
        return $xml;
    }

    /**
     * Generate Unique Id.
     *
     * @param Int $lenght
     *
     * @return String
     */
    public function uniqidReal($lenght = 16)
    {
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        }
        // @codeCoverageIgnoreStart
        elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' No cryptographically secure random function available.');
            throw new Exception("no cryptographically secure random function available");
        }
        // @codeCoverageIgnoreEnd
        return substr(bin2hex($bytes), 0, $lenght);
    }

    /**
     * Configuration Taz Token API URL
     *
     * @return String
     */
    public function getTazTokenUrl()
    {
        return $this->configInterface->getValue("fedex/taz/service_tokens_api_url", ScopeInterface::SCOPE_STORE);
    }

    /**
     * Configuration Taz Token API Client ID
     *
     * @param boolean $publicFlag
     * @return String
     */
    public function getTazClientId($publicFlag)
    {
        if ($publicFlag) {
            $clientIdPath = "fedex/taz/public_client_id";
        } else {
            $clientIdPath = "fedex/taz/client_id";
        }

        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue($clientIdPath, ScopeInterface::SCOPE_STORE)
        );
    }

    /**
     * Configuration Taz Token API Client Secret
     *
     * @param boolean $publicFlag
     * @return String
     */
    public function getTazClientSecret($publicFlag)
    {
        if ($publicFlag) {
            $clientSecretPath = "fedex/taz/public_client_secret";
        } else {
            $clientSecretPath = "fedex/taz/client_secret";
        }

        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue($clientSecretPath, ScopeInterface::SCOPE_STORE)
        );
    }

    /**
     * Token API To Get Taz Token
     *
     * @param boolean $publicFlag
     * @param boolean $forEmail
     * @return string|null
     */
    public function getTazToken($publicFlag = false, $forEmail = false)
    {
        if ($this->customerSession->getOnBehalfOf() && !$forEmail) {
            return null;
        }

        if ($this->isTazTokenExpired()) {
            $apiURL = $this->getTazTokenUrl();
            $client_id = $this->getTazClientId($publicFlag);
            $client_secret = $this->getTazClientSecret($publicFlag);
            $gatewayClientId = $this->getGatewayClientID();

            try {
                if (!$apiURL || !$client_id || !$client_secret || !$gatewayClientId) {
                    $this->logger->critical(__METHOD__.':'.__LINE__.' Missing Taz Token Configuration!');
                    throw new ConfigurationMismatchException(__('Missing Taz Token Configuration!'));
                }

                $params = [
                    'grant_type' => 'client_credentials',
                    'client_id' => $client_id,
                    'client_secret' => $client_secret
                ];

                $disableAcceptEncodingToggle = $this->toggleConfig->getToggleConfigValue('disable_accept_encoding_l1_l3');

                if ($disableAcceptEncodingToggle) {
                    $header = [
                        "Apikey: $gatewayClientId",
                        "client_id: $gatewayClientId",
                        "Connection: Keep-Alive",
                        "Keep-Alive: 300"
                    ];
                } else {
                    $header = [
                        "Accept-Encoding: *",
                        "Apikey: $gatewayClientId",
                        "client_id: $gatewayClientId",
                        "Connection: Keep-Alive",
                        "Keep-Alive: 300"
                    ];
                }

                $this->curl->setOptions(
                    [
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $header
                    ]
                );

                $apiURL = $this->addCacheInfoInApiUrl($apiURL);
                $this->curl->post($apiURL, $params);


                $response = $this->curl->getBody();
                $responseData = json_decode($response, true);
                if (isset($responseData['error'])) {
                    $this->logger->critical(__METHOD__.':'.__LINE__.' '.$responseData['error']);
                    throw new LocalizedException($responseData['error']);
                }

                $tazToken = $responseData["access_token"] ?? null;
                $this->setTazTokenInfo($tazToken, $responseData);
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__.':'.__LINE__.' Taz Token API Error: ' . $e->getMessage());
                return null;
            }
        }

        return $this->customerSession->getTazToken();
    }

    /**
     * Add cacheInfo In apiUrl
     * // B-1445896
     *
     * @param string $apiURL
     * @return string
     */
    public function addCacheInfoInApiUrl($apiURL) {
        if (strpos($apiURL, '&noCache=') !== false) {
            $uniqueId = $this->uniqidReal(6);
            $noCache = 'noCache=' . $uniqueId;
            $apiURL = preg_replace("/noCache=.*?/", $noCache, $apiURL);
        } else {
            $uniqueId = $this->uniqidReal(6);
            $apiURL .= (strpos($apiURL, '?') !== false ?
            '&noCache=' . $uniqueId :  '?noCache=' . $uniqueId);
        }
        return $apiURL;
    }

     /**
     * Response from curl request
     * // B-1445896
     *
     * @param string $tazToken
     * @param array $responseData
     *
     * @return array $responseData
     */
    public function setTazTokenInfo($tazToken, $responseData)
    {
        if ($tazToken) {
            $this->customerSession->setTazToken($tazToken);
            $this->customerSession->setTazTokenExpirationTime(time() + $responseData["expires_in"] ?? 3600);
        }
    }

    /**
     * Check if Taz Token is still valid
     * @return bool
     */
    private function isTazTokenExpired() {
        $expirationTime = $this->customerSession->getTazTokenExpirationTime();
        if ($expirationTime && $expirationTime > time()) {
            return false;
        }
        return true;
    }

    /**
     * Configuration Gateway Token API URL
     * @return array
     */
    public function getGatewayTokenUrl()
    {
        return $this->configInterface->getValue(
            "fedex/gateway_token/gateway_token_api_url", ScopeInterface::SCOPE_STORE);
    }

    /**
     * Configuration Gateway Token API Client ID
     * @return string
     */
    public function getGatewayClientID()
    {
        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue("fedex/gateway_token/client_id", ScopeInterface::SCOPE_STORE)
        );
    }

    /**
     * Configuration Gateway Token API Client Secret
     * @return array
     */
    public function getGatewayClientSecret()
    {
        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue("fedex/gateway_token/client_secret", ScopeInterface::SCOPE_STORE)
        );
    }

    /**
     * Token API To Get Gateway Token
     *
     * @return String
     */
    public function getGatewayToken($fromDownload = false)
    {
        if ($fromDownload || $this->toggleConfig->getToggleConfigValue('B1837352_verify_gateway_call')) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Inside GatewayToken API call ');
        if ($this->isGatewayTokenExpired()) {
            $apiURL = $this->getGatewayTokenUrl();
            $id = $this->getGatewayClientID();
            $secret = $this->getGatewayClientSecret();
            try {

                if (!$apiURL || !$id || !$secret) {
                    throw new ConfigurationMismatchException(__('Missing Gateway Token Configuration!'));
                }

                $headers = [
                    "Content-Type: application/x-www-form-urlencoded",
                    "Connection: Keep-Alive",
                    "Keep-Alive: 300"
                ];

                if ($this->customerSession->getOnBehalfOf()) {
                    $headers[] = self::X_ON_BEHALF_OF . $this->customerSession->getOnBehalfOf();
                }

                $params = ['client_secret' => $secret, 'client_id' => $id];
                $paramString = http_build_query($params);
                $postData = json_encode($params);

                $this->curl->setOptions(
                    [
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $paramString,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $headers
                    ]
                );

                $this->curl->post($apiURL, $postData);
                $response = json_decode($this->curl->getBody(), true);

                if (isset($response['error'])) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $response['error']);
                    throw new LocalizedException($response['error']);
                }

                $gatewayToken = $response['access_token'] ?? null;

                // B-1445896
                $this->setGatewayTokenInfo($gatewayToken, $response);
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Gateway Token API Error: ' . $e->getMessage());
                return null;
            }
        }
        return $this->customerSession->getGatewayToken();
       } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Toggle B1837352_verify_gateway_call is disabled');
            return null;
        }
    }


    /**
     * @return string|null
     */
    public function getAuthGatewayToken()
    {
        if($this->toggleConfig->getToggleConfigValue('E352723_use_clientId_header')){
            return $this->getGatewayClientID();
        } else {
            return $this->getGatewayToken();
        }

    }

    /**
     * Response from curl request
     * // B-1445896
     *
     * @param string $gatewayToken
     * @param array $response
     * @return array $response
     */
    public function setGatewayTokenInfo($gatewayToken, $response)
    {
        if ($gatewayToken) {
            $this->customerSession->setGatewayToken($gatewayToken);
            $this->customerSession->setGatewayTokenExpirationTime(time() + $response["expires_in"] ?? 3600);
        }
    }
    /**
     * Configuration Cxml Header To Domain
     * @return array
     */
    public function getHeaderToDomain()
    {
        return $this->configInterface->getValue(
            "header_settings/general/header_to_domain", ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Configuration Cxml Header To Identity
     * @return array
     */
    public function getHeaderToIdentity()
    {
        return $this->configInterface->getValue(
            "header_settings/general/header_to_identity", ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Configuration Cxml Sender Credentials
     * @return array
     */
    public function getSenderCredential()
    {
        return $this->configInterface->getValue(
            "header_settings/general/sender_credential", ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Configuration Cxml Sender Identity
     * @return array
     */
    public function getSenderIdentity()
    {
        return $this->configInterface->getValue("header_settings/general/sender_identity", ScopeInterface::SCOPE_STORE);
    }

    /**
     * Configuration Cxml Sender Secret
     * @return array
     */
    public function getSenderSecret()
    {
        return $this->configInterface->getValue("header_settings/general/sender_secret", ScopeInterface::SCOPE_STORE);
    }

    /**
     * Configuration Cxml Sender User Agent
     * @return array
     */
    public function getSenderUserAgent()
    {
        return $this->configInterface->getValue(
            "header_settings/general/sender_user_agent", ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Extract Detail From Contact Rules
     *
     * @param String $company_name
     *
     * @return array
     */
    public function extractContactData($company_name, $withNewEproUserIdentiferRule)
    {
        $key = $this->mappingContact['email'];
        $key2 = $this->mappingContact['firstname'];
        $company_name = str_replace(" ", "", strtolower($company_name));
        if (isset($this->_data[$key]) && !empty($this->_data[$key]) &&
            isset($this->_data[$key2]) && !empty($this->_data[$key2])) {
            $response['email'] = $this->_data[$key];
        } elseif (isset($this->_data[$key]) && !empty($this->_data[$key]) &&
            filter_var($this->_data[$key], FILTER_VALIDATE_EMAIL)) {
            $response['email'] = $this->_data[$key];
        } elseif (isset($this->_data[$key2]) && !empty($this->_data[$key2])) {
            $tempName = strtolower(preg_replace("/[\W\s\/\.\-]/", "", $company_name . '_' . $this->_data[$key2]));
            $response['email'] = $tempName . '@no' . $company_name . '.com';
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . self::INVALID_EMAIL);
            return ['error' => 1, 'msg' => self::INVALID_EMAIL];
        }
        if (isset($this->_data[$key2]) && !empty($this->_data[$key2])) {
            $name = explode(' ', $this->_data[$key2]);
            if (count($name) > 1) {
                $lastname = end($name);
                array_pop($name);
                $first = implode(" ", $name);
                $firstname = $first;
            } else {
                $firstname = $this->_data[$key2];
                $lastname = 'User';
            }
            $response['firstname'] = $firstname;
            $response['lastname'] = $lastname;
        } else {
            $response['firstname'] = $company_name;
            $response['lastname'] = 'User';
        }
        return $response;
    }

    /**
     * Extract Details From Combination
     *
     * @param String $company_name
     *
     * @return array
     */
    public function extractCombination($company_name, $withNewEproUserIdentiferRule)
    {
        $nameList = [];
        $response = [];
        $key = $this->mappingContact['email'];
        $company_name = str_replace(" ", "", strtolower($company_name));
        if (isset($this->_data[$key]) && !empty($this->_data[$key]) &&
        filter_var($this->_data[$key], FILTER_VALIDATE_EMAIL)) {
            $response['email'] = $this->_data[$key];
        } else {
            // B-1445896
            $info = $this->prepareEmailAndNameList($withNewEproUserIdentiferRule);
            $email = $info['email'];
            $nameList = $info['namelist'];

            if (!empty($email)) {
                $response['email'] = $email;
            } else {
                $emailP = implode("_", $nameList);
                $emailP = str_replace(" ", "", strtolower($emailP));
                if (!empty($emailP)) {
                    $response['email'] = $company_name . '_' . $emailP . '@no' . $company_name . '.com';
                } else {
                    $this->logger->error(__METHOD__.':'.__LINE__. self::INVALID_EMAIL);
                    return ['error' => 1, 'msg' => self::INVALID_EMAIL];
                }
            }
        }
        $key = $this->mappingContact['firstname'];
        if (isset($this->_data[$key]) && !empty($this->_data[$key])) {

            // B-1445896
            $nameInfo = $this->getNameFromKey($this->_data[$key]);
            $response['firstname'] = $nameInfo['firstname'];
            $response['lastname'] = $nameInfo['lastname'];
        } elseif (count($nameList)) {

            // B-1445896
            $nameInfo = $this->getNameFromNameList($nameList);
            $response['firstname'] = $nameInfo['firstname'];
            $response['lastname'] = $nameInfo['lastname'];
        } else {
            $response['firstname'] = $company_name;
            $response['lastname'] = 'User';
        }
        return $response;
    }

    /**
     * getNameFromKey
     * // B-1445896
     *
     * @param string $dataKey
     *
     * @return array
     */

    public function getNameFromKey($dataKey)
    {
        $name = explode(' ', $dataKey);
        $response = [];
        if (count($name) > 1) {
            $lastname = end($name);
            array_pop($name);
            $first = implode(" ", $name);
            $firstname = $first;
        } else {
            $firstname = $dataKey;
            $lastname = 'User';
        }

        $response['firstname'] = $firstname;
        $response['lastname'] = $lastname;
        return $response;
    }

    /**
     * getNameFromNameList
     * // B-1445896
     *
     * @param array $nameList
     *
     * @return array
     */
    public function getNameFromNameList($nameList)
    {
        $response = [];
        if (count($nameList) > 1) {
            $lastname = end($nameList);
            array_pop($nameList);
            $first = implode(" ", $nameList);
            $firstname = $first;
        } else {
            // D-82139 || remove space before exploding it
            // B-1299552 - Cleanup Toggle Feature - remove_space_from_name
            $nameList[0] = trim($nameList[0]);

            $fullname = explode(" ", $nameList[0]);
            if (count($fullname) > 1) {
                $lastname = end($fullname);
                array_pop($fullname);
                $first = implode(" ", $fullname);
                $firstname = $first;
            } else {
                $firstname = $fullname[0];
                $lastname = 'User';
            }
        }
        $response['firstname'] = $firstname;
        $response['lastname'] = $lastname;
        return $response;
    }

    /**
     * prepareEmailAndNameList
     * // B-1445896
     *
     * @return array
     */
    public function prepareEmailAndNameList($withNewEproUserIdentiferRule)
    {
        $allowRule = $this->extrinsicDataNewRule;
        $email = '';
        $nameList = [];
        $response = [];
        foreach ($this->_extrinsicData as $key => $val) {
            if ($withNewEproUserIdentiferRule && !in_array($key, $allowRule)) {
                continue;
            } else {
                if (filter_var($val, FILTER_VALIDATE_EMAIL) && $this->emailCode) {
                    if (empty($email)) {
                        $email = $val;
                    }
                } else {
                    $val = preg_replace(self::PREG_REPLACE_RULE, '', $val);
                    $nameList[] = $val;
                }
            }

        }
        $response['email'] = $email;
        $response['namelist'] = $nameList;
        return $response;
    }

    /**
     * Extract Extrinsic
     *
     * @param string $company_name
     *
     * @return array
     */
    public function extractExtrinsic($company_name, $withNewEproUserIdentiferRule)
    {
        $allowRule = $this->extrinsicDataNewRule;


        $nameList = [];
        $email = '';
        $response = [];
        $company_name = str_replace(" ", "", strtolower($company_name));
        $company_name = preg_replace(self::PREG_REPLACE_RULE, '', $company_name);
        foreach ($this->_extrinsicData as $key => $val) {
            if ($withNewEproUserIdentiferRule && !in_array($key, $allowRule)) {
                continue;
            } else {
                if (filter_var($val, FILTER_VALIDATE_EMAIL) && $this->emailCode) {
                    if (empty($email)) {
                        $email = $val;
                    }
                } else {
                    $val = preg_replace(self::PREG_REPLACE_RULE, '', $val);
                    $nameList[] = $val;
                }
            }

        }
        if (!empty($email)) {
            $response['email'] = $email;
        } else {
            $emailP = implode("_", $nameList);
            $emailP = str_replace(" ", "", strtolower($emailP));
            $emailP = str_replace(",", "", strtolower($emailP));
            if (!empty($emailP)) {
                // B-1445896
                $response = $this->addEmailAndExtId($response, $company_name, $emailP);
            } else {
                $this->logger->error(__METHOD__.':'.__LINE__. self::INVALID_EMAIL);
                return ['error' => 1, 'msg' => self::INVALID_EMAIL];
            }
        }

        // B-1445896
        $response = $this->getFirstLastNameFromNameList($response, $company_name, $nameList);
        return $response;
    }

    /**
     * getFirstLastNameFromNameList
     * // B-1445896
     *
     * @param array $response
     * @param string $company_name
     * @param array $nameList
     *
     * @return array
     */
    public function getFirstLastNameFromNameList($response, $company_name, $nameList)
    {
        if (count($nameList)) {
            if (count($nameList) > 1) {

                // B-1445896
                $nameInfo = $this->getNameIfCountGreaterThanOne($nameList);
                $firstname = $nameInfo['firstname'];
                $lastname = $nameInfo['lastname'];
            } else {

                // B-1445896
                $nameInfo = $this->getNameIfCountLessThanOne($nameList);
                $firstname = $nameInfo['firstname'];
                $lastname = $nameInfo['lastname'];
            }

            $firstname = str_replace(" ", "", strtolower($firstname));
            $firstname = str_replace(",", "", strtolower($firstname));
            $firstname = substr($firstname, 0, 30);
            $response['firstname'] = $firstname;
            $lastname = str_replace(" ", "", strtolower($lastname));
            $lastname = str_replace(",", "", strtolower($lastname));
            $lastname = substr($lastname, 0, 30);
            $response['lastname'] = $lastname;
        } else {
            $response['firstname'] = substr($company_name, 0, 30);
            $response['lastname'] = 'User';
        }
        return $response;
    }

    /**
     * addEmailAndExtId
     * // B-1445896
     *
     * @param array $response
     * @param string $company_name
     * @param string $emailP
     *
     * @return array
     */
    public function addEmailAndExtId($response, $company_name, $emailP)
    {
        if($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2294849)){
            $response['external_identifier_id'] = $emailP;
        }
        $response['external_identifier'] = $company_name . '_' . $emailP . '@no' . $company_name . '.com';
        $fullEmailId = $company_name . '_' . $emailP;
        $emailDomain = '@nodomain.com';
        $strippedEmailId = $fullEmailId;
        $customFullEmail = $strippedEmailId . $emailDomain;

        if (strlen($customFullEmail) >= 78) {
            $strippedEmailId = substr($fullEmailId, 0, 77 - strlen($emailDomain));
        }

        $response['email'] = $strippedEmailId . $emailDomain;
        return $response;
    }

    /**
     * getNameIfCountGreaterThanOne
     * // B-1445896
     *
     * @param array $nameList
     *
     * @return array
     */
    public function getNameIfCountGreaterThanOne($nameList)
    {
        $response = [];
        if (empty(end($nameList))) {
            $lastname = "lastname";
        } else {
            $lastname = end($nameList);
        }
        array_pop($nameList);
        $first = implode(" ", $nameList);
        if (empty($first)) {
            $firstname = "firstname";
        } else {
            $firstname = $first;
        }

        $response['firstname'] = $firstname;
        $response['lastname'] = $lastname;
        return $response;
    }

    /**
     * getNameIfCountLessThanOne
     * // B-1445896
     *
     * @param array $nameList
     *
     * @return array
     */
    public function getNameIfCountLessThanOne($nameList)
    {
        // D-82139 || remove space before exploding it
        // B-1299552 - Cleanup Toggle Feature - remove_space_from_name
        $nameList[0] = trim($nameList[0]);
        $fullname = explode(" ", $nameList[0]);

        if (count($fullname) > 1) {
            $lastname = end($fullname);
            array_pop($fullname);
            $first = implode(" ", $fullname);
            $firstname = $first;
        } else {
            if (empty($fullname[0])) {
                $firstname = "firstname";
            } else {
                $firstname = $fullname[0];
            }
            $lastname = 'User';
        }

        $response['firstname'] = $firstname;
        $response['lastname'] = $lastname;
        return $response;
    }

    /**
     * Validate customer against company
     *
     * @param Object $customer
     * @param Int    $company
     *
     * @return boolean
     */
    public function validateCustomer($customer, $company)
    {
        $customerD = $this->customerRepository->getById($customer->getId());
        $companyAttributes = $customerD->getExtensionAttributes()->getCompanyAttributes();
        $companyId = $companyAttributes->getCompanyId();
        if ($companyId == $company) {
            return true;
        }
        return false;

    }

    /**
     * Set customer New Id in registry
     *
     * @param Int $customerNewId
     */
    public function setCustomerNewId($customerNewId)
    {
        $this->_registry->register('customer_new_id', $customerNewId);
    }

    /**
     * Get customer New Id from registry
     *
     * @return Int
     */
    public function getCustomerNewId()
    {
        return $this->_registry->registry('customer_new_id');
    }

    /**
     * B-1014718 - Remove 'Retail Auth Settings' Store configurations
     * Configuration Retail Auth Token API URL
     * @return array
     */
    public function getRetailUrl()
    {
        /**  B-1129692 : Admin setting to create toggle for FXO marketplace feature */
        // B-1445896
        return $this->getGatewayTokenUrl();
    }

    /**
     * B-1014718 - Remove 'Retail Auth Settings' Store configurations
     * Configuration Retail Auth Token API Client ID
     * @return array
     */
    public function getRetailAClientID()
    {
        /**  B-1129692 : Admin setting to create toggle for FXO marketplace feature */
        // B-1445896
        return $this->getGatewayClientID();
    }

    /**
     * B-1014718 - Remove 'Retail Auth Settings' Store configurations
     * Configuration Gateway Token API Client Secret
     * @return array
     */
    public function getRetailAClientSecret()
    {
        /**  B-1129692 : Admin setting to create toggle for FXO marketplace feature */
        // B-1445896
        return $this->getGatewayClientSecret();
    }

    /**
     * Configuration Retail Auth Token API Grant_Type
     * @return array
     */
    public function getRetailAGrantType()
    {
        return $this->configInterface->getValue(
            "fedex/retail_gtn_auth_token/retail_auth_grant_type", ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Configuration Retail Auth Token API Scope
     * @return array
     */
    public function getRetailAScope()
    {
        return $this->configInterface->getValue(
            "fedex/retail_gtn_auth_token/retail_auth_scope", ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Token API To Get Retail Auth Token
     *
     * @return String
     */
    public function getRetailAuthToken()
    {
        /** B-1014718 - Remove 'Retail Auth Settings' Store configurations */
        $apiURL = $this->getRetailUrl();
        $id = $this->getRetailAClientID();
        $secret = $this->getRetailAClientSecret();
        $grant_type = $this->getRetailAGrantType();
        $scope = $this->getRetailAScope();

        $headers = array("Content-Type: application/x-www-form-urlencoded");
        /**  B-1129692 : Admin setting to create toggle for FXO marketplace feature */

        $params = ['client_secret' => $secret, 'client_id' => $id];


        $paramString = http_build_query($params);
        $postData = json_encode($params);

        try {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $paramString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers
                ]
            );
            $this->curl->post($apiURL, $postData);
            $response = json_decode($this->curl->getBody(), true);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Auth token response');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $response['access_token']);

            if (isset($response['error'])) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Retail Gateway Token API Error: ' . print_r($response, true));
            } else {
                return $response['access_token'];
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Retail Gateway Token API Error: ' . $e->getMessage());
        }
    }

    /**
     * Configuration Retail Auth Token API URL
     * @return array
     */
    public function getRetailGTNUrl()
    {
        /**  B-1109907: Optimize Configurations  */
        $is_optimize = $this->toggleConfig->getToggleConfigValue('is_optimize_configuration');
        if($is_optimize){
            return $this->configInterface->getValue("fedex/general/gtn_post_api_url", ScopeInterface::SCOPE_STORE);
        }
        return $this->configInterface->getValue("fedex/gtn/gtn_post_api_url", ScopeInterface::SCOPE_STORE);
    }

    public function getGTNNumber()
    {
        $data_string = "";
        $gtnNum = "";
        $getGTNToken = $this->getAuthGatewayToken();
        $setupURL = $this->getRetailGTNUrl();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $authorization = $authHeaderVal.$getGTNToken;
        $headers = array("Content-Type: application/json",
        "Accept-Language: json" , $authorization ); // Inject the token into the header

        if ($this->customerSession->getOnBehalfOf()) {
            array_push($headers, self::X_ON_BEHALF_OF.$this->customerSession->getOnBehalfOf());
        }

        if(!$this->addToCartPerformanceOptimizationToggle->isActive()){
            $ch = curl_init($setupURL);
        }

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );

        $this->curl->post($setupURL, $data_string);
        $output = $this->curl->getBody();

        if ($output === false) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' GTN Service API Error.');
            return null;
        } else {
            $result = json_decode($this->curl->getStatus());

            if ($result == 200 || $result == 201) {
                $array_data = json_decode($output, true);
                $gtnNum = $array_data['output']['gtn'];
                $gtnNum = ($this->requestQueryValidator->isGraphQl() ?
                        static::INSTORE_GTN_PREFIX :
                        static::DEFAULT_GTN_PREFIX
                    ) . $gtnNum;

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' GTN API response: '. $gtnNum);

                return $gtnNum;
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' GTN Service API Error.');
                return null;
            }
        }
    }

    // D-82139 - Missing Last name while converting quote to order
    public function removeSpaceFromNameToggle(){
		if($this->toggleConfig->getToggleConfigValue('remove_space_from_name')){
			return true;
		}
		return false;
	}

    /**
     * Check if Gateway Token is still valid
     * @return bool
     */
    private function isGatewayTokenExpired()
    {
        $expirationTime = $this->customerSession->getGatewayTokenExpirationTime();
        if ($expirationTime && $expirationTime > time()) {
            return false;
        }
        return true;
    }

    public function getCompanyUrl($company)
    {
        $storeId = $this->ondemandConfigInterface->getB2bDefaultStore();
        $store = $this->storeFactory->create()->load($storeId);
        $companyUrl = $store->getUrl();
        if (!$storeId) {

            $companyAdditionalData = $this->additionalDataFactory->create()
                ->getCollection()->addFieldToSelect('*')
                ->addFieldToFilter('company_id', ['eq' => $company->getId()])->getFirstItem();
            $storeId = $companyAdditionalData->getNewStoreViewId();
            $store = $this->storeFactory->create()->load($storeId);
            $companyUrl = $store->getUrl();

        }
        return trim($companyUrl, '/');
    }

    /**
     * Get PHP Session Id
     * @return string
     */
    public function getPHPSessionId()
    {
        return $this->cookieManager->getCookie('PHPSESSID');
    }
}
