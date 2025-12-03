<?php
/**
 * @package    Fedex_SharedDetails
 */
namespace Fedex\SharedDetails\Controller\Order;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\Ondemand\Model\Config;

class History extends \Magento\Framework\App\Action\Action implements OrderInterface, HttpGetActionInterface
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'environment_toggle_configuration/environment_toggle/sgc_b_2107362';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $deliveryDataHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param Config $config
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        private Data $deliveryDataHelper,
        private ScopeConfigInterface $scopeConfig,
        private UrlInterface $url,
        public Config $config
    ) {
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $hasShareOrderPermission= false;
        $isRolesAndPermissionEnabled = $this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions');
        if ($isRolesAndPermissionEnabled) {
            $hasShareOrderPermission = $this->deliveryDataHelper->checkPermission('shared_orders');
        }
        if ($this->deliveryDataHelper->isCompanyAdminUser() || $hasShareOrderPermission) {
            $resultPage = $this->resultPageFactory->create();
            $isUpdateTabNameToggleEnabled = $this->scopeConfig->getValue(
                self::SGC_TAB_NAME_UPDATES,
                ScopeInterface::SCOPE_STORE
            );

            if ($isUpdateTabNameToggleEnabled) {
                $tabNameTitle = $this->config->getMyAccountTabNameValue();
            } else {
                $tabNameTitle = $this->config->getSharedOrdersTabNameValue();
            }
            $resultPage->getConfig()->getTitle()->set(__($tabNameTitle));

            return $resultPage;
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
