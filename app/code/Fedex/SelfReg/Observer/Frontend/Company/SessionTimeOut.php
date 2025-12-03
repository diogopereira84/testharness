<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\SelfReg\Observer\Frontend\Company;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SessionTimeout
 *
 * @package Fedex\SelfReg\Observer\Frontend\Page
 */
class SessionTimeOut implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManagerInterface
     * @param CompanyFactory $companyFactory
     * @param Session $customerSession
     * @param UrlInterface $url
     * @param ToggleConfig $toggleConfig
     * @param SdeHelper $sdeHelper
     * @param AuthHelper $authHelper
     */
    public function __construct(
        protected \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        protected \Magento\Company\Model\CompanyFactory $companyFactory,
        protected \Magento\Customer\Model\Session $customerSession,
        protected \Magento\Framework\UrlInterface $url,
        protected ToggleConfig $toggleConfig,
        protected SdeHelper $sdeHelper,
        protected AuthHelper $authHelper
    )
    {
    }

    /**
     * Execute Method
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $moduleName = $observer->getEvent()->getRequest()->getModuleName();
        $fullActionName = $observer->getEvent()->getRequest()->getFullActionName();
        $storeObj = $this->storeManagerInterface->getStore();
        $storeCode = $storeObj->getCode();
        $moduleExclude = ['punchout'];
        /* B-1326203 | B-1473165 */
        $excludedUrl = ['loginascustomer_login_index', 'selfreg_login_fail', 'selfreg_landing_index',
							'ondemand_update_customer', 'ondemand_update_quote', 'ondemand_update_sales'];
        if (
            !$this->authHelper->isLoggedIn() && $storeCode != 'default'
            && !in_array($moduleName, $moduleExclude)
            && !in_array($fullActionName, $excludedUrl)
            && !$this->sdeHelper->getIsSdeStore()
        ) {
            $baseUrl = $storeObj->getBaseUrl();
            $currentUrl = $this->url->getCurrentUrl();
            $sessionTimeoutUrl = $storeObj->getUrl('session-timeout');
            $successPageUrl = $storeObj->getUrl('success');
            $baseUrl = rtrim($baseUrl, '/');

            $restrictedUrls = [
                rtrim($sessionTimeoutUrl, '/'),
                rtrim($successPageUrl, '/'),
            ];

            $companyObj = $this->companyFactory->create()->getCollection()
                ->addFieldToFilter('company_url', $baseUrl)->getFirstItem();
            if ($companyObj->getId() && !in_array(rtrim($currentUrl, '/'), $restrictedUrls)) {
                $observer->getControllerAction()
                    ->getResponse()
                    ->setRedirect($sessionTimeoutUrl);
            }
        }
    }
}
