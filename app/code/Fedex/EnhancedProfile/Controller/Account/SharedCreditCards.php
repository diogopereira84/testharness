<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnhancedProfile\Controller\Account;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Controller\AbstractAccount;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\Ondemand\Model\Config;

/**
 * SharedCreditCards Controller class
 */
class SharedCreditCards extends AbstractAccount
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'environment_toggle_configuration/environment_toggle/sgc_b_2107362';

    /**
     * Initialize dependencies.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $deliveryDataHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param RedirectFactory $resultRedirectFactory
     * @param EnhancedProfile $enhancedProfile
     * @param Config $config
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        private Data $deliveryDataHelper,
        private ScopeConfigInterface $scopeConfig,
        private UrlInterface $url,
        RedirectFactory $resultRedirectFactory,
        protected EnhancedProfile $enhancedProfile,
        public Config $config
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Account & Credit Card information
     *
     * @return void
     */
    public function execute()
    {
        $hasShareCreditPermission = false;
        $isRolesAndPermissionEnabled = $this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions');
        if ($isRolesAndPermissionEnabled) {
            $hasShareCreditPermission = $this->deliveryDataHelper->checkPermission('shared_credit_cards');
        }
        if ($this->deliveryDataHelper->isCompanyAdminUser() || $hasShareCreditPermission) {
            if ($this->enhancedProfile->isCompanySettingToggleEnabled()) {
                $pageFactory = $this->resultPageFactory->create();
                $isUpdateTabNameToggleEnabled = $this->scopeConfig->getValue(
                    self::SGC_TAB_NAME_UPDATES,
                    ScopeInterface::SCOPE_STORE
                );

                if ($isUpdateTabNameToggleEnabled) {
                    $tabNameTitle = $this->config->getMyAccountTabNameValue();
                } else {
                    $tabNameTitle = $this->config->getSitePaymentsTabNameValue();
                }
                $pageFactory->getConfig()->getTitle()->set($tabNameTitle);

                return $pageFactory;
            }

            return $this->resultPageFactory->create();
        } else {
            $defaultNoRouteUrl = $this->scopeConfig->getValue(
                'web/default/no_route',
                ScopeInterface::SCOPE_STORE
            );
            $redirectUrl = $this->url->getUrl($defaultNoRouteUrl);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($redirectUrl);
            return $resultRedirect;
        }
    }
}
