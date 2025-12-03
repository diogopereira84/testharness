<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Model;

use Exception;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\CustomerDetails\Helper\Data as CustomerDetailsHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as TokenHelper;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\Model\Config as SSOConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;

class Customer extends AbstractModel
{
    /**
     * @var HeaderData
     */
    protected $headerData;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CookieManagerInterface $cookieManager
     * @param CustomerSession $customerSession
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyManagementInterface $companyManagement
     * @param StoreManagerInterface $storeManager
     * @param TokenHelper $tokenHelper
     * @param Curl $curl
     * @param Json $json
     * @param CompanyRepositoryInterface $companyRepository
     * @param ToggleConfig $toggleConfig
     * @param AccountManagementInterface $customerAccountManagement
     * @param AdditionalDataFactory $additionalDataFactory
     * @param UrlInterface $url
     * @param ResponseFactory $responseFactory
     * @param SSOHelper $ssoHelper
     * @param SSOConfig $ssoConfig
     * @param CustomerDetailsHelper $customerDetailsHelper
     * @param ForgeRock $forgeRock
     * @param HeaderData $headerData
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        protected CookieManagerInterface $cookieManager,
        protected CustomerSession $customerSession,
        protected CustomerInterfaceFactory $customerInterfaceFactory,
        protected CustomerRepositoryInterface $customerRepository,
        protected CompanyManagementInterface $companyManagement,
        protected StoreManagerInterface $storeManager,
        protected TokenHelper $tokenHelper,
        protected Curl $curl,
        protected Json $json,
        protected CompanyRepositoryInterface $companyRepository,
        protected ToggleConfig $toggleConfig,
        protected AccountManagementInterface $customerAccountManagement,
        protected AdditionalDataFactory $additionalDataFactory,
        protected UrlInterface $url,
        protected ResponseFactory $responseFactory,
        protected SSOHelper $ssoHelper,
        protected SSOConfig $ssoConfig,
        protected CustomerDetailsHelper $customerDetailsHelper,
        private readonly ForgeRock $forgeRock,
        HeaderData $headerData,
        protected AuthHelper $authHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Check if customer should be redirected to SSO
     * B-1325041 : SDE SSO refactor
     *
     * @return Boolean
     */
    public function redirectCustomerToSso()
    {
        if ($this->authHelper->isLoggedIn()) {
            return false;
        }

        if (!$this->readSmeCookieAndSetCustomer()) {
            return true;
        }

        return false;
    }

    /**
     * Read Sme cookie and set customer session
     * B-1325041 : SDE SSO refactor
     *
     * @return boolean
     */
    public function readSmeCookieAndSetCustomer()
    {
        try {
            $cookie = $this->cookieManager->getCookie(SSOHelper::SDE_COOKIE_NAME);
            $forgeRockCookie = $this->forgeRock->getCookie();
            if ($forgeRockCookie) {
                $cookie = $forgeRockCookie;
            }
            if ($cookie) {
                $apiUrl = $this->ssoConfig->getProfileApiUrl();

                $status = $this->ssoHelper->getCustomerProfile($apiUrl, $cookie);
                if ($status !== 401) {
                    return $status;
                }
            }
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Decrypt SMDEFAULT cookie
     *
     * @return array
     */
    private function decryptCookie($cookie)
    {
        try {
            $tazToken = $this->tokenHelper->getTazToken();
            if ($tazToken) {
                $apiUrl = $this->ssoConfig->getProfileApiUrl();
                $gateWayToken = $this->tokenHelper->getAuthGatewayToken();
                $authHeaderVal = $this->headerData->getAuthHeaderValue();
                if (!empty($apiUrl) && $tazToken && $gateWayToken) {
                    $headers = [
                        "Content-Type: application/json",
                        "Accept: application/json",
                        "Accept-Language: json",
                        $authHeaderVal . $gateWayToken,
                        "Cookie: " . SSOHelper::SDE_COOKIE_NAME . "=" . $cookie,
                        "Cookie: Bearer=" . $tazToken,
                    ];
                    $options = [
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_ENCODING => '',
                    ];

                    $customerData = $this->callDecryptionApi($apiUrl, $options);
                    $email = $customerData['contact']['emailDetail']['emailAddress'] ?? '';
                    $firstname = $customerData['contact']['personName']['firstName'] ?? '';
                    $lastname = $customerData['contact']['personName']['lastName'] ?? '';

                    if (!empty($email) && !empty($firstname) && !empty($lastname)) {
                        $customer = [
                            'email' => $email,
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                        ];

                        return $customer;
                    } else {
                        $this->redirectToPageNotFound();
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Login customer
     *
     * @return boolean
     */
    private function loginCustomer($params = [])
    {
        if ($this->checkIfCustomerAlreadyExists($params['email'])) {
            $this->updateCustomer($params);

            return $this->createSession($params['email']);
        } else {
            return $this->registerCustomer($params);
        }
    }

    /**
     * Check if customer already exists
     *
     * @return boolean
     */
    private function checkIfCustomerAlreadyExists($email)
    {
        try {
            $websiteId = $this->storeManager->getWebsite()->getId();
            return !$this->customerAccountManagement->isEmailAvailable($email, $websiteId);
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Register new customer
     *
     * @return boolean
     */
    private function registerCustomer($customerData)
    {
        try {
            $companyId = $this->ssoHelper->getCustomerCompanyIdByStore();
            $customerGroupId = $this->ssoHelper->getCompanyCustomerGroupId($companyId);

            $customer = $this->customerInterfaceFactory->create();
            $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
            $customer->setStoreId($this->storeManager->getStore()->getStoreId());
            $customer->setEmail($customerData['email']);
            $customer->setFirstname(ucfirst($customerData['firstname']));
            $customer->setLastname(ucfirst($customerData['lastname']));
            if ($customerGroupId) {
                $customer->setGroupId($customerGroupId);
            }
            $customerId = $this->customerRepository->save($customer)->getId();
            // Assign company
            $this->companyManagement->assignCustomer($companyId, $customerId);
            // Create customer session
            if ($customerId && $this->createSession($customerData['email'])) {
                return true;
            }
        } catch (Exception $e) {
            $this->_logger
            ->critical(__METHOD__ . ':' . __LINE__ . ' Unable to register the customer. - ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Create customer session
     *
     * @return boolean
     */
    private function createSession($email)
    {
        try {
            $websiteId = $this->storeManager->getWebsite()->getId();
            $customer = $this->customerRepository->get($email, $websiteId);
            $customerCompanyId = $this->getCustomerCompanyId($customer->getId());
            if ($customerCompanyId) {
                $this->customerSession->logout()->setLastCustomerId($customer->getId());
                // Set customer session
                $this->customerSession->regenerateId();
                $this->customerSession->setCustomerCompany($customerCompanyId);
                $this->customerSession->setCustomerDataAsLoggedIn($customer);

                return true;
            }
        } catch (Exception $e) {
            $this->_logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Call cookie decryption API
     *
     * @return array
     */
    private function callDecryptionApi($apiUrl, $options)
    {
        try {
            $this->curl->setOptions($options);
            $this->curl->get($apiUrl);
            $output = $this->curl->getBody();

            // Log API response
            $this->_logger->info(__METHOD__ . ':' . __LINE__ . ' SDE Cookie Decryption API response - ' . $output);

            if ($output) {
                $response = $this->json->unserialize($output);
                if (isset($response['output']['profile'])) {
                    return $response['output']['profile'];
                } else {
                    $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' --- Error SDE Cookie Decryption API --- ');
                }
            }
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Get customer company id
     *
     * @return int|null
     */
    private function getCustomerCompanyId($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        if ($customer && $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes()) {
            return $companyAttributes->getCompanyId();
        }

        return null;
    }

    /**
     * Update customer data
     *
     * @return void
     */
    public function updateCustomer($customerData)
    {
        try {
            $customer = $this->customerRepository->get($customerData['email']);
            $customer->setFirstname(ucfirst($customerData['firstname']));
            $customer->setLastname(ucfirst($customerData['lastname']));
            $this->customerRepository->save($customer);
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Redirect to 404 page
     *
     * @return void
     */
    private function redirectToPageNotFound()
    {
        $pageNotFoundUrl = $this->url->getUrl('noroute');
        $this->responseFactory->create()->setRedirect($pageNotFoundUrl)->sendResponse();
    }
}
