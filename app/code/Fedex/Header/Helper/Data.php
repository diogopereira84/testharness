<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Header\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Context as CustomerModelContext;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\SessionFactory;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Customer\Model\Session;

/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data extends AbstractHelper
{

    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    public const EXPLORERS_D_193926_FIX = 'explorers_d_193926_fix';
    /**
     * Data Constructor
     *
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param HttpContext $httpContext
     * @param LoggerInterface $logger
     * @param SessionFactory $customerSession
     * @param ToggleConfig $toggleConfig
     * @param AuthHelper $authHelper
     * @param Session $session
     */
    public function __construct(
        Context $context,
        protected CustomerFactory $customerFactory,
        protected CustomerRepositoryInterface $customerRepositoryInterface,
        protected HttpContext $httpContext,
        protected LoggerInterface $logger,
        protected SessionFactory $customerSession,
        protected ToggleConfig $toggleConfig,
        protected AuthHelper $authHelper,
        private Session $session
    ) {
        parent::__construct($context);
    }

    /**
     * Check is loggedIn
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        return (bool) $this->httpContext->getValue(CustomerModelContext::CONTEXT_AUTH);
    }

    /**
     * Get link for ERP Homepage
     *
     * @return string $backurl
     */
    public function getLink()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customer = $this->getOrCreateCustomerSession();
        } else {
            $customer = $this->customerSession->create();
        }
        return $customer->getBackUrl() ? $customer->getBackUrl() : "";
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        if (($this->getLink()===null) || $this->getLink() == "") {
            return "";
        } else {
            return "Back to eProcurement";
        }
    }

    /**
     * Get customer by id
     *
     * @param int $customerId
     * @return object|boolean $customer|false
     */
    public function getCustomer($customerId)
    {
        try {
            return $this->customerFactory->create()->load($customerId);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Cannot get customer by id: ' . $customerId);
            return false;
        }
    }

    /**
     * Get LoggedIn User Name
     *
     * @return string $loginUserName
     */
    public function getLoginUserName()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSession->create();
        }

        if ($this->authHelper->isLoggedIn()) {
            $id = $customerSession->getId();
            if (!empty($id)) {
                $customer = $this->customerRepositoryInterface->getById($id);
                $loginUserName = trim((string)$customer->getFirstName() . ' ' . $customer->getLastName());
                if (!empty($loginUserName)) {
                    return $loginUserName;
                } else {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Cannot get login user name.');
                }
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Cannot get login user name.');
            }
        }
    }

    /**
     * Return the auth header value
     * @return string
     */
    public function getAuthHeaderValue(): string
    {
        $headerVal = "Authorization: Bearer ";
        if($this->toggleConfig->getToggleConfigValue('E352723_use_clientId_header')){
            $headerVal =  "client_id: ";
        }
        return $headerVal;
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
        if(!$this->session->isLoggedIn()){
            $this->session = $this->customerSession->create();
        }
        return $this->session;
    }

    /**
     * Toggle for D-193926 Fix
     * @return bool
     */
    public function getToggleD193926Fix()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D_193926_FIX);
    }
}
