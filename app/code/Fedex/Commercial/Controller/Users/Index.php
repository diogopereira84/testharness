<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Commercial\Controller\Users;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\UrlInterface;
use Fedex\Ondemand\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Class Index.
 */
class Index extends \Magento\Company\Controller\Users\Index
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_view';

    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'environment_toggle_configuration/environment_toggle/sgc_b_2107362';

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param LoggerInterface $logger
     * @param CommercialHelper $helperData
     * @param Data $deliveryDataHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param Config $config
     */
    public function __construct(
        Context                    $context,
        CompanyContext             $companyContext,
        LoggerInterface            $logger,
        protected CommercialHelper $helperData,
        private Data               $deliveryDataHelper,
        private ScopeConfigInterface $scopeConfig,
        private UrlInterface        $url,
        public Config              $config
    ) {
        parent::__construct($context, $companyContext, $logger);
    }
    public function execute()
    {
        if (!$this->deliveryDataHelper->getToggleConfigurationValue('xmen_remove_adobe_commerce_override')) {
            $hasManagerUserPermission = false;
            $isRolesAndPermissionEnabled = $this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions');
            $customerModel = $this->deliveryDataHelper->getCustomer();
            if ($isRolesAndPermissionEnabled) {
                $hasManagerUserPermission = $this->deliveryDataHelper->checkPermission('manage_users');
            }
            if (!($this->deliveryDataHelper->isCompanyAdminUser() || $hasManagerUserPermission)) {
                $defaultNoRouteUrl = $this->scopeConfig->getValue(
                    'web/default/no_route',
                    ScopeInterface::SCOPE_STORE
                );
                $redirectUrl = $this->url->getUrl($defaultNoRouteUrl);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($redirectUrl);
                return $resultRedirect;
            }
            $isSelfRegAdminUpdates = $this->helperData->isSelfRegAdminUpdates();
            if ($this->companyContext->getCustomerId()) {
                /** @var \Magento\Framework\View\Result\Page $resultPage */
                $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
                if ($isSelfRegAdminUpdates || $hasManagerUserPermission) {
                    $isUpdateTabNameToggleEnabled = $this->scopeConfig->getValue(
                        self::SGC_TAB_NAME_UPDATES,
                        ScopeInterface::SCOPE_STORE
                    );

                    if ($isUpdateTabNameToggleEnabled) {
                        $title = $this->config->getMyAccountTabNameValue();
                        $pageMainTitle = $resultPage->getLayout()->getBlock('page.main.title');
                        if ($pageMainTitle) {
                            $pageNameTitle = $this->config->getManageUsersTabNameValue();
                            $pageMainTitle->setPageTitle($pageNameTitle);
                        }
                    } else {
                        $title = $this->config->getManageUsersTabNameValue();
                    }
                } else {
                    $title = $this->config->getCompanyUsersTabNameValue();
                }
                $resultPage->getConfig()->getTitle()->set(__($title));
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
            return $resultPage;
        } else {
            return parent::execute();
        }
    }
}
