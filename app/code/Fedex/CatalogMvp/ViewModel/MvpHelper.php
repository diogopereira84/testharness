<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\ViewModel;

use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\FXOCMConfigurator\Helper\Data as FXOCMHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Directory\Model\PriceCurrency;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class MvpHelper
 * Handle the viewModel of the MvpHelper
 */
class MvpHelper implements ArgumentInterface
{
    const FXO_CM_FIXED_QTY_HANDLE_FOR_CATALOG_MVP = 'fxo_cm_fixed_qty_handle_for_catalog_mvp';

    public static $skuNumber = '1695219572266';
    public const TIGER_D_217190 = 'tiger_d_217190';
    public const TECH_TITANS_E_475721_PRODUCT_ADMIN_CTC = 'tech_titans_E_475721';

    public function __construct(
        protected CatalogMvp                                  $catalogMvp,
        protected Data                                        $deliveryHelper,
        protected CustomerSession                             $customerSession,
        protected ProductRepositoryInterface                  $productRepository,
        protected CompanyHelper                               $companyHelper,
        protected FXOCMHelper                                 $fxoCMHelper,
        protected PriceCurrency                               $priceCurrency,
        readonly private CatalogDocumentRefranceApi           $catalogDocumentRefranceApi,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        private readonly RequestInterface                     $request,
        private ScopeConfigInterface                          $scopeConfig
    ) {
    }
   /**
     * Check if Custom Doc Toggle is enable
     */
    public function isCatalogMvpCustomDocEnable()
    {
        return $this->catalogMvp->customDocumentToggle();
    }
    /**
     * Check if is commercial customer
     *
     * @return boolean
     */
    public function isCommercialCustomer()
    {
        return $this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * Checks if the customer is SelfReg Admin
     *
     * @return bool
     */
    public function isSelfRegCustomerAdmin()
    {
        return $this->catalogMvp->isSelfRegCustomerAdmin();
    }

    /**
     * Get or create the customer session.
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getOrCreateCustomerSession() : CustomerSession
    {

        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession = $this->customerSession->create();
        }
        return $this->customerSession;
    }

    /**
     * Checks if the customer is SelfReg Admin
     *
     * @return bool
     */
    public function isSharedCatalogPermissionEnabled()
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $return = $this->catalogMvp->isSharedCatalogPermissionEnabled();
        return $return;
    }

    /**
     * Checks if not print category
     *
     * @return bool
     */
    public function checkPrintCategory()
    {
        return $this->catalogMvp->checkPrintCategory();
    }

    /**
     * Checks if catalogmvp toggle is on
     *
     * @return bool
     */
    public function isMvpSharedCatalogEnable()
    {
        return $this->catalogMvp->isMvpSharedCatalogEnable();
    }

     /**
     * Get value of add/edit folder access from configuration
     *
     * @return bool
     */
    public function getAddEditFolderAccess()
    {
        return (bool) $this->deliveryHelper->getToggleConfigurationValue('add_edit_folder_access');
    }

    /**
     * Get current categroy
     *
     * @return int
     */
    public function currentCategory()
    {
        $currentCategory = $this->catalogMvp->getCurrentCategory();
        return $currentCategory->getId();
    }
    /**
     * Get Product Data by SKu
     *
     * @param  string $sku
     * @return Magento\Catalog\Model\Product | false
     */
    public function getProductData($sku)
    {
        if($sku) {
            try{
                return $this->productRepository->get($sku);
            }
            catch(\Magento\Framework\Exception\NoSuchEntityException $e){
                return false;
            }
        }
        return false;

    }
    /**
     * Get child category count
     *
     * @return int
     */
    public function getChildCategoryCount()
    {
        return $this->catalogMvp->getChildCategoryCount();
    }

    /**
     * Get Fxo Menu Id
     *
     * @param  int $productId
     * @return string
     */
    public function getFxoMenuId($productId)
    {
        return $this->catalogMvp->getFxoMenuId($productId);
    }

    /**
     * Get Non Standard Catalog toggle
     *
     * @return boolean
     */
    public function isNonStandardCatalogToggleEnabled()
    {
        return (boolean) $this->fxoCMHelper->isNonStandardCatalogToggleEnabled();
    }

    /**
     * Get cloud drive box enable from global and
     * company
     *
     * @return boolean
     */
    public function isCloudDriveBoxEnabled()
    {
        $company = $this->deliveryHelper->getAssignedCompany();
        $globalConfiguration = $this->deliveryHelper->getConfigurationValue(
            'fedex/cloud_drive_integration/box_enabled'
        );

        return (boolean) ($globalConfiguration && is_object($company) && $company->getBoxEnabled());
    }

    /**
     * Get cloud drive Dropbox enable from global and
     * company
     *
     * @return boolean
     */
    public function isCloudDriveDropboxEnabled()
    {
        $company = $this->deliveryHelper->getAssignedCompany();
        $globalConfiguration = $this->deliveryHelper->getConfigurationValue(
            'fedex/cloud_drive_integration/dropbox_enabled'
        );

        return (boolean) ($globalConfiguration && is_object($company) && $company->getDropboxEnabled());
    }

    /**
     * Get cloud drive google enable from global and
     * company
     *
     * @return boolean
     */
    public function isCloudDriveGoogleEnabled()
    {
        $company = $this->deliveryHelper->getAssignedCompany();
        $globalConfiguration = $this->deliveryHelper->getConfigurationValue(
            'fedex/cloud_drive_integration/google_enabled'
        );

        return (boolean) ($globalConfiguration && is_object($company) && $company->getGoogleEnabled());
    }

    /**
     * Get cloud drive microsoft enable from global and
     * company
     *
     * @return boolean
     */
    public function isCloudDriveMicrosoftEnabled()
    {
        $company = $this->deliveryHelper->getAssignedCompany();
        $globalConfiguration = $this->deliveryHelper->getConfigurationValue(
            'fedex/cloud_drive_integration/microsoft_enabled'
        );

        return (boolean) ($globalConfiguration && is_object($company) && $company->getMicrosoftEnabled());
    }

    /**
     * Get company fedex account number
     *
     * @return boolean
     */
    public function getCompanyFedExAccountNumber()
    {
        $company = $this->deliveryHelper->getAssignedCompany();
        $fedexAccountNumber = null;

        if ($company && is_object($company)) {
            $companyId = $company->getId();
            $fedexAccountNumber = $this->companyHelper->getFedexAccountNumber($companyId);
        }

        return $fedexAccountNumber;
    }

    /**
     * Get FXO CM integration Type
     *
     * @return string
     */
    public function getIntegrationType()
    {
        return $this->fxoCMHelper->getIntegrationType();
    }

    /**
     * return int
     */
    public function getSkuNumber()
    {
        return self::$skuNumber;
    }

    /**
     * return boolean
     */

    /**
     * B-1978493 - Return currency symbol to PHTML
     *
     * @param  null|string|bool|int|\Magento\Framework\App\ScopeInterface $scope
     * @param  \Magento\Framework\Model\AbstractModel|string|null         $currency
     * @return string
     */
    public function getCurrencySymbol($scope = null, $currency = null)
    {
        return $this->priceCurrency->getCurrency($scope, $currency)->getCurrencySymbol();
    }

    /**
     * @return int
     */
    public function getCharLimitToggle()
    {
        return $this->fxoCMHelper->getCharLimitToggle();
    }

    /**
     * Get document expired
     *
     * @param  int $productId
     * @return boolean
     */
    public function getIsExpiryDocument($productId) : bool
    {
        $result = false;
        $expiredDocuments = $this->catalogDocumentRefranceApi->getExpiryDocuments();
        if (in_array($productId, array_column($expiredDocuments, 'product_id'))) {
            $result = true;
        }
        return $result;
    }

    /**
     * Get catalog expiry notification toggle
     *
     * @return boolean
     */
    public function isCatalogExpiryNotificationToggle() : bool
    {
        return (boolean) $this->deliveryHelper->getToggleConfigurationValue(
            'catalog_expiry_notifications'
        );
    }

    /**
     * Get D-217161 toggle
     *
     * @return boolean
     */
    public function isD217161Enabled(): bool
    {
        return (boolean) $this->deliveryHelper->getToggleConfigurationValue(
            'sgc_d_217161'
        );
    }

    /**
     * Get toggle value for fixed qty handler
     *
     * @return boolean
     */
    public function getFixedQtyHandlerToggle()
    {
        return (boolean) $this
            ->deliveryHelper
            ->getToggleConfigurationValue(static::FXO_CM_FIXED_QTY_HANDLE_FOR_CATALOG_MVP);
    }

     /**
     * Get if catalog breakpoint toggle is on
     *
     * @return boolean
     */
    public function getCatalogBreakpointToggle()
    {
        return (boolean) $this
            ->deliveryHelper
            ->getToggleConfigurationValue('maze_geeks_catalog_mvp_breakpoints_and_ada');
    }

    /**
     * Check E443304_stop_redirect_mvp_addtocart toggle enable or not
     */
    public function isEnableStopRedirectMvpAddToCart()
    {
        return (boolean)$this->catalogMvp->isEnableStopRedirectMvpAddToCart();
    }

    /**
     * Check D-193118: EPRO_Unable to add in branch document into cart from catalog preview page toggle enable or not
     */
    public function eproUnableToAddInBranchProductToCartToggle() {
        return (boolean) $this
            ->deliveryHelper
            ->getToggleConfigurationValue('d_193118_epro_unable_to_add_inbranch_doc_to_cart');
    }

    /**
     * Get nsc replace file text
     *
     * @return array
     */
    public function getNscReplaceFileConfig()
    {
        $replaceFileApiUrl = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_api_url'
        );
        $replaceFileMaxLimitMsg = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_max_limit_msg'
        );
        $replaceFileMaxLimit = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_max_limit'
        );
        $replaceFileText = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_text'
        );
        $replaceFileNameLength = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_name_length'
        );
        $replaceFileNameLengthMsg = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_name_length_msg'
        );
        $replaceFileSupportedTypes = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_supported_types'
        );
        $replaceFileMaxSize = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_max_size'
        );
        $replaceFileMaxSizeMsg = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_max_size_msg'
        );
        $replaceFileExpiration = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_expiration'
        );
        $replaceFileCheckPdf = $this->deliveryHelper->getConfigurationValue(
            'fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_check_pdf'
        );

        return [
            "replace_file_api_url" => $replaceFileApiUrl,
            "replace_file_max_limit_msg" => $replaceFileMaxLimitMsg,
            "replace_file_max_limit" => $replaceFileMaxLimit,
            "replace_file_text" => $replaceFileText,
            "replace_file_name_length_msg" => $replaceFileNameLengthMsg,
            "replace_file_name_length" => $replaceFileNameLength,
            "replace_file_supported_types" => $replaceFileSupportedTypes,
            "replace_file_max_size_msg" => $replaceFileMaxSizeMsg,
            "replace_file_max_size" => $replaceFileMaxSize,
            "replace_file_expiration" => $replaceFileExpiration,
            "replace_file_check_pdf" => $replaceFileCheckPdf
        ];
    }

    /**
     * Get if NSC replace file toggle is on
     *
     * @return boolean
     */
    public function getNscReplaceFileToggleEnabled() {
        return (boolean) $this
            ->deliveryHelper
            ->getToggleConfigurationValue('explorer_nsc_replace_file');
    }

    /**
     * This function retrive curremt limit.
     * 
     * @param int 
     * @return bool
     */
    public function isMyCurrentLimit(int $limit): bool
    {
        $productListMode = $this->request->getParam('product_list_mode', 'list');
        $sessionKey = $productListMode === 'list' ? 'ProductListLimitList' : 'ProductListLimitGrid';

        $sessionLimit = $this->customerSession->getData($sessionKey);

        return (int)($sessionLimit ?? $this->getDefaultProductLimit($productListMode)) === $limit;
    }

    
    /**
     * Get the default product limit for list or grid mode.
     *
     * @param string $mode   'list' or 'grid'
     * @param null|string|bool|int|\Magento\Framework\App\ScopeInterface $scope Scope context (optional)
     * @return int
     */
    public function getDefaultProductLimit(string $mode, $scope = null): int
    {
        $configPath = $mode === 'list'
            ? 'catalog/frontend/list_per_page'
            : 'catalog/frontend/grid_per_page';

        return (int)$this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scope
        );
    }

    /**
     * @return bool
     */
    public function isD217190Enabled(){
        return (bool)$this
            ->deliveryHelper
            ->getToggleConfigurationValue(self::TIGER_D_217190);
    }

    /**
     * Determines if custom pagination should be applied.
     *
     * @return bool
     */
    public function shouldApplyCustomPagination(): bool
    {
        return $this->isRelevantContext();
    }

    /**
     * Checks if the context is relevant (i.e., specific action name).
     *
     * @return bool
     */
    private function isRelevantContext(): bool
    {
        return $this->request->getFullActionName() === 'selfreg_ajax_productlistajax';
    }

    /**
     * Retrieves the current HTTP request object.
     *
     * @return RequestInterface
     */
     public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Gets the session key for storing product list page size based on view mode.
     *
     * @return string
     */
    public function getSessionPageSizeKey(): string
    {
        $mode = $this->request->getParam('product_list_mode', 'list');
        return ($mode === 'grid') ? 'ProductListLimitGrid' : 'ProductListLimitList';
    }

    /**
     * Retrieves the product list page size from customer session.
     *
     * @return int|null
     */
    public function getSessionPageSize(): ?int
    {
        return $this->customerSession->getData($this->getSessionPageSizeKey());
    }

    /**
     * Sets the product list page size in the customer session.
     *
     * @param int $pageSize
     * @return void
     */
    public function setSessionPageSize(int $pageSize): void
    {
        $this->customerSession->setData($this->getSessionPageSizeKey(), $pageSize);
    }

    public function isToggleEnabledForProductAdminCTCE475721()
    {
        return (bool) $this->deliveryHelper->getToggleConfigurationValue(self::TECH_TITANS_E_475721_PRODUCT_ADMIN_CTC);
    }


}