<?php
namespace Fedex\EnhancedProfile\Block;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Block\Account\SortLinkInterface;
use Fedex\Commercial\Helper\CommercialHelper;

class SharedOrderHistory extends HtmlLink implements SortLinkInterface
{
    /**
     * @var Fedex\Delivery\Helper\Data $helperData
     */
    protected $helperData;

    /**
     * @param Context $context
     * @param Data|null $helperData
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helperData,
        protected UrlInterface $urlBuilder,
        public CommercialHelper $commercialHelper,
        array $data = []
    ) {
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    protected function _toHtml()
    {
        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentClass = '';
        $currentPage = false;
        $hasShareOrderPermission= false;
        $isRolesAndPermissionEnabled = $this->helperData->getToggleConfigurationValue('change_customer_roles_and_permissions');

        if ($isRolesAndPermissionEnabled) {
            $hasShareOrderPermission = $this->helperData->checkPermission('shared_orders');
        }
        if ($this->helperData->isCustomerAdminUser() || $hasShareOrderPermission) {
            if ($currentUrl !== null && strpos($currentUrl, '/shared/order/history') !== false) {
                $currentClass .= ' current';
                $currentPage = true;
            }
            $rolesandpermissionenabled = $this->commercialHelper->isRolePermissionToggleEnable();

                if($rolesandpermissionenabled) {
                    if ($currentPage) {
                        return '<li class="nav item' . $currentClass . '"><strong data-test-id="E-404291-B-2010873-TK-3436949-manage-user-action-sharedorders">' . $this->escapeHtml($this->getLabel()) . '</strong></li>';
                    } else {
                        return '<li class="nav item' . $currentClass . '"><a ' . $this->getLinkAttributes()
                    . ' data-test-id="E-404291-B-2010873-TK-3436949-manage-user-action-sharedorders">' . $this->escapeHtml($this->getLabel()) . '</a></li>';
                    }
                } else {
                    if ($currentPage) {
                        return '<li class="nav item' . $currentClass . '"><strong>' . $this->escapeHtml($this->getLabel()) . '</strong></li>';
                    } else {
                        return '<li class="nav item' . $currentClass . '"><a ' . $this->getLinkAttributes()
                    . '>' . $this->escapeHtml($this->getLabel()) . '</a></li>';
                    }
                }
        }
    }
}
