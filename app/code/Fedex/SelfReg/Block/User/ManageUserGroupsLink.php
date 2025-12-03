<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Block\User;

use Fedex\Delivery\Helper\Data;
use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Customer\Block\Account\SortLinkInterface;
use Fedex\SelfReg\ViewModel\CompanyUser;

class ManageUserGroupsLink extends HtmlLink implements SortLinkInterface
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
        public CompanyUser $companyUser,
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
        $hasManagerCatalogPermission = false;

        $isFolderLevelPermissionToggleEnabled = $this->companyUser->toggleUserGroupAndFolderLevelPermissions();
        $isUserGroupOrderApproversToggle = $this->companyUser->getUserGroupOrderApproversToggle();
        $isB2BOrderAprovalEnable = $this->companyUser->isB2BOrderAprovalEnable();
        $isAllowedSharedCatalog = $this->companyUser->isAllowedSharedCatalog();

        $hasManagerUserPermission = $this->helperData->checkPermission('manage_users');
        $hasManagerCatalogPermission = $this->helperData->checkPermission('manage_catalog');

        $showPage = true;
        if ($isFolderLevelPermissionToggleEnabled && !$isUserGroupOrderApproversToggle && !$isAllowedSharedCatalog && $isB2BOrderAprovalEnable) {
            $showPage = false;
        }
        
        if (!$isFolderLevelPermissionToggleEnabled && !$isUserGroupOrderApproversToggle) {
            $showPage = false;
        }

        if (!$isB2BOrderAprovalEnable && !$isAllowedSharedCatalog) {
            $showPage = false;
        }

        if (($showPage) && ($this->helperData->isCustomerAdminUser() || $hasManagerUserPermission || $hasManagerCatalogPermission))
        {
            $this->setLabel("Manage User Groups");

            if ($currentUrl !== null && strpos($currentUrl, 'company/user/groups')) {
                $currentClass .= ' current';
                $currentPage = true;
            }

            return '<li class="nav item ' . $currentClass . '"><a ' . $this->getLinkAttributes()
                . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
        }
    }
}
