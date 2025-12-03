<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Commercial\Plugin\Controller\Users;

use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\UrlInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\ResponseFactory;
use Fedex\Ondemand\Model\Config;

/**
 * Class Plugin Index
 */
class Index
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'environment_toggle_configuration/environment_toggle/sgc_b_2107362';

    /**
     * @var RedirectFactory $responseFactory
     */
    protected $responseFactory;
    
    /**
     * Initializing Constructor
     *
     * @param DeliveryDataHelper $deliveryDataHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param CommercialHelper $commercialHelper
     * @param CompanyContext $companyContext
     * @param ResponseFactory $responseFactory
     * @param Config $config
     */
    public function __construct(
        protected DeliveryDataHelper $deliveryDataHelper,
        protected ScopeConfigInterface $scopeConfig,
        protected UrlInterface $url,
        protected CommercialHelper $commercialHelper,
        protected CompanyContext $companyContext,
        ResponseFactory $responseFactory,
        public Config $config
    ) {
        $this->responseFactory = $responseFactory;
    }
    
    /**
     * Redirect to user to no route page if user don't have permission to manage user
     *
     * @param Object $subject
     * @return void
     */
    public function beforeExecute($subject)
    {
        if (!($this->deliveryDataHelper->isCompanyAdminUser() || $this->hasManagerUserPermission())
        ) {
            $this->getNoRoutePage();
        }
    }

    /**
     * Change title of page if user has permission to magange user
     *
     * @param Object $subject
     * @param Object $result
     * @return object
     */
    public function afterExecute($subject, $result)
    {
        if ($this->companyContext->getCustomerId()) {
            if ($this->commercialHelper->isSelfRegAdminUpdates() || $this->hasManagerUserPermission()) {
                $isUpdateTabNameToggleEnabled = $this->scopeConfig->getValue(
                    self::SGC_TAB_NAME_UPDATES,
                    ScopeInterface::SCOPE_STORE
                );

                if ($isUpdateTabNameToggleEnabled) {
                    $tabNameTitle = $this->config->getMyAccountTabNameValue();
                    $pageMainTitle = $result->getLayout()->getBlock('page.main.title');
                    if ($pageMainTitle) {
                        $pageNameTitle = $this->config->getManageUsersTabNameValue();
                        $pageMainTitle->setPageTitle($pageNameTitle);
                    }
                } else {
                    $tabNameTitle = $this->config->getManageUsersTabNameValue();
                }
                $result->getConfig()->getTitle()->set(__($tabNameTitle));
            }
        } else {
            $this->getNoRoutePage();
        }
        
        return $result;
    }

    /**
     * Check logged in user has permission to manage user
     *
     * @return boolena
     */
    public function hasManagerUserPermission()
    {
        $hasManagerUserPermission = false;
        $isRolesAndPermissionEnabled = $this->deliveryDataHelper
        ->getToggleConfigurationValue('change_customer_roles_and_permissions');
        $customerModel = $this->deliveryDataHelper->getCustomer();
        if ($isRolesAndPermissionEnabled) {
            $hasManagerUserPermission = $this->deliveryDataHelper->checkPermission('manage_users');
        }

        return $hasManagerUserPermission;
    }

    /**
     * Redirect to no route page
     *
     * @return boolean
     */
    public function getNoRoutePage()
    {
        $defaultNoRouteUrl = $this->scopeConfig->getValue(
            'web/default/no_route',
            ScopeInterface::SCOPE_STORE
        );
        $redirectUrl = $this->url->getUrl($defaultNoRouteUrl);
        $this->responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
    }
}
