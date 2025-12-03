<?php

/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\ViewModel;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;

/**
 * CartSummary ViewModel class
 */
class CartSummary implements ArgumentInterface
{
    public const XML_PATH_UPDATE_CONTINUE_SHOPPING_CTA = 'b2154431_update_continue_shopping_cta';

        /**
     * @var MarketPlaceHelper
     */
    private $marketPlaceHelper;

    /**
     * CartSummary Constructor
     *
     * @param SdeHelper $sdeHelper
     * @param ToggleConfig $toggleConfig
     * @param CartFactory $cartFactory
     * @param QuoteHelper $quoteHelper
     * @param FormKey $formKey
     * @param BatchUpload $batchupload
     * @param UnfinishedProjectNotification $unfinishedProjectNotification
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     */
    public function __construct(
        protected SdeHelper $sdeHelper,
        protected ToggleConfig $toggleConfig,
        protected CartFactory $cartFactory,
        protected QuoteHelper $quoteHelper,
        protected FormKey $formKey,
        protected BatchUpload $batchupload,
        protected UnfinishedProjectNotification $unfinishedProjectNotification,
        private readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        MarketPlaceHelper $marketPlaceHelper
    )
    {
        $this->marketPlaceHelper = $marketPlaceHelper;
    }

    /**
     * Check if the AddToCartPerformanceOptimization is enabled
     *
     * @return bool
     */
    public function isAddToCartPerformanceOptimizationEnabled(): bool
    {
        return $this
            ->addToCartPerformanceOptimizationToggle
            ->isActive();
    }

    /**
     * Checks Is SDE Store
     *
     * @return boolean true|false
     */
    public function isSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Checks is marketplace product
     *
     * @return boolean true|false
     */
    public function isMarketplaceProduct(): bool
    {
        return $this->sdeHelper->isMarketplaceProduct();
    }

    /**
     * Get SDE category URL
     *
     * @return string|Url
     */
    public function getSdeCategoryUrl()
    {
        $returnUrl = $this->sdeHelper->getSdeCategoryUrl();
        $baseUrl = $this->sdeHelper->getBaseUrl();

        if ($returnUrl == $baseUrl && $this->getUpdateContinueShoppingCtaToggle()) {
            $returnUrl = $this->getAllPrintProductUrl();
        }

        return $returnUrl;
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Check is View My Project Button Enable
     *
     * @return bool true|false
     */
    public function isViewMyProjectButtonEnable()
    {
        $quote = $this->cartFactory->create()->getQuote();
        if ($this->toggleConfig->getToggleConfigValue('batch_upload_toggle') &&
            !$this->quoteHelper->isFullMiraklQuote($quote) &&
            $this->batchupload->getUserWorkspaceSessionValue()) {
            return $this->unfinishedProjectNotification->isProjectAvailable();
        }

        return false;
    }

    /**
     * Toggle Tiger Team - B-2260777 Access to Workspace
     *
     * @return bool
     */
    public function isAccessToWorkspaceToggleEnable(): bool
    {
        return $this->unfinishedProjectNotification->isAccessToWorkspaceToggleEnable();
    }

    /**
     * Get Workspace Url
     *
     * @return string
     */
    public function getWorkspaceUrl(){
        return $this->unfinishedProjectNotification->getWorkspaceUrl();
    }

    /**
     * Get View My Project Url
     *
     * @return String
     */
    public function getViewMyProjectUrl()
    {
        $baseUrl = $this->sdeHelper->getBaseUrl();
        //Remove default string from base url
        if (strpos($baseUrl,'default')) {
            $baseUrl = str_replace('default/', '', $baseUrl) ;
        }
        $viewMyProjectUrl = $baseUrl . "configurator/index/index?viewproject=true";

        return $viewMyProjectUrl;
    }

    /**
     * Get toggle value for Millionaires - B-2154431: Update Continue Shopping CTA
     *
     * @return boolean
     */
    public function getUpdateContinueShoppingCtaToggle() {
        return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_UPDATE_CONTINUE_SHOPPING_CTA);
    }

    /**
     * Get CTA retail/commercial site url for continue shopping button
     *
     * @return string
     */
    public function getAllPrintProductUrl() {
        $applicationType = $this->batchupload->getApplicationType();
        $baseUrl = $this->sdeHelper->getBaseUrl();

        if ($applicationType == 'retail') {
            // Remove default string from base url
            if (strpos($baseUrl, 'default')) {
                $baseUrl = str_replace('default/', '', $baseUrl);
            }
            $redirecUrl = $baseUrl . $this->batchupload->getRetailPrintUrl();
        } else {
            $redirecUrl = $baseUrl . $this->batchupload->getCommercialPrintUrl();
        }

        return $redirecUrl;
    }

    /**
     * Check if the cart summary contains a legacy document. | B-2353473
     *
     * @return bool|null
     */
    public function checkLegacyDocOnCartSummary() {
        return $this->marketPlaceHelper->hasLegacyDocumentInQuoteSession();
    }

    /**
     * Check if the legacy document API call should be toggled in the cart. | B-2353473
     *
     * @return bool|null
     */
    public function checkLegacyDocApiOnCartToggle() {
        return $this->marketPlaceHelper->checkLegacyDocApiOnCartToggle();
    }
}
