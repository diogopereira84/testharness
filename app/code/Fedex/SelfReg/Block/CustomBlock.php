<?php
namespace Fedex\SelfReg\Block;

use Fedex\Delivery\Helper\Data;
use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Fedex\Commercial\Helper\CommercialHelper;

class CustomBlock extends HtmlLink
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
    protected function _toHtml()
    {
        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentClass = '';
        $hasShareCreditCardPermission = false;
        $isRolesAndPermissionEnabled = $this->helperData->getToggleConfigurationValue('change_customer_roles_and_permissions');
        if ($isRolesAndPermissionEnabled) {
            $hasShareCreditCardPermission= $this->helperData->checkPermission('shared_credit_cards');
        }

        $isCompanySettingsToggleEnable = $this->commercialHelper->isCompanySettingsToggleEnable();
        if ($this->helperData->isCompanyAdminUser() || ($hasShareCreditCardPermission)) {
            if ($currentUrl !== null && strpos($currentUrl, 'sharedcreditcards')) {
                $currentClass .= ' current';
            }
            $rolesandpermissionenabled = $this->commercialHelper->isRolePermissionToggleEnable();
            if ($isCompanySettingsToggleEnable) {
                $label = __('Site Level Payments');
            } else {
                $label = $this->getLabel();
            }

            if($rolesandpermissionenabled) {
                return '<li class="nav item' . $currentClass . '"><a ' . $this->getLinkAttributes()
            . ' data-test-id="E-404291-B-2010873-TK-3436949-manage-user-action-sharedcreditcards">' . $this->escapeHtml($label) . '</a></li>';
            } else {
                return '<li class="nav item' . $currentClass . '"><a ' . $this->getLinkAttributes()
            . '>' . $this->escapeHtml($label) . '</a></li>';
            }
        }
    }
}
