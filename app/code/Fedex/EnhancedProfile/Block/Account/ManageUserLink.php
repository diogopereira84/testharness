<?php

namespace Fedex\EnhancedProfile\Block\Account;

use Fedex\Delivery\Helper\Data;
use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Customer\Block\Account\SortLinkInterface;

class ManageUserLink extends HtmlLink implements SortLinkInterface
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

    /** ro render html output */
    protected function _toHtml()
    {
        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentClass = '';
        $currentPage = false;
        if (str_contains((string) $currentUrl, (string) $this->getPath())) {
            $currentClass .= ' current';
            $currentPage = true;
        }
        $hasManagerUserPermission = false;
        $isRolesAndPermissionEnabled = $this->helperData->getToggleConfigurationValue('change_customer_roles_and_permissions');
         if ($isRolesAndPermissionEnabled) {
            $hasManagerUserPermission = $this->helperData->checkPermission('manage_users');
        }
        if ($this->helperData->isCustomerAdminUser() || $hasManagerUserPermission) {
            $this->setLabel("Manage Users");
            if ($currentUrl !== null && strpos($currentUrl, 'company/users') !== false) {
                $currentClass .= ' current';
                $currentPage = true;
            }

                if($currentPage) {
                    return '<li class="nav item ' . $currentClass . '"><strong>' . $this->escapeHtml($this->getLabel()) . '</strong></li>';
                }

                return '<li class="nav item ' . $currentClass . '"><a ' . $this->getLinkAttributes()
                    . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';

        }
    }
}
