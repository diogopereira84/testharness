<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnhancedProfile\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Delivery\Helper\Data;
use Psr\Log\LoggerInterface;

/**
 * CompanySettings ViewModel class
 */
class CompanySettings implements ArgumentInterface
{
    /**
     * CompanySettings constructor.
     *
     * @param Data $deliveryDataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Data $deliveryDataHelper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Get company level site logo
     *
     * @return string|null
     */
    public function getCompanyLevelSiteLogo()
    {
        return $this->deliveryDataHelper->getCompanyLevelLogo();
    }

    /**
     * Get Company Id
     *
     * @return int
     */
    public function getCompanyId()
    {
        $companyId = 0;
        $company = $this->deliveryDataHelper->getAssignedCompany();
        if ($company) {
            $companyId = $company->getId();
        }
        
        return $companyId;
    }

    /**
     * Get company config data
     *
     * @return mixed
     */
    public function getCompanyConfiguration()
    {
        return $this->deliveryDataHelper->getAssignedCompany();
    }

    /**
     * Get company config reorder status
     *
     * @return boolean
     */
    public function getCompanyConfigReorderEnabled()
    {
        $isReorderEnabled = false;
        $company = $this->getCompanyConfiguration();
        if (is_object($company)) {
            $getAdditionalDataObject = $company->getExtensionAttributes()->getCompanyAdditionalData();
            $isReorderEnabled = (bool)$getAdditionalDataObject->getIsReorderEnabled();
        }

        return $isReorderEnabled;
    }

    /**
     * Get company config notification banner data
     *
     * @return array
     */
    public function getCompanyConfigNotificationBanner()
    {
        $notificationBannerConfigData = [];
        $company = $this->getCompanyConfiguration();
        if (is_object($company)) {
            $additionalObject = $company->getExtensionAttributes()->getCompanyAdditionalData();
            $notificationBannerConfigData = [
                "is_banner_enable" => !empty($additionalObject->getIsBannerEnable())
                ? $additionalObject->getIsBannerEnable() : "0",
                'banner_title' => !empty($additionalObject->getBannerTitle())
                    ? $additionalObject->getBannerTitle() : null,
                'description' => !empty($additionalObject->getDescription())
                    ? strip_tags($additionalObject->getDescription()) : null,
                'iconography' => !empty($additionalObject->getIconography())
                    ? $additionalObject->getIconography() : null,
                'cta_text' => !empty($additionalObject->getCtaText())
                    ? $additionalObject->getCtaText() : null,
                'cta_link' => !empty($additionalObject->getCtaLink())
                    ? $additionalObject->getCtaLink() : null,
                'link_open_in_new_tab' => !empty($additionalObject->getLinkOpenInNewTab())
                    ? $additionalObject->getLinkOpenInNewTab() : "0"
            ];
        }
        return $notificationBannerConfigData;
    }

    /**
     * Get company delivery options data
     *
     * @return mixed
     */
    public function getAllowedDeliveryOptions()
    {
        return $this->deliveryDataHelper->getAllowedDeliveryOptions();
    }
}
