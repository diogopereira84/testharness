<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
namespace Fedex\SelfReg\Observer\Frontend\Company;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\SelfReg\Block\Landing;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use \Psr\Log\LoggerInterface;

/**
 * Class WlgnIntegration
 * B-1320022 - WLGN integration for selfReg customer
 *
 * @package Fedex\SelfReg\Observer\Frontend\Page
 */
class WlgnIntegration implements ObserverInterface
{
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    public const MAZEGEEK_D_226789 = 'mazegeeks_d226789_quote_link_redirection_from_email';

    /**
     * @var CompanyFactory $companyFactory
     */
    protected $companyFactory;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * Constructor
     *
     * @param SessionFactory $customerSessionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param UrlInterface $url
     * @param ToggleConfig $toggleConfig
     * @param SelfReg $selfRegHelper
     * @param Ondemand $ondemand
     * @param SdeHelper $sdeHelper
     * @param ForwardFactory $forwardFactory
     * @param Landing $selfRegLanding
     * @param Http $http
     * @param AuthHelper $authHelper
     * @param Session $session
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private SessionFactory        $customerSessionFactory,
        protected StoreManagerInterface $storeManagerInterface,
        protected UrlInterface          $url,
        protected ToggleConfig          $toggleConfig,
        private SelfReg               $selfRegHelper,
        protected Ondemand              $ondemand,
        private SdeHelper             $sdeHelper,
        private ForwardFactory        $forwardFactory,
        private Landing               $selfRegLanding,
        private Http                  $http,
        protected AuthHelper            $authHelper,
        private Session               $session,
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected LoggerInterface        $logger
    )
    {
    }

    /**
     * Execute Method
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $observerRequest = $observer->getEvent()->getRequest();
        
        // Check if this is a postdispatch or customer login event
        $eventName = $observer->getEvent()->getName();
        $isPostDispatch = ($eventName === 'controller_action_postdispatch');
        $isCustomerLogin = ($eventName === 'customer_login');

        //B-1515570
        $moduleName = $observerRequest->getModuleName();
        $controllerName = $observerRequest->getControllerName();

        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customerSession = $this->getOrCreateCustomerSession();
        } else {
            $customerSession = $this->customerSessionFactory->create();
        }
        $storeObj = $this->storeManagerInterface->getStore();
        $storeCode = $storeObj->getCode();

        $storeGroupCode = $this->storeManagerInterface->getGroup()->getCode();

        if ($storeCode == 'default') {
            return $this;
        }

        // Handle email quote redirect in postdispatch OR customer login events
        if ($isPostDispatch || $isCustomerLogin) {
            $this->handleEmailQuoteRedirect($customerSession, $observerRequest, $storeGroupCode, $observer);
            return $this;
        }

         
        if ($moduleName != "punchout") {
            $redirectUrl = $this->getStoreRestructureUrl($customerSession, $observerRequest, $storeCode);

            if($redirectUrl){
                $this->redirect($observer, $redirectUrl);
                return $observer;
            }
        }
        $isSelfRegCompany = $this->selfRegHelper->isSelfRegCompany();
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();

        /*B-1473165*/
        $skipModulename = ['loginascustomer','selfreg','customer', 'restructure', 'login','oauth'];

        if ($isSelfRegCompany && $isSelfRegCustomer) {
            return $this;
        } elseif ($isSelfRegCompany && !in_array($moduleName, $skipModulename)) {
            if(!$this->session->isLoggedIn()){
                $this->session = $this->customerSessionFactory->create();
            }
            $customerSession = $this->session;
            $companyData = $customerSession->getOndemandCompanyInfo();

            $companyDataCheck = ($companyData && is_array($companyData) &&
                !empty($companyData['url_extension']) &&
                !empty($companyData['company_type']) &&
                !empty($companyData['company_data']['storefront_login_method_option']) &&
                    $companyData['company_data']['storefront_login_method_option'] == 'commercial_store_wlgn')?true:false;

                if ($companyDataCheck && $companyData['company_type']=='sde') {
                    return $this;
                }

            if ($companyDataCheck) {
                if ($this->authHelper->isLoggedIn()) {
                    $customerId = $customerSession->getCustomer()->getId();
                    $customerSession->logout()->setLastCustomerId($customerId);
                }

                $redirectUrl = $this->url->getUrl('selfreg/landing');
                $controllerAction = $observer->getControllerAction();
                if ($controllerAction && method_exists($controllerAction, 'getResponse')) {
                    $controllerAction->getResponse()->setRedirect($redirectUrl);
                }
            }

        }
    }

    /**
     * @inheritDoc
     *
     * B-1602925
     */
    public function getStoreRestructureUrl($customerSession, $observerRequest, $storeCode)
    {
        $redirectUrl = null;
        $storeGroupCode = $this->storeManagerInterface->getGroup()->getCode();
        $fullAction = $observerRequest->getFullActionName();
        
        // Check for email quote cookie FIRST - highest priority
        if($this->getToggleForD226789()){
            $cookieSet = null;
            $cookieSet = $this->getEmailLinkCookie();
            // For post-login redirects, check if user has valid session (customer ID) instead of full auth check
            $hasValidSession = $customerSession && $this->isCustomerSessionValid($customerSession);
            
            // If user is NOT logged in and accessing quote page, set the cookie for later redirect
            if($storeGroupCode == 'ondemand' && !$hasValidSession && $fullAction == 'uploadtoquote_index_view') {
                $currentUrl = $observerRequest->getUriString();
                if ($currentUrl && strpos($currentUrl, 'uploadtoquote/index/view') !== false) {
                    // Set the cookie with current URL for later redirect
                    $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                    $cookieMetadata->setDurationOneYear();
                    $cookieMetadata->setPath('/');
                    $cookieMetadata->setHttpOnly(false);
                    $this->cookieManager->setPublicCookie('emailhitquote', $currentUrl, $cookieMetadata);
                }
            }
            
            // Handle email quote redirection directly in predispatch when user is logged in
            // and on the homepage (cms_index_index)
            if($storeGroupCode == 'ondemand' && $hasValidSession && $cookieSet != null && $fullAction == 'cms_index_index') {
                // Delete the cookie first
                $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
                $this->cookieManager->deleteCookie('emailhitquote', $cookieMetadata);
                
                // Return direct redirect URL for immediate processing
                $redirectUrl = $cookieSet;
                return $redirectUrl;
            }
            
            // DO NOT redirect in other predispatch cases - only set/check cookies
            // Redirection will be handled above for homepage or in postdispatch for other pages
            
        }

        if ($storeGroupCode == 'sde_store' && $this->authHelper->isLoggedIn()) {
            $redirectUrl = $this->ondemand->getOndemandStoreUrl();
            return $redirectUrl;
        }

        if ($storeGroupCode != 'ondemand') {
            $redirectUrl = $this->ondemand->getOndemandStoreUrl() . strtolower($storeCode);
        }
        if($storeGroupCode == 'ondemand' && !$this->authHelper->isLoggedIn() && $fullAction == 'cms_index_index') {
            $redirectUrl = $this->selfRegLanding->getLoginUrl();
        }
        
        return $redirectUrl;
    }

    /**
     * Handle Email Quote Redirect during postdispatch
     * @param $customerSession
     * @param $observerRequest  
     * @param $storeGroupCode
     * @param $observer
     */
    private function handleEmailQuoteRedirect($customerSession, $observerRequest, $storeGroupCode, $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $fullAction = $observerRequest ? $observerRequest->getFullActionName() : 'no_request';
        
        // Check for email quote cookie redirect
        if($this->getToggleForD226789()){
            $cookieSet = $this->getEmailLinkCookie();
            $hasValidSession = $customerSession && $this->isCustomerSessionValid($customerSession);
            
            if($storeGroupCode == 'ondemand' && $hasValidSession && $cookieSet != null) {
                // For customer_login event, use a different redirect approach
                if ($eventName === 'customer_login') {
                    // Use session to store redirect URL
                    $this->session->setEmailQuoteRedirectUrl($cookieSet);
                    // Delete the cookie
                    $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
                    $this->cookieManager->deleteCookie('emailhitquote', $cookieMetadata);
                    return;
                }
                // For postdispatch, use JavaScript redirect
                $controllerAction = $observer->getControllerAction();
                if ($controllerAction && method_exists($controllerAction, 'getResponse')) {
                    $response = $controllerAction->getResponse();
                    $response->clearHeaders();
                    // Set JavaScript redirect with immediate execution
                    $redirectScript = '<script type="text/javascript">window.location.href = "' . addslashes($cookieSet) . '";</script>';
                    $response->setBody($redirectScript);
                    $response->sendHeaders();
                    // Delete the cookie after successful redirect
                    $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
                    $this->cookieManager->deleteCookie('emailhitquote', $cookieMetadata);
                    // Prevent further processing
                    exit();
                }
            }
            
            if($storeGroupCode == 'ondemand' && $this->authHelper->isLoggedIn() && $fullAction == 'cms_index_index' && $cookieSet != null) {
                $redirectUrl = $cookieSet;
                $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
                $this->cookieManager->deleteCookie('emailhitquote', $cookieMetadata);
            }
        }
    }

    /**
     * @inheritDoc
     *
     * B-1515570
     */
    public function redirect($observer, $redirectUrl)
    {
        $controllerAction = $observer->getControllerAction();
        if ($controllerAction && method_exists($controllerAction, 'getResponse')) {
            $controllerAction->getResponse()->setRedirect($redirectUrl);
        } else {
            file_put_contents(BP . '/var/log/quote_debug.log', date('Y-m-d H:i:s') . " - NO CONTROLLER ACTION OR getResponse method available\n", FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Get Email Link Cookie
     * @return string|null
     */
    public function getEmailLinkCookie()
    {
        return $this->cookieManager->getCookie(
            'emailhitquote'
        );
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
     * Toggle for Email Link Redirection from Email
     * @return bool
     */
    public function getToggleForD226789()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::MAZEGEEK_D_226789);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->session->isLoggedIn()){
            $this->session = $this->customerSessionFactory->create();
        }
        return $this->session;
    }

    /**
     * Safely check if customer session is valid to avoid mock object issues in tests
     * @param $customerSession
     * @return bool
     */
    private function isCustomerSessionValid($customerSession)
    {
        try {
            // Check if customer session has a customer first
            if (!$customerSession || !method_exists($customerSession, 'getCustomerId')) {
                return $this->authHelper->isLoggedIn();
            }
            return $customerSession->getCustomerId() || $this->authHelper->isLoggedIn();
        } catch (\Throwable $e) {
            return $this->authHelper->isLoggedIn();
        }
    }

}
