<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Catalog\ViewModel;

use Fedex\CatalogDocumentUserSettings\Helper\Data as CatalogDocumentUserSettingsHelper;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Fedex\FXOCMConfigurator\Helper\Data as FXOCMHelper;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;

/**
 * B-1149167 : RT-ECVS-SDE-SDK changes for File upload
 */
class ProductList implements ArgumentInterface
{

    /**
     * ProductList constructor
     *
     * @param PunchoutHelper $punchoutHelper
     * @param DeliveryDataHelper $deliveryDataHelper
     * @param CatalogDocumentUserSettingsHelper $catalogDocumentUserSettingsHelper
     * @param SdeHelper $sdeHelper
     * @param CatalogMvpHelper $catalogMvpHelper
     * @param FXOCMHelper $fxoCMHelper
     * @param CatalogMvpConfigInterface $catalogMvpConfigInterface
     */
    public function __construct(
        private PunchoutHelper $punchoutHelper,
        private DeliveryDataHelper $deliveryDataHelper,
        private CatalogDocumentUserSettingsHelper $catalogDocumentUserSettingsHelper,
        private SdeHelper $sdeHelper,
        protected CatalogMvpHelper $catalogMvpHelper,
        protected FXOCMHelper $fxoCMHelper,
        protected CatalogMvpConfigInterface $catalogMvpConfigInterface
    ) {
    }

    /**
     * Get Taz Token
     *
     * @return string|null
     */
    public function getTazToken()
    {
        if ($this->deliveryDataHelper->isEproCustomer()) {
            return $this->punchoutHelper->getTazToken();
        }

        return '';
    }

    /**
     * Get company site name
     *
     * @return string
     */
    public function getSiteName()
    {
        if ($this->deliveryDataHelper->isEproCustomer()) {
            return $this->deliveryDataHelper->getCompanySite();
        }

        return '';
    }

    /**
     * Get product wrapper class
     *
     * @return string
     */
    public function getWrapperClass()
    {
        if ($this->isCommercialCustomer()) {
            return 'ero-session';
        }

        return 'retail-session';
    }

    /**
     * Get CatalogDocumentUserSettingsHelper instance
     *
     * @return CatalogDocumentUserSettingsHelper
     */
    public function getCatalogDocumentUserSettingsHelper()
    {
        return $this->catalogDocumentUserSettingsHelper;
    }

    /**
     * Get DeliveryDataHelper instance
     *
     * @return DeliveryDataHelper
     */
    public function getDeliveryDataHelper()
    {
        return $this->deliveryDataHelper;
    }

    /**
     * Check if current customer is commercial customer
     *
     * @return bool
     */
    public function isCommercialCustomer()
    {
        return $this->deliveryDataHelper->isCommercialCustomer();
    }

    /**
     * Check if current store is SDE store
     *
     * @return bool
     */
    public function getIsSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Get CatalogMvpHelper instance
     *
     * @return CatalogMvpHelper
     */
    public function getCatalogMvpHelper(): CatalogMvpHelper
    {
        return $this->catalogMvpHelper;
    }


    /**
     * @return int
     */
    public function getCharLimitToggle()
    {
        return $this->fxoCMHelper->getCharLimitToggle();
    }

    /**
     * @return boolean
     */
    public function isCatalogEllipsisControlEnabled()
    {
        return $this->fxoCMHelper->isCatalogEllipsisControlEnabled();
    }

    /**
     * @return int
     */
    public function getCatalogEllipsisControlTotalCharacters()
    {
        return $this->fxoCMHelper->getCatalogEllipsisControlTotalCharacters();
    }

    /**
     * @return int
     */
    public function getCatalogEllipsisControlStartCharacters()
    {
        return $this->fxoCMHelper->getCatalogEllipsisControlStartCharacters();
    }

    /**
     * @return int
     */
    public function getCatalogEllipsisControlEndCharacters()
    {
        return $this->fxoCMHelper->getCatalogEllipsisControlEndCharacters();
    }

    /**
     * @return int
     */
    public function getFixedQtyHandlerToggle() {
        return $this->fxoCMHelper->getFixedQtyHandlerToggle();
    }

    /**
     * Return the value of the D206810 toggle
     *
     * @return bool|int
     */
    public function isD206810ToggleEnabled(): bool|int {
        return $this->catalogMvpConfigInterface->isD206810ToggleEnabled();
    }
}
