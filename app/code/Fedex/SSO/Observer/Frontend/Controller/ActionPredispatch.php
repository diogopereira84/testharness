<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SSO\Observer\Frontend\Controller;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Login\Helper\Login;
use Fedex\SelfReg\Helper\SelfReg as SelfRegHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;

class ActionPredispatch implements ObserverInterface
{

    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    public function __construct(
        public LoggerInterface                                $logger,
        protected StoreManagerInterface                       $storeManager,
        protected Login                                       $login,
        protected ToggleConfig                                $toggleConfig,
        protected ActionFlag                                  $actionFlag,
        protected SessionFactory                              $sessionFactory,
        protected UrlInterface                                $urlInterface,
        protected AuthHelper                                  $authHelper,
        protected SelfRegHelper                               $selfRegHelper,
        protected CustomerSession                             $customerSession,
        private Context                                       $context,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        protected Http  $http
    ) {
    }

    /**
     * Execute observer
     *
     * @param  Observer $observer
     * @return ActionPredispatch
     */
    public function execute(Observer $observer)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_user_email_verification_redirect')) {
            $controllerAction = $observer->getControllerAction();
            $isValidAction = $controllerAction && method_exists($controllerAction, 'getResponse');

            if (!$isValidAction) {
                return $this;
            }
        }

        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator')) {
            $requestUrl = $this->http->getServer('REQUEST_URI');
            if ($requestUrl && str_contains($requestUrl, 'loginascustomer/login/index') && $this->customerSession->isLoggedIn()) {
                return;
            }
        }

        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            if(!$this->customerSession->isLoggedIn()) {
                $this->customerSession = $this->sessionFactory->create();
            }
            $customerSession = $this->customerSession;
        } else {
            $customerSession = $this->sessionFactory->create();
        }
        $isLoggedIn = $this->isLoggedin($customerSession);

        /**
         * B-1735489 start
         */
        if (!$isLoggedIn) {
            $this->login->handleCustomerSession();
        } else {
            $companyId = $this->login->getCompanyId();
            if (!$companyId && $this->storeManager->getStore()->getCode() == "default") {
                $customerSession->unsOndemandCompanyInfo();
                $customerSession->unsCustomerCompany();
            }
            else if($this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions')) {
                $permissions= $this->selfRegHelper->checkPermission($companyId);
                if(!empty($permissions)) {
                    $customerSession->setUserPermissionData(array_flip($permissions));
                }
                else
                {
                    $customerSession->setUserPermissionData(null);
                }
            }
            /**
             * Add Ondemand Company Info in Session if its empty B-1836797
             */
            if ($companyId && empty($customerSession->getOndemandCompanyInfo())
            ) {
                $ondemandData = $this->login->getOndemandCompanyData($companyId);
                $urlExtension = $ondemandData['company_data']['company_url_extention'] ?? null;
                $this->login->setUrlExtensionCookie($urlExtension);
                $customerSession->setOndemandCompanyInfo($ondemandData);
            }
            if ($companyId && $this->storeManager->getStore()->getCode() != "ondemand") {
                $onDemandStoreUrl = $this->login->getOndemandStoreUrl();
                $currentUrl = $this->urlInterface->getCurrentUrl();
                /**
                 * B-1846743 - Redirect to home page issue for cid psg url for comercial store
                 */
                if (str_contains($currentUrl, "/cid") || str_contains($currentUrl, "/psg")) {
                    $onDemandStoreUrl = str_replace("/default", "/ondemand", $currentUrl);
                }
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()->getResponse()->setRedirect($onDemandStoreUrl);

                return $this;
            }
            if (!$companyId && $this->storeManager->getStore()->getCode() != "default") {
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()->getResponse()->setRedirect($this->login->getRetailStoreUrl());
            }
            if ($this->storeManager->getStore()->getCode() == "ondemand" &&!$observer->getRequest()->isAjax()) {
                $urlExtension = $this->login->getUrlExtensionCookie();
                $currentUrl = $this->urlInterface->getCurrentUrl();
                if (!str_contains($currentUrl, $urlExtension . "/")) {
                    $currentUrl = str_replace("/ondemand", "/ondemand/" . $urlExtension, $currentUrl);
                    $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                    $observer->getControllerAction()->getResponse()->setRedirect($currentUrl);
                }
            }
        }

        return $this;
    }

    private function isLoggedin($customerSession)
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $return = $this->authHelper->isLoggedIn();
        return $return;
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
}
