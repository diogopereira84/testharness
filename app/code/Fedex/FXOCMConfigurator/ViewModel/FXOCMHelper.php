<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FXOCMConfigurator\ViewModel;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CloudDriveIntegration\Helper\Data as cloudHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Fedex\FXOCMConfigurator\Helper\Data;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CustomerCanvas\ViewModel\CanvasParams;

/**
 * Class FXOCMHelper
 * Handle the viewModel of the FXOCMHelper
 */
class FXOCMHelper implements ArgumentInterface
{
    public const TECH_TITANS_E_475721_PRODUCT_ADMIN_CTC = 'tech_titans_E_475721';

    public function __construct(
        protected Data                                        $fxocmhelper,
        protected cloudHelper                                 $cloudHelper,
        protected SsoConfiguration                            $ssoConfiguration,
        protected ProductInfoHandler                          $productInfoHandler,
        protected Cart                                        $cart,
        protected CatalogMvp                                  $catalogmvp,
        protected SelfReg                                     $selfRegHelper,
        protected ProductRepositoryInterface                  $productRepository,
        protected DeliveryHelper                              $deliveryHelper,
        protected SdeHelper                                   $sdeHelper,
        protected AdminConfigHelper                           $adminConfigHelper,
        protected CompanyHelper                               $companyHelper,
        protected SessionFactory                              $customerSessionFactory,
        protected BatchUpload                                 $batchupload,
        protected StoreManagerInterface                       $storeManager,
        protected CollectionFactory                           $quoteItemCollectionFactory,
        protected PunchoutHelper                              $punchoutHelper,
        protected AuthHelper                                  $authHelper,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        private ScopeConfigInterface                          $configInterface,
        private Curl                                          $curl,
        private LoggerInterface                               $logger,
        private CatalogDocumentRefranceApi                    $catalogDocumentRefranceApi,
        private readonly ConfigInterface                      $productBundleConfig,
        private readonly CanvasParams                         $configProvider
    ) {
    }

    /**
     * Checks is marketplace product
     *
     * @return boolean true|false
     */
    public function isIframeSDKEnable(): bool
    {
        return $this->fxocmhelper->getFxoCMToggle();
    }

    /**
     * Get Skuonly Product Id
     *
     * @return string|null
     */
    public function getSkuOnlyProductId(): ?string
    {
        return $this->fxocmhelper->getSkuOnlyProductId();
    }

    /**
     * Get SDK URL from configuration
     *
     * @return string
     */
    public function getSdkUrl()
    {
        return $this->fxocmhelper->getFxoCMSdkUrl();
    }

    /**
     * Get Base Url from configuration
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->fxocmhelper->getFxoCMBaseUrl();
    }

    /**
     * Get Client Id from configuration
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->fxocmhelper->getFxoCMClientId();
    }

    /**
     * Check frontend or admin
     *
     * @return string
     */
    public function checkAreaCode():string
    {
        return $this->fxocmhelper->checkAreaCode();
    }

    /**
     * Check drive upload is enabled
     *
     * @return boolean
     */
    public function isEnabled():bool
    {
        $cloudenable = false;
        if ($this->isBoxEnabled() || $this->isDropboxEnabled() || $this->isGoogleEnabled()
            || $this->isMicrosoftEnabled()
        ) {
            $cloudenable = true;
        }
        return $cloudenable;
    }

    /**
     * Check Box upload is enabled
     *
     * @return boolean
     */
    public function isBoxEnabled():bool
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $company = $this->deliveryHelper->getAssignedCompany();
            $globalConfiguration = $this->cloudHelper->isBoxEnabled();
            return (boolean) ($globalConfiguration && $company->getBoxEnabled());
        } else {
            return $this->cloudHelper->isBoxEnabled();
        }
    }

    /**
     * Check DropBox upload is enabled
     *
     * @return boolean
     */
    public function isDropboxEnabled():bool
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $company = $this->deliveryHelper->getAssignedCompany();
            $globalConfiguration = $this->cloudHelper->isDropboxEnabled();
            return (boolean) ($globalConfiguration && $company->getDropboxEnabled());
        } else {
            return $this->cloudHelper->isDropboxEnabled();
        }
    }

    /**
     * Check Google upload is enabled
     *
     * @return boolean
     */
    public function isGoogleEnabled():bool
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $company = $this->deliveryHelper->getAssignedCompany();
            $globalConfiguration = $this->cloudHelper->isGoogleEnabled();
            return (boolean) ($globalConfiguration && $company->getGoogleEnabled());
        } else {
            return $this->cloudHelper->isGoogleEnabled();
        }
    }

    /**
     * Check Microsoft upload is enabled
     *
     * @return boolean
     */
    public function isMicrosoftEnabled():bool
    {
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $company = $this->deliveryHelper->getAssignedCompany();
            $globalConfiguration = $this->cloudHelper->isMicrosoftEnabled();
            return (boolean) ($globalConfiguration && $company->getMicrosoftEnabled());
        } else {
            return $this->cloudHelper->isMicrosoftEnabled();
        }
    }

    /**
     * Check is retail customer
     *
     * @return boolean
     */
    public function isRetail():bool
    {
        return $this->ssoConfiguration->isRetail();
    }

    /**
     * Get External Product
     *
     * @return array
     */
    public function getExternalProd($item):array
    {
        return $this->productInfoHandler->getItemExternalProd($item);
    }

    public function isTigerE468338ToggleEnabled():bool
    {
        return $this->productBundleConfig->isTigerE468338ToggleEnabled();
    }

    /**
     * Get Cart Items
     *
//     * @return array
     */
    public function getCheckoutItem()
    {
        if ($this->isTigerE468338ToggleEnabled()) {
            return $this->cart->getQuote()->getAllVisibleItems();
        } else {
            return $this->cart->getItems();
        }
    }

    /**
     * Check catalog MVP customer admin
     *
     * @return boolean
     */
    public function isMvpSharedCatalogEnable()
    {
        return $this->catalogmvp->isMvpSharedCatalogEnable();
    }

    /**
     * Check if selfreg customer
     *
     * @return boolean
     */
    public function isSelfRegCustomer()
    {
        return $this->selfRegHelper->isSelfRegCustomer();
    }

    /**
     * Get Product Data by SKu
     *
     * @param  string $sku
     * @return Magento\Catalog\Model\Product | false
     */
    public function getProductData($sku): mixed
    {
        try {
            return $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Check if current store is SDE store
     *
     * @return boolean
     */
    public function getIsSdeStore():bool
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Check if enable upload to quote is enabled
     */
    public function getEnableUploadToQuote()
    {
        $companyId = '';
        $company = $this->deliveryHelper->getAssignedCompany();
        if ($company) {
            $companyId =  $company->getId();
        }
        $storeId = $this->storeManager->getStore()->getId();

        return $this->adminConfigHelper->isUploadToQuoteEnable($storeId, $companyId);
    }

    /**
     * Check if enable upload to quote is enabled on store level
     *
     */
    public function getEnableUploadToQuoteForNSCFlow()
    {
        $storeId = $this->storeManager->getStore()->getId();

        if ($this->fxocmhelper->isCompanySettingNonStandardCatalogToggleEnabled()
            && $this->deliveryHelper->isSelfRegCustomerAdminUser()
            && $this->fxocmhelper->isNscUserFlow()
        ) {
            return $this->isAllowNonStandardCatalog();
        }

        return $this->adminConfigHelper->isUploadToQuoteEnableForNSCFlow($storeId);
    }

    /**
     * Check if Non Standard Catalog Toggle Enabled or Disabled
     *
     * @return boolean
     */
    public function getNonStandardCatalogToggleEnabled():bool
    {
        if ($this->fxocmhelper->isNonStandardCatalogToggleEnabled()
            && $this->fxocmhelper->isCompanySettingNonStandardCatalogToggleEnabled()
        ) {
            if ($this->fxocmhelper->isNscUserFlow()
                && ($this->deliveryHelper->isSelfRegCustomerAdminUser()
                    || $this->deliveryHelper->checkPermission('manage_catalog')
                )
            ) {
                return $this->isAllowNonStandardCatalog();
            } else {
                // For CTC admin it should execute.
                return $this->fxocmhelper->isNonStandardCatalogToggleEnabled();
            }
        } else {
            return $this->fxocmhelper->isNonStandardCatalogToggleEnabled();
        }
    }

    /**
     * Check if Allow Non Standard Catalog Toggle Enabled or Disabled
     *
     * @return boolean
     */
    public function isAllowNonStandardCatalog():bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        $company = $this->deliveryHelper->getAssignedCompany();
        if ($company) {
            $companyId =  $company->getId();

            return $this->adminConfigHelper->isAllowNonStandardCatalogForUser($storeId, $companyId);
        }

       return false;
    }

    /**
     * Check if additional print instructions enable for company admin user
     *
     * @return array
     */
    public function getPrintInstructionsForCompanyAdmin()
    {
        $configValue = [];
        //Whenever remove toggle please remove if else condition code and js code also remove that is use in js file
        if ($this->fxocmhelper->isCompanySettingNonStandardCatalogToggleEnabled()) {
            $configValue['isSelfRegCustomerAdminUser'] = 1;
        } else {
            $configValue['isSelfRegCustomerAdminUser'] = $this->deliveryHelper->isSelfRegCustomerAdminUser();
        }
        $configValue['isNonStandardCatalogToggleEnabled'] = $this->getNonStandardCatalogToggleEnabled();

        return $configValue;
    }

    /**
     * To get additional print instructions configuration value
     *
     * @return array
     */
    public function getAdditionalPrintInstructionsConfigValue()
    {
        $uploadToQuoteArrayForCustomerAdmin = [];

        $uploadToQuoteArrayForCustomerAdmin['enableUploadToQuote'] = $this->getEnableUploadToQuoteForNSCFlow();
        $uploadToQuoteArrayForCustomerAdmin['title'] = $this->adminConfigHelper
            ->getNonStandardCatalogConfigValue('additional_print_instructions_title', null);
        $uploadToQuoteArrayForCustomerAdmin['message'] = $this->adminConfigHelper
            ->getNonStandardCatalogConfigValue('additional_print_instructions_message', null);
        $uploadToQuoteArrayForCustomerAdmin['nonStandardSizeWarningMessage'] = $this->adminConfigHelper
            ->getNonStandardCatalogConfigValue('non_standard_size_warning_message', null);
        return $uploadToQuoteArrayForCustomerAdmin;
    }

    /**
     * To get the upload to quote configuration value
     *
     * @return array
     */
    public function getUploadToQuoteConfigValue()
    {
        $uploadToQuoteArrayForCtcAdmin = [];

        $uploadToQuoteArrayForCtcAdmin['enableUploadToQuote'] = $this->getEnableUploadToQuoteForNSCFlow() ?
            $this->getEnableUploadToQuoteForNSCFlow() : $this->getEnableUploadToQuote();
         $uploadToQuoteArrayForCtcAdmin['title'] = $this->adminConfigHelper
            ->getUploadToQuoteConfigValue('quote_request_title', null);
        $uploadToQuoteArrayForCtcAdmin['message'] = $this->adminConfigHelper
            ->getUploadToQuoteConfigValue('quote_request_message', null);
        $uploadToQuoteArrayForCtcAdmin['nonStandardSizeWarningMessage'] = $this->adminConfigHelper
            ->getUploadToQuoteConfigValue('standard_size_warning_message', null);

        return $uploadToQuoteArrayForCtcAdmin;
    }

    /**
     * To get the upload to quote configuration value empty if non standard catalog toggle off
     *
     * @return array
     */
    public function getEmptyUploadToQuoteConfigValue()
    {
        $uploadToQuoteEmptyArrayForCtcAdmin = [];
        $uploadToQuoteEmptyArrayForCtcAdmin['enableUploadToQuote'] = false;

        return $uploadToQuoteEmptyArrayForCtcAdmin;
    }

    /**
     * To get Fedex Account Number
     *
     * @return string
     */
    public function getFedexAccountNumber()
    {
        $company = $this->deliveryHelper->getAssignedCompany();
        if ($company) {
            return $this->companyHelper->getFedexAccountNumber();
        }
    }

    /**
     * To check customer is logged in
     *
     * @deprecated use \Fedex\Base\Helper\Auth::isLoggedIn() instead
     * @return     bool|string
     */
    public function checkLoggedInCustomer()
    {
        return $this->authHelper->isLoggedIn();
    }

    /**
     * Checks is Batch Upload Enable
     *
     * @return boolean true|false
     */
    public function isBatchUploadEnable(): bool
    {
        return $this->fxocmhelper->getBatchUploadToggle();
    }

    /**
     * Get userworkspace value from session
     */
    public function getUserworkspaceSession()
    {
        return $this->batchupload->getUserWorkspaceSessionValue();
    }

    /**
     * Get customer id
     */
    public function getCustomerId()
    {
        return $this->batchupload->customerId();
    }

    /**
     * Get userworkspace from customer id
     */
    public function getUserworkSpaceFromCustomerId($customerId)
    {
        return $this->batchupload->getUserworkSpaceFromCustomerId($customerId);
    }

    /**
     * Set userworkspaceData in customer session
     */
    public function addDataInSession($workSpaceData)
    {
        return $this->batchupload->addDataInSession($workSpaceData);
    }

    /**
     * get userworkspaceData from session or from db
     */
    public function getWorkspaceData()
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $userworkspaceSession = $this->getUserworkspaceSession();
        if (!$userworkspaceSession) {
            $customerId = $this->getCustomerId();
            if ($customerId) {
                $userworkspaceSession = $this->getUserworkSpaceFromCustomerId($customerId);
                if ($userworkspaceSession) {
                    $this->addDataInSession($userworkspaceSession);
                }
            }
        }
        $return = $userworkspaceSession;
        return $userworkspaceSession;
    }

    /**
     * Get redirect URL
     */
    public function getRedirectUrl()
    {
        return $this->fxocmhelper->getRedirectUrl();
    }

    /**
     * Get All Products PageUrl
     */
    public function getallProductsPageUrl()
    {
        $applicationType = $this->batchupload->getApplicationType();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        if ($applicationType=='retail') {
            //Remove default string from base url
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
     * Get Item Custom Field Option value
     *
     * @param int $itemId
     * return string
     */
    public function getItemCustomDocumentData($itemId)
    {
        $quoteItemCollection = $this->quoteItemCollectionFactory->create();
        $quoteItem           = $quoteItemCollection
            ->addFieldToSelect('*')
            ->addFieldToFilter('item_id', $itemId)
            ->getFirstItem();

        // retrieve the quote item options
        $quoteItemOptions = $quoteItem->getOptionByCode('customize_fields');
        if ($quoteItemOptions && $quoteItemOptions->getData()) {
            $customizeData = $quoteItemOptions->getData('value');
            if ($customizeData) {
                $customizeData = json_decode($customizeData, true);
                if (isset($customizeData['value'])) {
                    return json_encode($customizeData['value']);
                }
            }
        }
        return '[{}]';
    }

    /**
     * Get Item Info BuyRquest Option value
     *
     * @param int $itemId
     * return string
     */
    public function getItemExternalProd($itemId)
    {
        $quoteItemCollection = $this->quoteItemCollectionFactory->create();
        $quoteItem           = $quoteItemCollection
            ->addFieldToSelect('*')
            ->addFieldToFilter('item_id', $itemId)
            ->getFirstItem();

        // retrieve the quote item options
        $quoteItemOptions = $quoteItem->getOptionByCode('info_buyRequest');

        if ($quoteItemOptions && $quoteItemOptions->getData()) {
            $customizeData = $quoteItemOptions->getData('value');
            if ($customizeData) {
                $customizeData = json_decode($customizeData, true);
                if (isset($customizeData['external_prod'][0])) {
                    return json_encode($customizeData['external_prod'][0]);
                }
            }
        }
        return false;
    }

    /**
     * Get company site name
     *
     * @return string
     */
    public function getSiteName()
    {
        $siteName = '';
        if ($this->deliveryHelper->getCompanySite()) {
            $siteName = $this->deliveryHelper->getCompanySite();
        } else if($this->deliveryHelper->getCompanyName()) {
            $siteName = $this->deliveryHelper->getCompanyName();
        }
        return $siteName;
    }

    /**
     * Get Taz Token
     *
     * @return string
     */
    public function getTazToken()
    {
        $tazToken = '';
        if ($this->punchoutHelper->getTazToken()) {
            $tazToken = $this->punchoutHelper->getTazToken();
        }
        return $tazToken;
    }

    /**
     * New Documents Api Image Preview Toggle
     *
     * @return boolean true|false
     */
    public function isNewDocumentsApiImageEnable()
    {
        return $this->fxocmhelper->getNewDocumentsApiImagePreviewToggle();
    }

    /**
     * Get Integration Type
     *
     * @return string
     */
    public function getIntegrationType()
    {
        if($this->fxocmhelper->getEproIntegrationType() && $this->isEproCustomer()) {
            return "IFRAME";
        }
        return $this->fxocmhelper->getIntegrationType();
    }

    /*
     * Get Footer Content
     *
     * @return string
     */
    public function getFooterContent()
    {
        return $this->fxocmhelper->getFooterContent();
    }

    /*
     * Get Logo Url
     *
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->fxocmhelper->getLogoUrl();
    }

    /*
     * Get company name for self reg and SDE
     *
     * @return string
     */
    public function getCompanyName()
    {
        if (!$this->isRetail()) {
            return $this->deliveryHelper->getCompanyName();
        }
    }


    /*
     * Get Footer Text
     *
     * @return string
     */
    public function getFooterText()
    {
        return $this->fxocmhelper->getFooterText();
    }


    /*
     * Get Footer Link
     *
     * @return string
     */
    public function getFooterLink()
    {
        return $this->fxocmhelper->getFooterLink();
    }

    /**
     * Check fxocm custom doc for epro is enabled
     */
    public function checkFxoCmEproCustomDocEnabled()
    {
        return $this->fxocmhelper->checkFxoCmEproCustomDocEnabled();
    }

    /*
     * check epro customer
     * @return string
     */
    public function isEproCustomer()
    {
        return $this->deliveryHelper->isEproCustomer();
    }

    /**
     * Get toggle value for fixed qty handler
     *
     * @return string
     */
    public function getFixedQtyHandlerToggle()
    {
        return $this->fxocmhelper->getFixedQtyHandlerToggle();
    }

    /**
     * Get toggle value for allowFileUpload issue D-177591
     */
    public function getFixAllowFileUploadToggle()
    {
        return $this->fxocmhelper->getFixAllowFileUploadToggle();
    }

    /**
     * Get Page Group from Printready
     */
    public function getPageGroupsPrintReady($documentId, $returnArray = false)
    {
        $returnJson = '[]';
        try {
            if ($documentId !== '') {
                $url = $this->getPrintReadyAPIUrl();
                $dataString = $this->prepareDataPrintReady($documentId);
                $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
                $headers = [
                    "Content-Type: application/json",
                    "client_id: ".$gateWayToken
                ];
                $this->curl->setOptions(
                    [
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $dataString,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_ENCODING => '',
                    ]
                );
                $this->curl->post($url, $dataString);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $pageGroups = [];
                if (isset($response['output']) && isset($response['output']['document']['documentMetrics']['pageGroups'])) {
                    if (is_array($response['output']['document']['documentMetrics']['pageGroups'])) {
                        $pageGroupArray = $response['output']['document']['documentMetrics']['pageGroups'];
                        foreach ($pageGroupArray as $page) {
                           $pageGroups['start'] = $page['startPageNumber'];
                           $pageGroups['end'] = $page['endPageNumber'];
                           $pageGroups['width'] = $page['pageWidthInInches'];
                           $pageGroups['height'] = $page['pageHeightInInches'];
                        }
                        return $returnArray ? [$pageGroups] : json_encode([$pageGroups]);
                    }
                }

                if (isset($response['errors']) || !isset($response['output'])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Print Ready API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Print Ready API response:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                    return $returnArray ? [] : $returnJson;
                }
                return $returnArray ? [] : $returnJson;
            }
            return $returnArray ? [] : $returnJson;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Exception occurred while pulling Print Ready API: ' . $e->getMessage());
            return $returnArray ? [] : $returnJson;
        }
    }

    /**
     * Get All Pagegroups based on content assosication
     * @param  string $externalProdData
     * @return string
     */
    public function getPageGroups($externalProdData)
    {
        $pageGroups = [];
        $externalProdDataArray = json_decode($externalProdData);
        if (isset($externalProdDataArray->contentAssociations) && is_array($externalProdDataArray->contentAssociations) && count($externalProdDataArray->contentAssociations) > 0) {
            $counter = 0;
            foreach ($externalProdDataArray->contentAssociations as $key => $content) {
                if (!isset($content->pageGroups) || empty($content->pageGroups)) {
                    $pageGroups[$counter]['contentReference'] = $content->contentReference;
                    $pageGroups[$counter]['pageGroups'] = $this->getPageGroupsPrintReady($content->contentReference, true);
                }
                $counter++;
            }
        }
        return json_encode($pageGroups);
    }

    /**
     * Prepare Data for Print Ready Call
     * @return array
     */
    public function prepareDataPrintReady($documentId)
    {
         $data = [
           "printReadyRequest" => [
                 "documentId" => $documentId,
                 "conversionOptions" => [
                    "lockContentOrientation" => false,
                    "minDPI" => 200,
                    "defaultImageWidthInInches" => "8.5",
                    "defaultImageHeightInInches" => "11"
                 ],
                 "normalizationOptions" => [
                       "lockContentOrientation" => false,
                       "marginWidthInInches" => "0",
                       "targetWidthInInches" => "",
                       "targetHeightInInches" => "",
                       "targetOrientation" => "UNKNOWN"
                    ],
                 "previewURL" => true,
                 "expiration" => [
                          "units" => "DAYS",
                          "value" => 365
                       ]
              ]
        ];

        return json_encode($data);
    }

    /**
     * Get Print Ready API Url
     *
     * @return string
     */
    public function getPrintReadyAPIUrl()
    {
        return $this->configInterface->getValue('fedex/general/print_ready_api_url', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get toggle value for print ready call for custom doc fix
     *
     * @return boolean
     */
    public function getPrintReadyCustomDocFixToggle()
    {
        return $this->fxocmhelper->getPrintReadyCustomDocFixToggle();
    }

    /**
     * Get Toggle Value epro Custom doc for migrated Document Toggle
     *
     * @return boolean
     */
    public function getEproMigratedCustomDocToggle()
    {
        return $this->fxocmhelper->getEproMigratedCustomDocToggle();
    }

    /**
     * Get Epro upload to quote feature toggle enable or disable
     *
     * @return  boolean
     */
    public function isEproUploadToQuoteToggleEnable(): bool|int|null
    {
        return $this->fxocmhelper->isEproUploadToQuoteToggleEnable();
    }

    /**
     * Get convert to size modal text configuration value
     *
     * @return string
     */
    public function getConvertToSizeModalText(): string | null
    {
        return $this->fxocmhelper->getConvertToSizeModalText();
    }

    /*
     * Get home url
     *
     * @return string
     */
    public function getHomeUrl()
    {
        return $this->ssoConfiguration->getHomeUrl();
    }

    /**
     * Get GeneralConfig Value
     *
     * @return string
     */
    public function getGeneralConfig($code)
    {
        return $this->configInterface->getValue($code, ScopeInterface::SCOPE_STORE);
    }

    /**
     * method to update document id, on new doc api for user workspace data
     *
     * @return  string
     */
    public function migrateWorkspaceId($userworkspaceSession) {
        $data = json_decode($userworkspaceSession, true);
        $fileIdMap = [];
        // Update the 'files' section and populate the map
        if (isset($data['files'])) {
            foreach ($data['files'] as &$file) {
                if (is_numeric($file['id']) ) {
                    // Convert the ID if it's numeric
                    $response = $this->catalogDocumentRefranceApi
                    ->documentLifeExtendApiCallWithDocumentId($file['id']);
                    if (!empty($response) && array_key_exists('output', $response)) {
                        $newDocumentId = $response['output']['document']['documentId'];
                        $fileIdMap[$file['id']] = $newDocumentId;
                        $file['id'] = $newDocumentId;
                    }
                }
            }
        }

        // Update the 'associatedDocuments' section in 'projects' based on the file name
        if (isset($data['projects'])) {
            foreach ($data['projects'] as &$project) {
                foreach ($project['associatedDocuments'] as &$document) {
                    if (isset($document['id']) && isset($fileIdMap[$document['id']])) {
                        // Set the new ID for the document based on the file ID map
                        $document['id'] = $fileIdMap[$document['id']];
                    }
                }
            }
        }
        // Encode the updated data back into a JSON string
        return json_encode($data, JSON_PRETTY_PRINT);
    }


    /**
     * Method to remove legacy document from workspace
     *
     * @return  bool|string
     */
    public function removeLegacyDocFromWorkspace($userworkspaceSession)
    {
        try {
            $data = json_decode($userworkspaceSession, true);
            $filteredFiles = [];
            // Filter out numeric IDs and keep only non-numeric files
            if (isset($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $file) {
                    if (isset($file['id']) && !is_numeric($file['id'])) {
                        $filteredFiles[] = $file;
                    }
                }
                $data['files'] = $filteredFiles;
            }

            if (isset($data['projects']) && is_array($data['projects'])) {
                foreach ($data['projects'] as &$project) {
                    if (isset($project['associatedDocuments']) && is_array($project['associatedDocuments'])) {
                        $filteredDocuments = [];
                        foreach ($project['associatedDocuments'] as $document) {
                            if (isset($document['id']) && !is_numeric($document['id'])) {
                                $filteredDocuments[] = $document;
                            }
                        }
                        $project['associatedDocuments'] = $filteredDocuments;
                    }
                }
            }
            // Encode the updated data back into a JSON string
            return json_encode($data, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            $this->logger->error('Exception occurred while removing legacy document from workspace: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Toggle to migrate document of workspace
     *
     * @return boolean true|false
     */
    public function isUserWorkSpaceNewDocumentToggle()
    {
        return (boolean) $this->fxocmhelper->getUserWorkSpaceNewDocumentToggle();
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
     * Get toggle value for allowFileUpload for catalog flow
     */
    public function getAllowFileUploadCatalogFlow()
    {
        return $this->fxocmhelper->getAllowFileUploadCatalogFlow();
    }

    /**
     * Get Toggle to remove legacy document from workspace B-2353482
     *
     * @return bool|null
     */
    public function isRemoveLegacyDocumentToggle(): bool|null
    {
        return (bool) $this->fxocmhelper->removeLegacyDocumentFromWorkspace();
    }

    public function isToggleEnabledForProductAdminCTCE475721(): bool
    {
        return (bool) $this
            ->deliveryHelper
            ->getToggleConfigurationValue(self::TECH_TITANS_E_475721_PRODUCT_ADMIN_CTC);
    }
    /**
     * @return array
     */
    public function isDyeSubEnabled():bool
    {
        return $this->configProvider->isDyeSubEnabled();
    }
    /**
     * Get External Product
     *
     * @return string
     */
    public function getVendorOptions($item):string
    {
        $vendorOptions= '{}';
        $productConfig = $this->productInfoHandler->getProductConfig($item);
        if(!empty($productConfig)){
            $vendorOptions = json_encode($productConfig["vendorOptions"]??[]);
        }
        return $vendorOptions;
    }

    /**
     * @param $instanceId
     * @return false
     */
    public function getDyesubProductByInstanceId(string $instanceId): bool
    {
        $items = $this->cart->getItems()->getItems();
        $matched = array_filter($items, fn($item) => $item->getInstanceId() === $instanceId);

        if (!$matched) {
            return false;
        }

        $item = reset($matched);
        return (bool) $item->getProduct()->getIsCustomerCanvas();
    }

}
