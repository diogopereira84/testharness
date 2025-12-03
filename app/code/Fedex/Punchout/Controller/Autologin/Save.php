<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Controller\Autologin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Base\Helper\Auth as AuthHelper;

class Save extends Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    private $_storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     */
    private $_customerRepositoryInterface;

    /**
     * Save constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CompanyFactory $companyFactory
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CookieManagerInterface $cookieManager
     * @param ToggleConfig $toggleConfig
     * @param AuthHelper $authHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private \Magento\Customer\Model\Session $customerSession,
        private \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected CompanyFactory $companyFactory,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected CookieManagerInterface $cookieManager,
        protected ToggleConfig $toggleConfig,
        protected AuthHelper $authHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        parent::__construct($context);
    }

    /**
     * Auto login action
     *
     * @return mixed.
     */
    public function execute()
    {
	$resultJson = $this->resultJsonFactory->create();
        $requestData = $this->getRequest()->getPost('data');
        $requestData = json_decode($requestData, true);
        $currentWebsiteId = $this->_storeManager->getStore()->getWebsiteId();
        $customerD = $this->customerFactory->create()
                        ->setWebsiteId($currentWebsiteId)->loadByEmail($requestData['email']);
        $customerId = $requestData['customer_id'];
        if (empty($customerD->getId()) || $customerD->getId() == $customerId) {
            try {
                $this->saveData($customerId, $currentWebsiteId, $requestData);
                $companyId = $requestData['loginData']['company_id'];
                $companyObj = $this->companyFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['eq' => $companyId])->getFirstItem();
                if ($companyObj && is_array($companyObj->getData())) {

                    $companyData = $companyObj->getData();
                    $sessionData = [];
                    $sessionData['company_id'] = $companyId;
					$sessionData['company_data'] = $companyData;
					$sessionData['ondemand_url'] = true;
					$sessionData['url_extension'] = true;
                    $sessionData['company_type'] = 'epro';
                    $this->customerSession->setOndemandCompanyInfo($sessionData);
                }
                return $this->enableSession($customerId, $requestData);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                return $resultJson->setData(['error' => 1, 'msg' => $e->getMessage(), 'case' => '2']);
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Email is already available with us, please use another email');
            return $resultJson->setData(['error' => 1,
                    'msg' => 'Email is already available with us, please use another email']);
        }
    }

    /**
     * Save customer
     *
     * @param Int   $customerId
     * @param Int   $currentWebsiteId
     * @param array $requestData
     *
     * @return Boolean|Exception
     */
    public function saveData($customerId, $currentWebsiteId, $requestData)
    {
        try {
            $customerD = $this->customerFactory->create()
                ->setWebsiteId($currentWebsiteId)->loadByEmail($requestData['email']);
            $customer = $this->_customerRepositoryInterface->getById($customerId);
            if ($customerD->getId() != $customerId) {
                $customer->setEmail($requestData['email']);
            }
            $currentStoreId = $this->_storeManager->getStore()->getId();
            $currentStoreName = $this->_storeManager->getStore()->getName();

            $customer->setStoreId($currentStoreId);
            $customer->setCreatedIn($currentStoreName);

            $customer->setFirstname($requestData['fname']); //set customer First Name
            $customer->setLastname($requestData['lname']);
            $customer->setCustomAttribute('contact_number', $requestData['phone']);
            $customer->setCustomAttribute('contact_ext', $requestData['ext']);
            $this->_customerRepositoryInterface->save($customer);
            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Set Customer Session
     *
     * @param Int   $customerId
     * @param array $requestData
     */
    public function enableSession($customerId, $requestData)
    {
	$resultJson = $this->resultJsonFactory->create();
        // clear old session
        $this->customerSession->logout()->setLastCustomerId($customerId);
        //generate new session
        $customerD = $this->customerFactory->create()->load($customerId);
        $this->customerSession->regenerateId();
        $this->customerSession->setCustomerAsLoggedIn($customerD);
        $this->customerSession->setCustomerCompany($requestData['loginData']['company_id']);
        $this->customerSession->setBackUrl($requestData['loginData']['redirect_url']);
        $this->customerSession->setCommunicationUrl($requestData['loginData']['response_url']);
        $this->customerSession->setCommunicationCookie($requestData['loginData']['cookie']);
        $this->customerSession->setCompanyName($requestData['loginData']['company_name']);
        $this->customerSession->setGatewayToken($requestData['loginData']['gatewayToken']);
        $this->customerSession->setApiAccessToken($requestData['loginData']['access_token']);
        $this->customerSession->setApiAccessType('Bearer');
        $publicCookieMetadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath("/")
            ->setHttpOnly(true)
            ->setDuration(time() + 86400)
            ->setSecure(true)
            ->setSameSite("None");
        $this->cookieManager->setPublicCookie(
            'PHPSESSID',
            $this->customerSession->getSessionId(),
            $publicCookieMetadata
        );
        if ($this->authHelper->isLoggedIn()) {
		return $resultJson->setData(['error' => 0, 'msg' => 'Updated']);
        } else {
            $this->logger->error(__METHOD__.':'.__LINE__.
            ' Contact Information Updated, unable to login. Please punchout again');
            return $resultJson->setData(['error' => 1,
            'msg' => 'Contact Information Updated, unable to login. Please punchout again']);
        }
    }
}
