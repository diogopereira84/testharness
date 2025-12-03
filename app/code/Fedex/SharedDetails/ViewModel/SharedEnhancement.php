<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedDetails\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SharedEnhancement implements ArgumentInterface
{
    public const EXPLORERS_CUSTOMER_REPORTING_ENHANCEMENTS = 'explorers_customer_reporting_enhancements';

    public const EXPLORERS_COMPANY_SETTING_CUSTOMER_ADMIN = 'explorers_company_settings_customer_admin';

    /**
     * Timeframe options config path
     */
    public const TIMEFRAME_OPTIONS = 'fedex/recipient_email_address_limit/timeframe_options';

    /**
     * Manage user email address allow limit config path
     */
    public const MANAGE_USER_EMAILS_ALLOW_LIMIT = 'fedex/recipient_email_address_limit/manage_user_email_address_limit';

    /**
     * Custom date options config path
     */
    public const CUSTOM_DATE_OPTION = 'fedex/recipient_email_address_limit/custom_date_option';

    /**
     * @param ToggleConfig $toggleConfig
     * @param UrlInterface $urlInterface
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected UrlInterface $urlInterface,
        protected ScopeConfigInterface $scopeConfigInterface
    )
    {
    }

    /**
     * Check is shared Order page
     *
     * @return bool true|false
     */
    public function isSharedOrderPage()
    {
        if (strpos($this->urlInterface->getCurrentUrl(), '/shared/order/history') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Check is customer reporting enhancement toggle enabled or disabled
     *
     * @return bool true|false
     */
    public function isCustomerReportingEnhancementToggleEnabled()
    {
        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_CUSTOMER_REPORTING_ENHANCEMENTS)) {
            return true;
        }

        return false;
    }

    /**
     * Get timeframe configuration
     *
     * @return array
     */
    public function getTimeframeOptions()
    {
        $timeframeOptionsArray = [];
        $timeframeOptions = $this->scopeConfigInterface->getValue(self::TIMEFRAME_OPTIONS, ScopeInterface::SCOPE_STORE);
        $customDateRange = $this->scopeConfigInterface->getValue(self::CUSTOM_DATE_OPTION, ScopeInterface::SCOPE_STORE);

        $timeframeOptionsArray[] = ['label' => 'Select a timeframe', 'value' => ''];
        if ($timeframeOptions) {
            if (!empty($timeframeOptions) && strpos($timeframeOptions, ',') !== false) {
                $data = explode(",", $timeframeOptions);
                foreach ($data as $option) {
                    $timeframeOptionsArray[] = $this->prepareTimeframeOptions($option);
                }
            } elseif (!empty($timeframeOptions)) {
                $timeframeOptionsArray[] = $this->prepareTimeframeOptions($timeframeOptions);
            }
        }

        if ($customDateRange) {
            $timeframeOptionsArray[] = ['label' => 'Custom Date Range', 'value' => 'custom-'.$customDateRange];
        }

        return $timeframeOptionsArray;
    }

    /**
     * Get timeframe select options configuration
     *
     * @return array
     */
    public function getTimeframeSelectOptions()
    {
        $timeframeOptionsArray = [];
        $timeframeOptions = $this->scopeConfigInterface->getValue(self::TIMEFRAME_OPTIONS, ScopeInterface::SCOPE_STORE);
        $customDateRange = $this->scopeConfigInterface->getValue(self::CUSTOM_DATE_OPTION, ScopeInterface::SCOPE_STORE);

        if ($timeframeOptions) {
            if (!empty($timeframeOptions) && strpos($timeframeOptions, ',') !== false) {
                $data = explode(",", $timeframeOptions);
                foreach ($data as $option) {
                    $timeframeOptionsArray[] = $this->prepareTimeframeOptions($option);
                }
            } elseif (!empty($timeframeOptions)) {
                $timeframeOptionsArray[] = $this->prepareTimeframeOptions($timeframeOptions);
            }
        }

        if ($customDateRange) {
            $timeframeOptionsArray[] = ['label' => 'Custom Date Range', 'value' => 'custom-'.$customDateRange];
        }

        return $timeframeOptionsArray;
    }

    /**
     * Prepare timeframe configuration
     *
     * @param string $option
     * @return array
     */
    private function prepareTimeframeOptions($option)
    {
        if ($option == 1) {
            return ['label' => $option.' Month', 'value' => $option];
        } else {
            return ['label' => $option.' Months', 'value' => $option];
        }
    }

    /**
     * Get user emails allow limit configuration
     *
     * @return int|string
     */
    public function getUserEmailsAllowLimit()
    {
        return $this->scopeConfigInterface->getValue(self::MANAGE_USER_EMAILS_ALLOW_LIMIT, ScopeInterface::SCOPE_STORE);
    }


    /**
    * Toggle for Company Settings
    *
    * @return boolean
    */
    public function isCompanySettingsToggleEnabled()
    {
       return $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_COMPANY_SETTING_CUSTOMER_ADMIN);
    }
}
