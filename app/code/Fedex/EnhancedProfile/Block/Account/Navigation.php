<?php

namespace Fedex\EnhancedProfile\Block\Account;

use Fedex\EnhancedProfile\Helper\Account;
use Fedex\SSO\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Navigation extends Current implements SortLinkInterface
{
    /**
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param Session $customerSession
     * @param Account $accountHelper
     * @param CompanyManagementInterface $companyRepository
     * @param AuthHelper $authHelper
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        protected Session $customerSession,
        protected Account $accountHelper,
        protected CompanyManagementInterface $companyRepository,
        public CommercialHelper $commercialHelper,
        protected AuthHelper $authHelper,
        protected Data $dataHelper,
        protected ToggleConfig $toggleConfig,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
{
    $rolesandpermissionenabled = $this->commercialHelper->isRolePermissionToggleEnable();
    if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator')) {
       $loginAsAdmin = $this->accountHelper->getAdminIdByLoginAsCustomer();
        $label = strtolower(str_replace(' ', '-', $this->getLabel()));

        // Hide "Accounts & Credit Cards" and "Preferences" if loginAsAdmin is greater than zero
        if ($loginAsAdmin > 0 && ($label == "accounts-&-credit-cards" || $label == "preferences")) {
           return '';
        }
    }
    $label = strtolower(str_replace(' ', '-', $this->getLabel()));
    if (
        $this->toggleConfig->getToggleConfigValue('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers')
    ) {
        if ($this->dataHelper->isSSOlogin() && ($label == "accounts-&-credit-cards" || $label == "preferences")) {
            return parent::_toHtml();
        }
    } else{
        if ($this->dataHelper->isSSOlogin() && ($label == "accounts-&-credit-cards" || $label == "preferences")) {
            return '';
        }
    }
    

    if ($rolesandpermissionenabled) {
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }

        $highlight = '';

        if ($this->getIsHighlighted()) {
            $highlight = ' current';
        }

        if ($this->isCurrent()) {
            $html = '<li class="nav item">';
            $html .= '<strong>' . $this->escapeHtml(__($this->getLabel())) . '</strong>';
            $html .= '</li>';
        } else {
            $label = strtolower(str_replace(' ', '-', $this->getLabel()));
            $html = '<li class="nav item' . $highlight . '"><a href="' . $this->escapeHtml($this->getHref()) . '"';
            $html .= $this->getTitle()
                ? ' title="' . $this->escapeHtml(__($this->getTitle())) . '"'
                : '';
            $html .= $this->getAttributesHtml() . ' data-test-id="E-404291-B-2010873-TK-3436949-manage-user-action-' . $label . '">';

            if ($this->getIsHighlighted()) {
                $html .= '<strong>';
            }

            $html .= $this->escapeHtml(__($this->getLabel()));

            if ($this->getIsHighlighted()) {
                $html .= '</strong>';
            }

            $html .= '</a></li>';
        }

        return $html;
    } else {
        return parent::_toHtml();
    }
}

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * @return false|string
     */
    public function getCompanyLoginType()
    {
        if ($this->authHelper->isLoggedIn()) {
            return match ($this->authHelper->getCompanyAuthenticationMethod()) {
                AuthHelper::AUTH_FCL => 'FCL',
                AuthHelper::AUTH_SSO => 'SSO',
                AuthHelper::AUTH_PUNCH_OUT => 'EPro Punchout',
                default => false
            };
        }
        return false;
    }
}
