<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Block\Account;

use Fedex\Delivery\Helper\Data;
use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Customer\Block\Account\SortLinkInterface;

class CompanySettings extends HtmlLink implements SortLinkInterface
{
    /**
     * @param Context $context
     * @param Data $helperData
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        protected Context $context,
        protected Data $helperData,
        protected UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /** To render html output */
    protected function _toHtml()
    {
        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentClass = '';
        $currentPage = false;
        if (str_contains((string) $currentUrl, (string) $this->getPath())) {
            $currentClass .= ' current';
            $currentPage = true;
        }

        $isCompanySettingToggleEnabled = $this->helperData->getToggleConfigurationValue('explorers_company_settings_customer_admin');

        if ($this->helperData->isCommercialCustomer()) {
            $this->setLabel("Site Settings");
            if ($currentUrl !== null && strpos($currentUrl, 'customer/account/sitesettings') !== false) {
                $currentClass .= ' current';
                $currentPage = true;
            }

            if($isCompanySettingToggleEnabled && $this->checkSiteSettingsPermission()) {
                return '<li class="nav item ' . $currentClass . '"><a ' . $this->getLinkAttributes()
                    . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
            }
        }
    }

    /**
     * Check if user has a permission for site settings
     *
     * @return string
     */
    public function checkSiteSettingsPermission()
    {
        if ($this->helperData->isCompanyAdminUser()) {
            return true;
        }

        return $this->helperData->checkPermission('site_settings');
    }
}
