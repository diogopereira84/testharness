<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Plugin;

use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Magento\Company\Api\CompanyRepositoryInterface as MagentoCompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * B-1250149 : Magento Admin UI changes to group all the Customer account details
 */
class CompanyRepository
{


    public const  TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';
    protected static $companyExtensionCache = [];

    private array $cacheGet = [];

    public function __construct(
        private AdditionalDataFactory   $additionalDataFactory,
        private CompanyExtensionFactory $companyExtensionFactory,
        private ToggleConfig            $toggleConfig,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig
    ) {
    }

    /**
     * Add Store view id in company data
     * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
     *
     * @param  MagentoCompanyRepositoryInterface $subject
     * @param  CompanyInterface                  $result
     * @return object
     */
    public function afterGet(MagentoCompanyRepositoryInterface $subject, $result)
    {
        if ($result) {
            if (array_key_exists($result->getId(), $this->cacheGet)
                && $this->performanceImprovementPhaseTwoConfig->isActive()
            ) {
                return $this->cacheGet[$result->getId()];
            }

            if($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $companyId = $result->getId();
                if (!isset(self::$companyExtensionCache[$companyId])) {
                    $companyExtension = $result->getExtensionAttributes();
                    if ($companyExtension === null) {
                        $companyExtension = $this->companyExtensionFactory->create();
                    }
                    $companyAdditionalData = $this->additionalDataFactory->create()
                        ->getCollection()->addFieldToSelect('*')
                        ->addFieldToFilter('company_id', ['eq' => $result->getId()])->getFirstItem();
                    $companyExtension->setCompanyPaymentOptions($companyAdditionalData->getCompanyPaymentOptions());
                    $companyExtension->setFedexAccountOptions($companyAdditionalData->getFedexAccountOptions());
                    $companyExtension->setCreditcardOptions($companyAdditionalData->getCreditcardOptions());
                    $companyExtension->setDefaultPaymentMethod($companyAdditionalData->getDefaultPaymentMethod());
                    // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
                    $companyExtension->setCcToken($companyAdditionalData->getCcToken());
                    $companyExtension->setCcData($companyAdditionalData->getCcData());
                    // B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
                    $companyExtension->setCcTokenExpiryDateTime($companyAdditionalData->getCcTokenExpiryDateTime());

                    // Set Admin toggles values && Notofication Banner Data
                    $companyExtension = $this->setAdminToggleNotificationBannerData($companyExtension, $companyAdditionalData);

                    self::$companyExtensionCache[$companyId] = $companyExtension;
                }
                $result->setExtensionAttributes(self::$companyExtensionCache[$companyId]);
            } else {
                $companyExtension = $result->getExtensionAttributes();
                if ($companyExtension === null) {
                    $companyExtension = $this->companyExtensionFactory->create();
                }
                $companyAdditionalData = $this->additionalDataFactory->create()
                    ->getCollection()->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $result->getId()])->getFirstItem();
                $companyExtension->setCompanyPaymentOptions($companyAdditionalData->getCompanyPaymentOptions());
                $companyExtension->setFedexAccountOptions($companyAdditionalData->getFedexAccountOptions());
                $companyExtension->setCreditcardOptions($companyAdditionalData->getCreditcardOptions());
                $companyExtension->setDefaultPaymentMethod($companyAdditionalData->getDefaultPaymentMethod());
                // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
                $companyExtension->setCcToken($companyAdditionalData->getCcToken());
                $companyExtension->setCcData($companyAdditionalData->getCcData());
                // B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
                $companyExtension->setCcTokenExpiryDateTime($companyAdditionalData->getCcTokenExpiryDateTime());

                // Set Admin toggles values && Notofication Banner Data
                $companyExtension = $this->setAdminToggleNotificationBannerData($companyExtension, $companyAdditionalData);

                $result->setExtensionAttributes($companyExtension);
            }
        }
        $this->cacheGet[$result->getId()] = $result;
        return $this->cacheGet[$result->getId()];
    }

    /**
     * Set Admin Toggles values and Notification data
     */
    public function setAdminToggleNotificationBannerData($companyExtension, $companyAdditionalData)
    {
        $companyExtension->setCompanyAdditionalData($companyAdditionalData);

        return $companyExtension;
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     *
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }
}
