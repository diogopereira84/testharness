<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Company;

use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Action\Context;
use Fedex\Ondemand\Helper\Ondemand;
use Magento\Customer\Model\SessionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\SelfReg\Block\Landing;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Fedex\Company\Model\CompanyData;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\App\Request\Http;
use Fedex\NotificationBanner\ViewModel\NotificationBanner;
use Fedex\SDE\Model\Customer as SdeCustomerModel;
use Fedex\Login\Helper\Login;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Customer\Model\Session;

class Redirect extends \Magento\Framework\App\Action\Action
{
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    /**
     * @param Context $context
     * @param Ondemand $ondemandHelper
     * @param SessionFactory $customerSessionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param Landing $selfRegLanding
     * @param SdeHelper $sdeHelper
     * @param RedirectFactory $resultRedirectFactory
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param UrlInterface $urlInterface
     * @param CompanyData $companyData
     * @param SsoConfiguration $ssoConfiguration
     * @param Http $request
     * @param NotificationBanner $notificationBanner
     * @param SdeCustomerModel $sdeCustomerModel
     * @param Login $loginHelper
     * @param AuthHelper $authHelper
     */
    public function __construct(
        Context $context,
        private Ondemand $ondemandHelper,
        private SessionFactory $customerSessionFactory,
        private StoreManagerInterface $storeManagerInterface,
        private Landing $selfRegLanding,
        private SdeHelper $sdeHelper,
        RedirectFactory $resultRedirectFactory,
        private LoggerInterface $logger,
        private ToggleConfig $toggleConfig,
        private UrlInterface $urlInterface,
        private CompanyData $companyData,
        private SsoConfiguration $ssoConfiguration,
        private Http $request,
        private NotificationBanner $notificationBanner,
        private SdeCustomerModel $sdeCustomerModel,
        private Login $loginHelper,
        protected AuthHelper $authHelper,
        private Session $session
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action of Pickup Address
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
		$resultRedirect = $this->resultRedirectFactory->create();
		$storeObj = $this->storeManagerInterface->getStore();
		$storeCode = $storeObj->getCode();

        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            if(!$this->session->isLoggedIn()){
                $this->session = $this->customerSessionFactory->create();
            }
            $customerSession = $this->session;
        } else {
            $customerSession = $this->customerSessionFactory->create();
        }

		if ($storeCode != 'ondemand') {
            $this->logger->info('Ondemand controller hit : Storeview Restructure Toggle is Disable.');

            $norouteUrl = $this->urlInterface->getUrl('noroute');
			$this->getResponse()->setRedirect($norouteUrl);
            return false;
        }

        try {
			if (!$this->authHelper->isLoggedIn()) {
                $companyData = $this->ondemandHelper->getOndemandCompanyData();
                if(!empty($this->toggleConfig->getToggleConfigValue('tech_titans_D_230789_bookmarking_site_fix'))){
                $currentUrl = $this->urlInterface->getCurrentUrl();
                $parseUrl = parse_url($currentUrl);
                $parseUrl = explode('/',$parseUrl['path']);
                $this->logger->info('ParseUrl ' . print_r($parseUrl, true));
                if(isset($parseUrl[6])) {
                    $urlExtensionByURL = $parseUrl[6];
                    $this->logger->info('From URL -  UrlExtension: ' . $urlExtensionByURL);
                    $companyFromUrlExtension = $this->ondemandHelper->getCompanyFromUrlExtension($urlExtensionByURL);
                    $this->logger->info('Get Company From URL -  UrlExtension: ' . print_r($companyFromUrlExtension['company_url_extention'], true));
                    if (!empty($companyFromUrlExtension)) {
                        $this->loginHelper->setUrlExtensionCookie($urlExtensionByURL);  
                        }
                    }
                } else {                
                        $urlExtension = $companyData['company_data']['company_url_extention'] ?? null;
                        if (!empty($urlExtension)) {
                            $this->loginHelper->setUrlExtensionCookie($urlExtension);
                        } else {
                            $redirectUrl = $this->selfRegLanding->getLoginUrl();
                            $resultRedirect->setUrl($redirectUrl);
                            return $resultRedirect;
                        }   
                    }

				if ($companyData && is_array($companyData) &&
					$companyData['ondemand_url'] &&
					!$companyData['url_extension']
				) {
					$redirectUrl = $this->selfRegLanding->getLoginUrl();
					$resultRedirect->setUrl($redirectUrl);
				}

				if ($companyData && is_array($companyData) &&
					!empty($companyData['url_extension']) &&
					!empty($companyData['company_type'])
				) {
					$this->customerSessionFactory->create()->setOndemandCompanyInfo($companyData);

                    if ($this->isStoreFrontDataSet()) {
                        $this->redirectToStoreFront();
                        return false;
                    } else {
    					if ($companyData['company_type'] == 'selfreg') {
    						$resultRedirect->setUrl('selfreg/landing');
    					} elseif ($companyData['company_type'] == 'sde') {
    						$redirectionUrl = $companyData['company_data']['sso_login_url'] ?? $this->sdeHelper->getSsoLoginUrl();
    						$resultRedirect->setUrl($redirectionUrl);
    					}
                    }
				}
			}else{
				$redirectUrl = $this->ondemandHelper->getOndemandStoreUrl();
				$resultRedirect->setUrl($redirectUrl);
			}
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());

            $norouteUrl = $this->urlInterface->getUrl('noroute');
			$this->getResponse()->setRedirect($norouteUrl);
            return false;
        }
        return $resultRedirect;
    }

    /**
     * isStoreFrontDataSet
     * @return boolean
     */
    public function isStoreFrontDataSet(): bool
    {
        $loginMethod = $this->authHelper->getCompanyAuthenticationMethod();
        return ($loginMethod == AuthHelper::AUTH_FCL || $loginMethod == AuthHelper::AUTH_SSO ||
            ($this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method')
                && $loginMethod == AuthHelper::AUTH_SSO_FCL));
    }

    /**
     * redirectToWlgn
     * @return boolean
     */
    public function redirectToStoreFront()
    {
        $companyData = $this->ondemandHelper->getOndemandCompanyData();
        $loginMethod = $companyData['company_data']['storefront_login_method_option']!=null?$companyData['company_data']['storefront_login_method_option']:'';
        if ($loginMethod == 'commercial_store_wlgn') {
            $commercialLandingPageFixToggle = $this->toggleConfig->getToggleConfigValue('explorers_d_197790_commercial_landing_page_fix');
            if ($commercialLandingPageFixToggle) {
                $urlExtension = $this->getRequest()->getParam('url');
                $redirectUrl = $this->urlInterface->getUrl($urlExtension.'/selfreg/landing');
            } else {
                $redirectUrl = $this->urlInterface->getUrl('selfreg/landing');
            }
            $this->wlgnRedirect($redirectUrl);
        } elseif ($loginMethod == 'commercial_store_sso' ||
        ($this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method')
        && $loginMethod == 'commercial_store_sso_with_fcl')) {
            $redirectUrl = (isset($companyData['company_data']['sso_login_url'])
                && $companyData['company_data']['sso_login_url'] != '')?
                $companyData['company_data']['sso_login_url']:'';
            $this->ssoRedirect($redirectUrl);
        }
        return true;
    }

    /**
     * ssoRedirect
     * @param  string $redirectUrl
     * @return boolean
     */
    public function ssoRedirect($redirectUrl)
    {
        $this->getResponse()->setRedirect($redirectUrl);
    }

    /**
     * wlgnRedirect
     * @param  string $redirectUrl
     * @return boolean
     */
    public function wlgnRedirect($redirectUrl)
    {
        if ($redirectUrl != '') {
            $this->getResponse()->setRedirect($redirectUrl);
        }
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }
}
