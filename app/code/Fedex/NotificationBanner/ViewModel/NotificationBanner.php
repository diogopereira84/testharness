<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\NotificationBanner\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Helper\Data as CompanyHelper;

/**
 * NotificationBanner View Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class NotificationBanner implements ArgumentInterface
{
    public const NOTIFICATION_BANNER_ENABLE =
    'notification_banner/notification_banner_all_flow_group/notification_banner_enabled';
    public const NOTIFICATION_BANNER_TITLE =
    'notification_banner/notification_banner_all_flow_group/banner_title';
    public const NOTIFICATION_BANNER_SELECTED_ICON =
    'notification_banner/notification_banner_all_flow_group/notification_iconography';
    public const NOTIFICATION_BANNER_EDITOR =
    'notification_banner/notification_banner_all_flow_group/notificaiton_banner_editor';
    public const NOTIFICATION_BANNER_CTA_TEXT =
    'notification_banner/notification_banner_all_flow_group/cta_text';
    public const NOTIFICATION_BANNER_CTA_LINK =
    'notification_banner/notification_banner_all_flow_group/cta_link';
    public const NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW =
    'notification_banner/notification_banner_all_flow_group/link_open_window';

    public const STORE_DEFAULT = 'default';
    public const STORE_ONDEMAND = 'ondemand';
    public const STORE_LEVEL_ENABLED = 'storeLevelEnabled';
    public const COMPANY_LEVEL_ENABLED = 'companyLevelEnabled';

    /**
     * NotificationBanner Class Construct
     *
     * @param ScopeConfigInterface  $scopeConfig
     * @param Session               $customerSession
     * @param StoreManagerInterface $storeManager
     * @param StoreManagerInterface $assetRepo
     * @param Http                  $request
     * @param UrlInterface          $urlInterface
     * @param ToggleConfig          $toggleConfig
     * @param CompanyHelper         $companyHelper
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected Session $customerSession,
        protected StoreManagerInterface $storeManager,
        protected Repository $assetRepo,
        protected Http $request,
        protected UrlInterface $urlInterface,
        protected ToggleConfig $toggleConfig,
        protected CompanyHelper $companyHelper
    )
    {
    }

    /**
     * get notification banner configuration
     * @return array
     */
    public function getBannerConfiguration()
    {
        $storeId = $this->getCurrentStoreId();
        $enableLevel = $this->getFinalBannerEnableStatus($storeId);
        
        $bannerConfiguration = [];
        if ($enableLevel) {
            $bannerConfiguration = [
                'enabled'              => true,
                'isPageNotFound'       => $this->isPageNotFound(),
                'banner_title'         => $this->getFinalBannerConfValue(
                                            static::NOTIFICATION_BANNER_TITLE,
                                            $storeId,
                                            $enableLevel
                                        ),
                'iconography'          => $this->getFinalBannerConfValue(
                                            static::NOTIFICATION_BANNER_SELECTED_ICON,
                                            $storeId,
                                            $enableLevel
                                        ),
                'description'          => $this->getFinalBannerConfValue(
                                            static::NOTIFICATION_BANNER_EDITOR,
                                            $storeId,
                                            $enableLevel
                                        ),
                'cta_text'             => $this->getFinalBannerConfValue(
                                            static::NOTIFICATION_BANNER_CTA_TEXT,
                                            $storeId,
                                            $enableLevel
                                        ),
                'cta_link'             => $this->getFinalBannerConfValue(
                                            static::NOTIFICATION_BANNER_CTA_LINK,
                                            $storeId,
                                            $enableLevel
                                        ),
                'link_open_in_new_tab' => $this->getFinalBannerConfValue(
                                            static::NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW,
                                            $storeId,
                                            $enableLevel
                                        )
            ];
        }

        return $bannerConfiguration;
    }

    /**
     * Get values of notification banner configuration by key
     *
     * @param String $path
     * @param Int $storeId
     * @return null|bool|string
     */
    public function getNotificationBannerConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Banner Enable/Disable status
     * @param int $storeId
     * @return null|bool
     */
    public function getFinalBannerEnableStatus($storeId = null)
    {
        $storeCode = $this->storeManager->getStore()->getCode();

        $storeLevelEnable = $this->scopeConfig
            ->getValue(static::NOTIFICATION_BANNER_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);

        $companyId = $this->customerSession->getCustomerCompany() ?? null;
        $company = $this->companyHelper->getCustomerCompany($companyId);

        if ($storeCode == static::STORE_DEFAULT && $storeLevelEnable) {
            $enableStatus = static::STORE_LEVEL_ENABLED;
        } elseif (is_object($company) && $storeCode == static::STORE_ONDEMAND) {
            $companyLevelEnableStatus = $company->getExtensionAttributes()
                ->getCompanyAdditionalData()->getIsBannerEnable();

            if ($storeLevelEnable) {
                $enableStatus = static::STORE_LEVEL_ENABLED;
            } elseif (!$storeLevelEnable && $companyLevelEnableStatus) {
                $enableStatus = static::COMPANY_LEVEL_ENABLED;
            } else {
                $enableStatus = null;
            }
        } else {
            $enableStatus = null;
        }

        return $enableStatus;
    }

    /**
     * Get Banner fields configuration from Store/companies
     * @param string  $path
     * @param int     $storeId
     * @param string  $enableLevel
     * @return string
     */
    public function getFinalBannerConfValue($path, $storeId = null, $enableLevel = null)
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        
        $storeLevelValue = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        $companyId = $this->customerSession->getCustomerCompany() ?? null;
        $company = $this->companyHelper->getCustomerCompany($companyId);

        $fieldValue = null;
        if ($storeCode == static::STORE_DEFAULT || $enableLevel == static::STORE_LEVEL_ENABLED) {
            $fieldValue = $storeLevelValue;
        } else {
            if ($path == static::NOTIFICATION_BANNER_TITLE) {
                $fieldValue = $company->getExtensionAttributes()->getCompanyAdditionalData()->getBannerTitle();
            } elseif ($path == static::NOTIFICATION_BANNER_SELECTED_ICON) {
                $fieldValue = $company->getExtensionAttributes()->getCompanyAdditionalData()->getIconography();
            } elseif ($path == static::NOTIFICATION_BANNER_EDITOR) {
                $fieldValue = $company->getExtensionAttributes()->getCompanyAdditionalData()->getDescription();
            } elseif ($path == static::NOTIFICATION_BANNER_CTA_TEXT) {
                $fieldValue = $company->getExtensionAttributes()->getCompanyAdditionalData()->getCtaText();
            } elseif ($path == static::NOTIFICATION_BANNER_CTA_LINK) {
                $fieldValue = $company->getExtensionAttributes()->getCompanyAdditionalData()->getCtaLink();
            } elseif ($path == static::NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW) {
                $fieldValue = $company->getExtensionAttributes()->getCompanyAdditionalData()->getLinkOpenInNewTab();
            }
        }

        return $fieldValue;
    }

    /**
     * Get current store id
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }

    /**
     * Get Notification Banner icon
     *
     * @param string $notificationIconType
     * @return string
     */
    public function getNotificationBannerIcon($notificationIconType)
    {
        if ($notificationIconType == "warning") {
            $icon = $this->assetRepo->getUrl('Fedex_NotificationBanner::images/Alert.png');
        } else {
            $icon = $this->assetRepo->getUrl('Fedex_NotificationBanner::images/lightbulb-icon.png');
        }
        
        return $icon;
    }

    /**
     * Check for excluded pages
     *
     * @return bool true|false
     */
    public function isPageNotFound()
    {
        $controllerName = $this->request->getControllerName();
        $currentUrl = $this->urlInterface->getCurrentUrl();
        
        if ($controllerName == 'noroute' || strpos($currentUrl, 'no-route') !== false ||
        strpos($currentUrl, 'canva') !== false || strpos($currentUrl, 'configurator') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Check is notification banner enabled
     *
     * @return bool true|false
     */
    public function isNotificationBannerEnabled()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_ENABLE, $storeId);
    }

    /**
     * Get notification banner title
     *
     * @return string
     */
    public function notificationBannerTitle()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_TITLE, $storeId);
    }

    /**
     * Get notification banner selected iconography icon
     *
     * @return string
     */
    public function notificationBannerSelectedIconType()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_SELECTED_ICON, $storeId);
    }

    /**
     * Get notification banner body text
     *
     * @return string
     */
    public function notificationBannerBodyText()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_EDITOR, $storeId);
    }

    /**
     * Get notification banner CTA text
     *
     * @return string
     */
    public function notificationBannerCtaText()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_CTA_TEXT, $storeId);
    }

    /**
     * Get notification banner CTA link
     *
     * @return string
     */
    public function notificationBannerCtaLink()
    {
        $storeId = $this->getCurrentStoreId();
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_CTA_LINK, $storeId);
    }

    /**
     * Get notification banner CTA link
     *
     * @return string
     */
    public function notificationBannerLinkOpenInNewWindow()
    {
        $storeId = $this->getCurrentStoreId();
        
        return $this->getNotificationBannerConfig(static::NOTIFICATION_BANNER_CTA_LINK_OPEN_WINDOW, $storeId);
    }
}
