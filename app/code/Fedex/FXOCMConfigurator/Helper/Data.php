<?php

namespace Fedex\FXOCMConfigurator\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\SelfReg\Block\Landing;
use Magento\Framework\App\Request\Http;

/**
 * Class Helper
 */
class Data extends AbstractHelper
{
    const FXO_CM_TOGGLE = 'fxo_cm_toggle';
    const BATCH_UPLOAD_TOGGLE = 'batch_upload_toggle';
    const NEW_DOCUMENTS_API_IMAGE_PREVIEW = 'new_documents_api_image_preview_toggle';
    const INTEGRATION_TYPE = 'fxo_cm_integration';
    const FXO_CM_EPRO_CUSTOM_DOC = 'fxo_cm_epr_custom_doc';
    const CHAR_LIMIT_TOGGLE = 'character_limit_toggle';
    const CATLOG_ENABLE_ELLIPSIS_CONTROL = 'fedex/catalog_ellipsis_control/enabled';
    const CATLOG_ELLIPSIS_CONTROL_TOTAL_CHARACTERS = 'fedex/catalog_ellipsis_control/total_characters';
    const CATLOG_ELLIPSIS_CONTROL_START_CHARACTERS = 'fedex/catalog_ellipsis_control/start_characters';
    const CATLOG_ELLIPSIS_CONTROL_END_CHARACTERS = 'fedex/catalog_ellipsis_control/end_characters';
    const FXO_CM_FIXED_QTY_HANDLE_FOR_CATALOG_MVP = 'fxo_cm_fixed_qty_handle_for_catalog_mvp';
    const TECH_TITANS_FIX_ALLOW_FILE_UPLOAD_ISSUE = 'tech_titans_d_177591_fix_allow_file_upload_issue';
    const EXPLORERS_PRINTREADY_CUSTOM_DOC_FIX = 'explorers_call_print_ready_custom_doc_fix';
    const MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC = 'b2184326_epro_migrated_custom_doc';
    const EXPLORERS_EPRO_UPLOAD_TO_QUOTE = 'explorers_epro_upload_to_quote';
    const CONVERT_TO_SIZE_MODAL_TEXT = 'fedex/fxo_cm_content/convert_to_size_modal_text';
    const CONVERT_TO_SIZE_MODAL_REDIRECT_LINK = 'fedex/fxo_cm_content/convert_to_size_modal_redirect_link';
    const MILLIONAIRES_CUSTOM_DOC_CART_PAGE_IMAGE = 'd_188392_selfreg_migrated_customdoc_preview_image_cart_page';
    const USER_WORKSPACE_NEW_DOCUMENTS_UPDATE = 'b_2205515_migrate_workspace_data_to_new_doc_api';
    const MILLIONAIRES_COMMERCIAL_CART_PAGE_LINE_ITEMS = 'd191716_commercial_cart_page_line_items';
    const MILLIONAIRES_EPRO_LEGACY_LINE_ITEMS = 'd193132_ePro_legacy_line_items';
    const EXPLORER_EPRO_INTEGRATION_TYPE = 'explorer_B2285990_ePro_Integrationtype';
    const EXPLORERS_ALLOW_FILE_UPLOAD_CATALOG_FLOW = 'explorers_allow_file_upload_catalog_flow';
    public const REMOVE_LEGACY_DOCUMENT_REORDER_CRON_TOGGLE = 'techtitans_B2353493_legacy_documents_not_reorderable_nightly_job';
    const TECHTITANS_WORKSPACE_REMOVE_LEGACY_DOCUMENT = 'techtitans_b2353482_remove_legacy_document';

    protected $context;

    /**
     * Constructor
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptorInterface
     * @param State $state
     * @param RedirectInterface $redirect
     * @param Landing $landing
     * @param Http $request
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        ScopeConfigInterface $scopeConfig,
        protected EncryptorInterface $encryptorInterface,
        protected State $state,
        protected RedirectInterface $redirect,
        protected Landing $landing,
        protected Http $request
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Get FXO CM Toggle
     */
    public function getFxoCMToggle(): bool|int
    {
        return $this->toggleConfig->getToggleConfigValue(static::FXO_CM_TOGGLE);
    }

    /**
     * Get FXO CM Url
     */
    public function getFxoCMSdkUrl()
    {
        return $this->scopeConfig->getValue(
            'fedex/general/fxocm_sdk_url'
        );
    }

    /**
     * Get Sku only product id
     *
     * @return string|null
     */
    public function getSkuOnlyProductId()
    {
        return $this->scopeConfig->getValue(
            'ondemand_setting/category_setting/sku_only_productId'
        );
    }

    /**
     * Get FXO CM Base Url
     */
    public function getFxoCMBaseUrl()
    {
        return $this->scopeConfig->getValue(
            'fedex/gateway_token/fxocm_base_url'
        );
    }

    /**
     * Get FXO CM Client Id
     */
    public function getFxoCMClientId()
    {
        return $this->encryptorInterface->decrypt(
            $this->scopeConfig->getValue(
                'fedex/gateway_token/client_id'
            )
        );
    }

    /**
     * check area code
     * @return string
     */
    public function checkAreaCode():string
    {
        return $this->state->getAreaCode();
    }

    /**
     * Get Batch Upload Toggle
     */
    public function getBatchUploadToggle(): bool|int
    {
        return $this->toggleConfig->getToggleConfigValue(static::BATCH_UPLOAD_TOGGLE);
    }

    /**
     * Check If Explorers Non Standard Catalog Toggle Enabled or Disabled
     */
    public function isNonStandardCatalogToggleEnabled(): bool|int
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_non_standard_catalog');
    }

    /**
     * Check If Explorers Company Setting Non Standard Catalog Toggle Enabled or Disabled
     */
    public function isCompanySettingNonStandardCatalogToggleEnabled(): bool|int|null
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_company_setting_non_standard_catalog');
    }

    /**
     * Check If NSC user flow then return true otherwise false
     */
    public function isNscUserFlow(): bool|int
    {
        if ($this->request->getModuleName() === 'catalogmvp') {
            return true;
        }

        return false;
    }

    /**
     * Get redirect URL
     *
     */
    public function getRedirectUrl()
    {
        return $this->redirect->getRefererUrl();
    }


     /**
      * Get New Documents Api Image Preview
      */
    public function getNewDocumentsApiImagePreviewToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::NEW_DOCUMENTS_API_IMAGE_PREVIEW);
    }

    /**
     * Get FXO CM Integration Type
     */
    public function getIntegrationType()
    {
        return $this->toggleConfig->getToggleConfigValue(static::INTEGRATION_TYPE);
    }

    /**
     * Get FXO CM Footer Content
     */
    public function getFooterContent()
    {
        return $this->scopeConfig->getValue('fedex/fxo_cm_content/footer_content');
    }

    /**
     * Get Logo Url
     */
    public function getLogoUrl()
    {
        return $this->landing->getLogoSrc();
    }

    /**
     * Get FXO CM Footer Text
     */
    public function getFooterText()
    {
        return $this->scopeConfig->getValue('fedex/fxo_cm_content/fxocm_footer_text');
    }

    /**
     * Get FXO CM Footer Link
     */
    public function getFooterLink()
    {
        return $this->scopeConfig->getValue('fedex/fxo_cm_content/fxocm_footer_link');
    }

    /**
     * Check fxocm custom doc for epro is enabled
     */
    public function checkFxoCmEproCustomDocEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(static::FXO_CM_EPRO_CUSTOM_DOC);
    }

    /**
     * Get 100 Char Limit Toggle
     */
    public function getCharLimitToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::CHAR_LIMIT_TOGGLE);
    }

    /**
     * @return bool
     */
    public function isCatalogEllipsisControlEnabled()
    {
        return (bool) $this->scopeConfig->getValue(
            static::CATLOG_ENABLE_ELLIPSIS_CONTROL
        );
    }

    /**
     * @return int
     */
    public function getCatalogEllipsisControlTotalCharacters()
    {
        return (int) $this->scopeConfig->getValue(
            static::CATLOG_ELLIPSIS_CONTROL_TOTAL_CHARACTERS
        );
    }

    /**
     * @return int
     */
    public function getCatalogEllipsisControlStartCharacters()
    {
         return (int) $this->scopeConfig->getValue(
            static::CATLOG_ELLIPSIS_CONTROL_START_CHARACTERS
        );
    }

    /**
     * @return int
     */
    public function getCatalogEllipsisControlEndCharacters()
    {
        return (int) $this->scopeConfig->getValue(
            static::CATLOG_ELLIPSIS_CONTROL_END_CHARACTERS
        );
    }

    /**
     * Get toggle value for fixed qty handler
     */
    public function getFixedQtyHandlerToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::FXO_CM_FIXED_QTY_HANDLE_FOR_CATALOG_MVP);
    }

    /**
     * Get toggle value for allowFileUpload issue D-177591
     * @return boolean
     */
    public function getFixAllowFileUploadToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::TECH_TITANS_FIX_ALLOW_FILE_UPLOAD_ISSUE);
    }

    /**
     * Get toggle value for print ready call for custom doc fix
     *
     * @return boolean
     */
    public function getPrintReadyCustomDocFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::EXPLORERS_PRINTREADY_CUSTOM_DOC_FIX);
    }

    /**
     * Get Toggle Value epro Custom doc for migrated Document Toggle
     *
     * @return boolean
     */
    public function getEproMigratedCustomDocToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC);
    }

    /**
     * Get Epro upload to quote feature toggle enable or disable
     *
     * @return  boolean
     */
    public function isEproUploadToQuoteToggleEnable(): bool|int|null
    {
        return $this->toggleConfig->getToggleConfigValue(static::EXPLORERS_EPRO_UPLOAD_TO_QUOTE);
    }

    /**
     * Get convert to size modal text configuration value
     *
     * @return string
     */
    public function getConvertToSizeModalText(): string | null
    {
        return $this->scopeConfig->getValue(static::CONVERT_TO_SIZE_MODAL_TEXT);
    }

    /*
     * Get SelfReg Preview Image for cart page toggle enable or disable
     *
     * @return  boolean
     */
    public function isSelfRegPreviewImage(): bool|int|null
    {
        return $this->toggleConfig->getToggleConfigValue(static::MILLIONAIRES_CUSTOM_DOC_CART_PAGE_IMAGE);
    }

    /*
     *  Get Toggle to migrate document of workspace
     *
     * @return  boolean
     */
    public function getUserWorkSpaceNewDocumentToggle(): bool|int|null
    {
        return $this->toggleConfig->getToggleConfigValue(static::USER_WORKSPACE_NEW_DOCUMENTS_UPDATE);
    }


    /**
     * Get Commercial Cart LineItems Toggle
     */
    public function getCommercialCartLineItemsToggle(): bool|int
    {
        return (boolean)$this->toggleConfig->getToggleConfigValue(static::MILLIONAIRES_COMMERCIAL_CART_PAGE_LINE_ITEMS);
    }

    /**
     * Get ePro legacy synced LineItems Toggle
     */
    public function getEproLegacyLineItemsToggle(): bool|int
    {
        return (boolean)$this->toggleConfig->getToggleConfigValue(static::MILLIONAIRES_EPRO_LEGACY_LINE_ITEMS);
    }

    /**
     * Get if epro should ingnore value of getIntegrationType() synced LineItems Toggle
     */
    public function getEproIntegrationType() {
        return (boolean)$this->toggleConfig->getToggleConfigValue(static::EXPLORER_EPRO_INTEGRATION_TYPE);
    }

    /**
     * Get toggle value for allowFileUpload catalog flow B-2293456
     * @return boolean
     */
    public function getAllowFileUploadCatalogFlow()
    {
        return $this->toggleConfig->getToggleConfigValue(static::EXPLORERS_ALLOW_FILE_UPLOAD_CATALOG_FLOW);
    }

    /**
     * Get toggle value for remove reorderable documents(legacy document) API call from the nightly job B-2353493
     * @return boolean
     */
    public function getLegacyDocsNoReorderCronToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::REMOVE_LEGACY_DOCUMENT_REORDER_CRON_TOGGLE);
    }
      
    /*
     *  Remove legacy document from workspace B-2353482
     *
     * @return  bool | null
     */
    public function removeLegacyDocumentFromWorkspace(): bool|null
    {
        return $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_WORKSPACE_REMOVE_LEGACY_DOCUMENT);
    }
}
