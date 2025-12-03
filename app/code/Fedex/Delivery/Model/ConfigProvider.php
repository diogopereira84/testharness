<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model;

use Fedex\Cart\ViewModel\CheckoutConfig;
use Magento\Backend\Model\Auth\Session as BackendSession;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Magento\Framework\App\ObjectManager;
use Fedex\CustomerDetails\Helper\Data as CustomerDetailsHelper;
use Fedex\Delivery\Block\CartPickup;
use Fedex\Delivery\ViewModel\CartPickup as CartPickupViewModel;
use Fedex\Delivery\Helper\Data;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Recaptcha\Api\Data\ConfigInterface as RecaptchaConfigInterface;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SubmitOrderSidebar\ViewModel\OrderSuccess;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Directory\Model\RegionFactory;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\Framework\View\Asset\Repository;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceRatesHelper;

/**
 * ConfigProvider Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const FIRST_NAME = 'firstname';
    public const LAST_NAME = 'lastname';
    public const CUSTOM_ATTRIBUTES = 'custom_attributes';
    public const VALUE = 'value';
    public const COMPANY = 'company';
    public const REGION = 'region';
    public const POSTCODE = 'postcode';
    public const TELEPHONE = 'telephone';
    public const STREET = 'street';
    public const STATUS = 'status';
    public const ENABLE_PROMOCODE_RETAIL = 'enable_promo_code_features_retail';
    public const ENABLE_PROMOCODE_COMMERCIAL = 'enable_promo_code_features_commercial';
    public const NOTIFICATION_PROMOCODE_EDITOR =
    'notification_promocode/notification_promocode_group/notificaiton_promocode_editor';
    public const TERMS_AND_CONDITION_URL = 'fedex/enhanced_profile_group/terms_and_condition_url';
    public const EXPLORERS_ENABLE_DISABLE_FEDEX_ACCOUNT_CC = 'explorers_enable_disable_fedex_account_cc_commercial';
    public const ARMADA_CALL_RATE_API_SHIPPING_VALIDATION = 'armada_call_rate_api_shipping_validation';
    public const EXPLORERS_CATALOG_MIGRATION = 'explorers_catalog_migration';
    public const TIGER_MY_ACCOUNT_UI_NAVIGATION_CONSISTENCY = 'tiger_e_408758_my_account_ui_navigation_consistency';
    public const TIGER_PICKUP_DATETIME_EXPIRATION_TOGGLE = 'tiger_pickup_datetime_expiration_toggle';
    public const TIGER_ENABLE_HCO_SUMMARY_ITEMS_REPRICE = 'tiger_enable_hco_summary_items_reprice';
    public const TIGER_DISPLAY_SELFREG_CART_FXO_DISCOUNT_3P_ONLY = 'tiger_b1973447_display_selfreg_cart_fxo_discount_3P_only';
    public const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';
    public const EXPLORERS_RESTRICTED_AND_RECOMMNDED_PRODUCTION = 'explorers_restricted_and_recommended_production';
    public const EXPLORERS_D174773_FIX = 'explorers_d_174773_fix';
    public const EXPLORERS_D179523_FIX = 'explorers_d_179523_fix';
    public const SGC_PRIORITY_PRINT_LIMITED_TIME_TAG = 'sgc_priority_print_limited_time_tag';
    public const SGC_PROMISE_TIME_PICKUP_OPTIONS = 'sgc_promise_time_pickup_options';
    public const MAZEGEEKS_D_187301_Fix = 'mazegeeks_d187301_fix';
    public const EXPLORERS_SITE_LEVEL_QUOTE_STORES = 'explorers_site_level_quoting_stores';
    public const EXPLORERS_D180349_FIX = 'explorers_D180349_fix';
    public const EXPLORERS_D193257_FIX = 'explorers_d_193257_fix';
    public const XMEN_ORDER_CONFIRMATION_FIX = 'xmen_order_confirmation_fix';
    public const TIGER_D180761_3P_ORDERS_MISSING_STATE_FIX = 'tiger_d180761_3p_orders_missing_state_fix';
    public const TECHTITAN_D_180202_CANNOT_SUBMIT_ORDER_SINGLE_METHOD = 'tech_titans_D_180202_cannot_submit_order_with_single_method_selected';
    public const TIGER_E_427646_SHIPPING_METHODS_DISPLAY = 'tiger_e_427646_shipping_methods_display';
    public const EXPLORERS_EPRO_U2Q = 'explorers_epro_upload_to_quote';
    public const XMEN_D177346_FIX = 'xmen_D177346_fix';
    public const TIGER_E424573_OPTIMIZING_PRODUCT_CARDS = 'tiger_E424573_optimizing_product_cards';
    public const EXPLORERS_D193256_FIX = 'explorers_d_193256_fix';
    public const EXPLORERS_PRODUCTION_LOCATION_FIX = 'explorers_d188299_production_location_fix';
    public const TIGER_D195836_FIX_LOAD_TIME_HERO_BANNER = 'tiger_d195836_fix_high_load_time_for_hero_banner';
    public const TIGER_D203990_TOGGLE = 'tiger_d203990';
    public const EXPLORERS_ADDRESS_CLASSIFICATION_FIX = 'explorers_address_classification_fix';

    public const TECH_TITANS_D_194434 = 'tech_titans_d_194434';
    public const TECH_TITANS_D_198167 = 'tech_titans_d_198167';
    public const MAGEGEEKS_PO_BOX_VALIDATION = 'maegeeks_pobox_toggle';

    public const TECHTITANS_D_199955_SUBFOLDERS = 'tech_titans_d_199955';
    public const REMOVE_BASE64_TOGGLE = 'is_remove_base64_image';

    public const TIGER_D195387 = 'tiger_d195387';
    public const TIGER_D213977 = 'tiger_d213977';
    public const TIGER_D217535 = 'tiger_d217535';
    public const TIGER_D217133 = 'tiger_d217133';
    public const TIGER_E486666 = 'tiger_e486666';
    public const EXPLORERS_E_450676_ADDRESS_BOOK = 'explorers_e_450676_personal_address_book';
    public const MAZEGEEK_B2352379_DISCOUNT_BREAKDOWN = 'mazegeek_B2352379_discount_breakdown';
    public const TECHTITANS_D_205447_FIX = 'techtitans_205447_wrong_location_fix';
    public const TIGER_E_469373 = 'tigerteam_E469373_fedex_shipping_account_number_validation_api_call';
    public const TIGER_TEAM_B_2429967 = 'tiger_team_B_2429967';
    public const IS_B2421984_ENABLED = 'tech_titans_b_2421984_remove_preview_calls_from_catalog_flow';
    public const TECHTITANS_D221338 = 'tech_titans_d221338';
    public const TECHTITANS_D217174 = 'tech_titans_d217174';
    public const TIGER_E499634 = 'enable_customer_acknowledgement_sharing_shipping_account_number_with_third_party';
    public const SHIPPING_ACCOUNT_ACKNOWLEDGEMENT_MESSAGE = 'fedex/marketplace_configuration/shipping_account_acknowledgement_message';
    public const SHIPPING_ACCOUNT_ACKNOWLEDGEMENT_ERROR_MESSAGE = 'fedex/marketplace_configuration/shipping_account_acknowledgement_error_message';
    public const TIGER_B2532564 = 'tiger_b2532564';
    public const TIGER_D_216029 = 'tiger_team_D_216029';
    public const TIGER_TEAM_D_225000 = 'tiger_team_D_225000';
    public const TIGER_D_227679 = 'tiger_team_D_227679';
    public const TECHTITANS_D_192487 = 'techtitans_D_192487';
    public const TIGER_E_469378_U2Q_PICKUP = 'tiger_team_E_469378_u2q_pickup';
    public const MAZEGEEKS_E_482379_ALLOW_CUSTOMER_TO_CHOOSE_PRODUCTION_LOCATION_UPDATES = 'mazegeeks_e_482379_allow_customer_to_choose_production_location_updates';
    public const SGC_D_236651 = 'sgc_d236651';
    public const TECHTITANS_D_238830 = 'tech_titans_D_238830';
    public const TECHTITANS_D_238086 = 'tech_titans_D_238086';

    /**
     * @param Data $helper
     * @param BackendSession $backendSession
     * @param CustomerDetailsHelper $retailHelper
     * @param CartPickup $cartPickup
     * @param ToggleConfig $toggleConfig
     * @param CheckoutConfig $checkoutConfig
     * @param Session $customerSession
     * @param AccountManagementInterface $accountManagement
     * @param SdeHelper $sdeHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param CompanyHelper $companyHelper
     * @param OrderSuccess $orderSuccessViewModel
     * @param LoggerInterface $logger
     * @param SelfReg $selfregHelper
     * @param CartPickupViewModel $cartPickupViewModel
     * @param Account $accountHelper
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param RegionFactory $regionFactory
     * @param RecaptchaConfigInterface $recaptchaConfig
     * @param AuthHelper $authHelper
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param Repository $assetRepository
     * @param MarketPlaceHelper $marketPlaceHelper
     * @param SecureHtmlRenderer $secureHtmlRenderer
     * @param FuseBidViewModel $fuseBidViewModel
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     * @param MarketPlaceRatesHelper $marketPlaceRatesHelper
     */
    public function __construct(
        protected Data $helper,
        private BackendSession $backendSession,
        protected CustomerDetailsHelper $retailHelper,
        protected CartPickup $cartpickup,
        protected ToggleConfig $toggleConfig,
        protected CheckoutConfig $checkoutConfig,
        protected Session $customerSession,
        protected AccountManagementInterface $accountManagement,
        protected SdeHelper $sdeHelper,
        protected ScopeConfigInterface $scopeConfig,
        protected CompanyHelper $companyHelper,
        protected OrderSuccess $orderSuccessViewModel,
        protected LoggerInterface $logger,
        protected SelfReg $selfregHelper,
        private CartPickupViewModel $cartPickupViewModel,
        protected Account $accountHelper,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected RegionFactory $regionFactory,
        protected RecaptchaConfigInterface $recaptchaConfig,
        protected AuthHelper $authHelper,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        protected Repository $assetRepository,
        private MarketPlaceHelper $marketPlaceHelper,
        private SecureHtmlRenderer $secureHtmlRenderer,
        protected FuseBidViewModel $fuseBidViewModel,
        private GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId,
        private MarketPlaceRatesHelper $marketPlaceRatesHelper
    ) {
    }

    /**
     * Shipping configuration for checkout page
     *
     * @return array
     */
    public function getConfig()
    {
        $enableFcl = true;
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        $enablePromocodeRetail = (bool)
        $this->toggleConfig->getToggleConfigValue(self::ENABLE_PROMOCODE_RETAIL);
        $enablePromocodeCommercial = (bool)
        $this->toggleConfig->getToggleConfigValue(self::ENABLE_PROMOCODE_COMMERCIAL);
        $companyName = $this->companyHelper->getCompanyName();
        $fedexAccountNumber = ($this->checkoutConfig->getAccountDiscountWarningFlag()) ?
        '' : $this->checkoutConfig->getAppliedFedexAccountNumber();
        $fedexAccounNumberType = $this->accountHelper->getAccountNumberType($fedexAccountNumber);
        $fedexAccounNumberDiscount = $fedexAccounNumberType == 'DISCOUNT' ? $fedexAccountNumber : null;
        $fedexAccountNumber = $fedexAccountNumber != $fedexAccounNumberDiscount ? $fedexAccountNumber : null;
        $explorersCatalogMigration = (bool) $this->toggleConfig->getToggleConfigValue(
            static::EXPLORERS_CATALOG_MIGRATION
        );
        $explorersNonStandardCatalog = (bool) $this->toggleConfig->getToggleConfigValue(
            static::EXPLORERS_NON_STANDARD_CATALOG
        );

        $explorersAddressClassificationFix = (bool) $this->toggleConfig->getToggleConfigValue(
            static::EXPLORERS_ADDRESS_CLASSIFICATION_FIX
        );

        //E-390888 - Add FedEx Accounts for CC Commercial sites toggle enable or disable
        $explorersEnableDisableFedexAccountCC = (bool) $this->toggleConfig->getToggleConfigValue(
            static::EXPLORERS_ENABLE_DISABLE_FEDEX_ACCOUNT_CC
        );
        $sgcPromiseTimePickupOptions = (bool) $this->toggleConfig->getToggleConfigValue(
            static::SGC_PROMISE_TIME_PICKUP_OPTIONS
        );
        $sgcPriorityPrintLimitedTimeTag = (bool) $this->toggleConfig->getToggleConfigValue(
            static::SGC_PRIORITY_PRINT_LIMITED_TIME_TAG
        );
        $explorersSiteLevelQuoteStores = (bool) $this->toggleConfig->getToggleConfigValue(
            static::EXPLORERS_SITE_LEVEL_QUOTE_STORES
        );
        $mazegeeksImprovingUpdateItemQtyCart = (bool) $this->toggleConfig->getToggleConfigValue('mazegeeks_improving_update_item_qty_cart');

        // Set default payment method
        // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
        $profilePreferredPayment = $this->accountHelper->getPreferredPaymentMethod();
        $allowedPaymentMethods = $this->companyHelper->getCompanyPaymentMethod();
        $paymentMethodData = $this->companyHelper->getDefaultPaymentMethod();
        $preselectPaymentMethod = $profilePreferredPayment ?: $this->companyHelper->getPreferredPaymentMethodName();
        $defaultPaymentMethod = $preselectPaymentMethod ?? $paymentMethodData['defaultMethod'];
        $defaultPaymentMethodValue = $this->getPaymentMethodDefaultValue(
            $preselectPaymentMethod,
            $paymentMethodData['paymentMethodInfo']
        ) ?? '';
        $preselectAccountValue = $fedexAccounNumberType == 'DISCOUNT' && !is_array($defaultPaymentMethodValue)
            ? $defaultPaymentMethodValue
            : null;
        $isNonEditableCompanyCcPaymentMethod = (bool) $this->companyHelper->getNonEditableCompanyCcPaymentMethod();
        $isApplicablePaymentMethodCCOnly = (bool) $this->companyHelper->isApplicablePaymentMethodCCOnly();
        $isCommercial = $this->helper->isCommercialCustomer();
        $isEproCustomer = $this->helper->isEproCustomer();
        $isEnableFcl = (($enableFcl) ? $isCommercial && $isEproCustomer : $this->authHelper->isLoggedIn());

	    $armadaCallRateApiShippingValidation = (bool) $this->toggleConfig->getToggleConfigValue(
            static::ARMADA_CALL_RATE_API_SHIPPING_VALIDATION
        );

        $tigerPickupDatetimeExpirationToggle = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TIGER_PICKUP_DATETIME_EXPIRATION_TOGGLE
        );
        $tigerEnableHCOSummaryItemsReprice = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TIGER_ENABLE_HCO_SUMMARY_ITEMS_REPRICE
        );
        $tigerDisplaySelfregCartFxoDiscount3pOnly= (bool)$this->toggleConfig->getToggleConfigValue(
            static::TIGER_DISPLAY_SELFREG_CART_FXO_DISCOUNT_3P_ONLY
        );
        $explorersRestrictedProduction = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_RESTRICTED_AND_RECOMMNDED_PRODUCTION
        );
        $explorersD174773Fix = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_D174773_FIX
        );
        $explorersD180349Fix = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_D180349_FIX
        );
        $explorersD193257Fix = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_D193257_FIX
        );
        $explorersD179523Fix = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_D179523_FIX
        );

        $tigerD195836FixLoadTimeHeroBanner = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TIGER_D195836_FIX_LOAD_TIME_HERO_BANNER
        );

        $techTitansD194434 = (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_D_194434);
        $techTitansD198167 = (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_D_198167);
        $mazeGeeksD187301 = (bool) $this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D_187301_Fix);
        $promoDiscountEnabled = $this->checkoutConfig->isPromoDiscountEnabled();
        $accountDiscountEnabled = $this->checkoutConfig->isAccountDiscountEnabled();
        $termsAndConditionsEnabled = $this->checkoutConfig->isTermsAndConditionsEnabled();
        $isSelfRegCustomerAdminUser = $this->helper->isSelfRegCustomerAdminUser();
        $isUploadToQuoteEnable = $this->uploadToQuoteViewModel->isUploadToQuoteEnable();

        $infoIconImage = $this->assetRepository->getUrl('images/upload-to-quote/info.png');
        $incompleteCheckmarkImage = $this->assetRepository->getUrl('images/incomplete-checkmark.svg');
        $errorIconImage = $this->assetRepository->getUrl('images/icon-error.png');
        $crossIconImage = $this->assetRepository->getUrl('images/upload-to-quote/close.png');
        $warningIconImage = $this->assetRepository->getUrl('images/commercial-order-approval/warning.png');

        $arrUploadToQuoteConfigValues = [
            'enableUploadToQuote' => $isUploadToQuoteEnable,
            'message' => $this->uploadToQuoteViewModel->getUploadToQuoteConfigValue('quote_request_message'),
        ];
        $isRecipientAddressEnabled = $this->companyHelper->getRecipientAddressFromPo();

        $isD180202ToggleEnable = (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITAN_D_180202_CANNOT_SUBMIT_ORDER_SINGLE_METHOD);

        $tigerE424573OptimizingProductCards = (bool) $this->toggleConfig->getToggleConfigValue(
            static::TIGER_E424573_OPTIMIZING_PRODUCT_CARDS
        );
        $tigerE458381Essendant = (bool)$this->marketPlaceHelper->isEssendantToggleEnabled();
        $tigerShippingMethodsDisplay = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TIGER_E_427646_SHIPPING_METHODS_DISPLAY
        );

        $xmenOrderConfirmationFix = (bool) $this->toggleConfig->getToggleConfigValue(
            static::XMEN_ORDER_CONFIRMATION_FIX
        );

        $locationIconImage = $this->assetRepository->getUrl('images/upload-to-quote/Location.svg');
        $adminId = $this->getLoggedAsCustomerAdminId->execute();
        $impersonatorIsEnabled =  $this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator');
        $fcl_login_customer_detail = $this->helper->getFCLCustomerLoggedInInfo();
        $fclDefaultShippingData = $this->getDefaultShippingAddress();
        if($impersonatorIsEnabled && $adminId) {
            $fcl_login_customer_detail['first_name'] = "";
            $fcl_login_customer_detail['last_name'] = "";
            $fcl_login_customer_detail['contact_number'] = "";
            $fcl_login_customer_detail['email_address'] = "";
            $fcl_login_customer_detail['contact_ext'] = "";
            $fclDefaultShippingData = [];
        }

        $scriptTest = <<<script
        window.FDXPAGEID = 'US/en/office/default/checkout';
        window.FDX = window.FDX || {};
        window.FDX.GDL = window.FDX.GDL || [];
        window.FDX.GDL.push([
            'event:publish',
            [
                'page',
                'pageinfo',
                {pageId: 'US/en/office/default/checkout',confirm:'event62'}
            ]
        ]);
        script;

        $scriptWithNonce = $this->secureHtmlRenderer->renderTag(
            'script',
            ['type' => 'text/javascript'],
            $scriptTest,
            false);

        return [
            'media_url' => $this->cartpickup->getMediaUrl('wysiwyg'),
            'is_logged_in' => $this->isLoggedIn($isEnableFcl),
            'fcl_login_customer_detail' => $fcl_login_customer_detail,
            'dunc_office_api_url' => ($this->checkoutConfig->getDocumentOfficeApiUrl() ?? ''),
            'is_pickup' => $this->isFromPickup(),
            'is_delivery' => $this->isFromDelivery(),
            'both' => $this->isFromBoth(),
            'is_fcl_customer' => $this->isFromFCLCustomer(),
            'is_self_reg_fcl_customer' => $this->isFromSelfRegFcl(),
            'is_epro' => $isEproCustomer,
            'fcl_customer_default_shipping_data' => $fclDefaultShippingData,
            'is_out_sourced' => $this->helper->isOurSourced(),
            'is_sde' => $this->sdeHelper->isProductSdeMaskEnable(),
            'is_sde_store' => $isSdeStore,
            'sde_product_mask_image_url' => $this->sdeHelper->getSdeMaskSecureImagePath(),
            'sde_signature_message' => $this->sdeHelper->getDirectSignatureMessage(),
            'enable_promo_code_features_retail' => $enablePromocodeRetail,
            'enable_promo_code_features_commercial' => $enablePromocodeCommercial,
            'promo_code_combined_discount_message' => $this->getCombinedDiscountMessage(),
            'show_promo_code_combined_discount_message' => $this->checkoutConfig->getWarningMessage(),
            'shipping_account_number' => $this->companyHelper->getFedexShippingAccountNumber(),
            'company_payment_methods_allowed' => $allowedPaymentMethods ?? false,
            'company_name' => $companyName,
            'fedex_account_number' => $preselectAccountValue ?: $fedexAccountNumber,
            'fedex_account_number_discount' => $fedexAccounNumberDiscount,
            'company_fxo_account_number' => $this->companyHelper->getFxoAccountNumber(),
            'company_payment_method_cc_only' => $isApplicablePaymentMethodCCOnly,
            'company_discount_account_number' => $this->companyHelper->getDiscountAccountNumber(),
            'preselect_payment_method' => $preselectPaymentMethod,
            'default_payment_method' => $defaultPaymentMethod,
            'default_payment_method_value' => $defaultPaymentMethodValue,
            'available_payment_method_value' => $paymentMethodData['paymentMethodInfo'],
            'fedex_account_payment_method_identifier' => PaymentOptions::FEDEX_ACCOUNT_NUMBER,
            'credit_card_payment_method_identifier' => PaymentOptions::CREDIT_CARD,
            'quote_id' => $this->orderSuccessViewModel->getQuoteId(),
            'retail_profile_session' => $this->customerSession->getProfileSession(),
            'is_selfreg_customer' => $this->selfregHelper->isSelfRegCustomer(),
            'is_selfreg_customer_admin_user' => $isSelfRegCustomerAdminUser,
            'is_non_editable_company_cc_payment' => $isNonEditableCompanyCcPaymentMethod,
            'is_terms_and_condition_url' => $this->getTermsAndConditionUrl(),
            'dunc_request_response_data' => $this->customerSession->getDuncResponse(),
            'pickup_search_error_description' => $this->cartPickupViewModel->getPickupSearchErrorMessage(),
            'explorers_enable_disable_fedex_account_cc_commercial' => $explorersEnableDisableFedexAccountCC,
            'promo_discount_enabled' => $promoDiscountEnabled,
            'account_discount_enabled' => $accountDiscountEnabled,
            'terms_and_conditions_enabled' => $termsAndConditionsEnabled,
            'armada_call_rate_api_shipping_validation' => $armadaCallRateApiShippingValidation,
            'isUploadToQuote' => $this->uploadToQuoteViewModel->isUploadToQuoteEnable(),
            'upload_to_quote_config_values' => json_encode($arrUploadToQuoteConfigValues),
            'input_name_error_message' => $this->helper->getFormFieldNameErrorMessage(),
            'login_validation_key' => $this->customerSession->getLoginValidationKey(),
            'explorers_catalog_migration'=> $explorersCatalogMigration,
            'tiger_google_recaptcha_site_key' => $this->recaptchaConfig->getPublicKey(),
            'is_quote_price_is_dashable' => $this->uploadToQuoteViewModel->checkoutQuotePriceisDashable(),
            'non_standard_imageurl' => $this->uploadToQuoteViewModel->getNonStandardImageUrl(),
            'tiger_enable_essendant' => $tigerE458381Essendant,
            'tiger_enable_cbb' => $this->marketPlaceHelper->isCBBToggleEnabled(),
            'tiger_display_selfreg_cart_fxo_discount_3P_only' => $tigerDisplaySelfregCartFxoDiscount3pOnly,
            'explorers_non_standard_catalog'=> $explorersNonStandardCatalog,
            'explorers_restricted_and_recommended_production' => $explorersRestrictedProduction,
            'explorers_d_174773_fix' => $explorersD174773Fix,
            'explorers_d_179523_fix' => $explorersD179523Fix,
            'explorers_D180349_fix' => $explorersD180349Fix,
            'explorers_d_193257_fix' => $explorersD193257Fix,
            'sgc_priority_print_limited_time_tag' => $sgcPriorityPrintLimitedTimeTag,
            'sgc_promise_time_pickup_options' => $sgcPromiseTimePickupOptions,
            'xmen_order_approval_b2b_enabled' => $this->orderApprovalViewModel->isOrderApprovalB2bEnabled(),
            'xmen_pending_order_approval_msg_title' => $this->orderApprovalViewModel->getPendingOrderApprovalMsgTitle(),
            'xmen_pending_order_approval_msg' => $this->orderApprovalViewModel->getPendingOrderApprovalMsg(),
            'info_icon_url' =>  $infoIconImage,
            'error_icon_url' => $errorIconImage,
            'cross_icon_url' => $crossIconImage,
            'incomplete_checkmark_icon_url' => $incompleteCheckmarkImage,
            'is_recipient_address_from_po' => $isRecipientAddressEnabled,
            'explorers_site_level_quoting_stores' =>  $explorersSiteLevelQuoteStores,
            'xmen_order_confirmation_fix' => $xmenOrderConfirmationFix,
            'isD180202ToggleEnable' => $isD180202ToggleEnable,
            'xmen_order_approval_warning_icon' => $warningIconImage,
            'tiger_B2027702_vendor_shipping_account_enable' => $this->marketPlaceHelper->isVendorSpecificCustomerShippingAccountEnabled(),
            'tiger_B2027702_vendor_shipping_account_disclaimer' => $this->marketPlaceHelper->getVendorSpecificCustomerShippingAccountDisclaimer(),
            'tiger_shipping_methods_display' => $tigerShippingMethodsDisplay,
            'location_icon_image' => $locationIconImage,
            'gdlScript' => $scriptWithNonce,
            'explorers_epro_upload_to_quote' =>(bool) $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_EPRO_U2Q),
            'xmen_D177346_fix' => (bool) $this->toggleConfig->getToggleConfigValue(self::XMEN_D177346_FIX),
            'tigerE424573OptimizingProductCards' => $tigerE424573OptimizingProductCards,
	        'explorers_d_193256_fix' => (bool) $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D193256_FIX),
            'explorers_d188299_production_location_fix' => (bool) $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_PRODUCTION_LOCATION_FIX),
            'mazegeeks_ctc_admin_impersonator'=> (bool)$this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator'),
            'mazegeeks_d187301_fix' =>  $mazeGeeksD187301,
            'is_fusebid_toggle_enabled' => $this->fuseBidViewModel->isFuseBidToggleEnabled(),
            'tiger_D193772_fix' => (bool) $this->toggleConfig->getToggleConfigValue('tiger_d193772_fix'),
            'loggedAsCustomerCustomerId' => $adminId,
            'techtitans_d194434' => $techTitansD194434,
            'techtitans_d198167' => $techTitansD198167,
            'marketplace_freight_shipping_enabled' => $this->marketPlaceRatesHelper->isFreightShippingEnabled(),
            'marketplace_freight_surcharge_text' => $this->marketPlaceRatesHelper->getFreightShippingSurchargeText(),
            'tiger_D195836_fix_load_time_hero_banner' => $tigerD195836FixLoadTimeHeroBanner,
            'mazegeeks_improving_update_item_qty_cart'=> $mazegeeksImprovingUpdateItemQtyCart,
            'explorers_address_classification_fix' => $explorersAddressClassificationFix,
            'explorers_d_198644_fix' => (bool) $this->toggleConfig->getToggleConfigValue('explorers_d_198644_fix'),
            'tech_titans_b_2179775' => (bool) $this->toggleConfig->getToggleConfigValue('tech_titans_b_2179775'),
            'tech_titans_d_203420' => (bool) $this->toggleConfig->getToggleConfigValue('tech_titans_d_203420'),
            'maegeeks_pobox_validation'=> (bool)$this->toggleConfig->getToggleConfigValue(SELF::MAGEGEEKS_PO_BOX_VALIDATION),
            'tiger_d203990'=> (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D203990_TOGGLE),
            /** B-2353103 : Return if Base64 Img Toggle Enabled*/
            'is_remove_base64_image' => (bool) $this->toggleConfig->getToggleConfigValue(self::REMOVE_BASE64_TOGGLE),
            'document_image_preview_url' => ($this->checkoutConfig->getDocumentImagePreviewUrl() ?? ''),
            /** B-2353103 : Return if Base64 Img Toggle Enabled*/
            'document_image_url' => ($this->checkoutConfig->getDocumentImagePreviewUrl() ?? ''),
            'explorers_e_450676_personal_address_book' => (bool) $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_E_450676_ADDRESS_BOOK),
            'tiger_d195387'=> (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D195387),
            'mazegeek_b2352379_discount_breakdown'=> (bool)$this->toggleConfig->getToggleConfigValue(self::MAZEGEEK_B2352379_DISCOUNT_BREAKDOWN),
            'tech_titans_d_205447_fix' => (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_205447_FIX),
            'tiger_b2384493' => (bool) $this->toggleConfig->getToggleConfigValue('tiger_b2384493'),
            'tech_titans_b_2421984_remove_preview_calls_from_catalog_flow' => (bool) $this->toggleConfig->getToggleConfigValue(self::IS_B2421984_ENABLED),
            'tigerteamE469373enabled' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_E_469373),
            'tiger_d213977' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D213977),
            'tech_titans_d_214912' => (bool) $this->toggleConfig->getToggleConfigValue('tech_titans_d_214912'),
            'tiger_team_B_2429967' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TEAM_B_2429967),
            'tiger_d217535' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D217535),
            'tiger_d217133' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D217133),
            'tech_titans_d221338' => (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D221338),
            'tech_titans_d217174' => (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D217174),
            'tiger_e486666' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_E486666),
            'isCustomerAcknowledgementThirdPartyEnabled' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_E499634),
            'shipping_account_acknowledgement_message' => $this->getShippingAccountAcknowledgementMessage(),
            'shipping_account_acknowledgement_error_message' => $this->getShippingAccountAcknowledgementErrorMessage(),
            'tiger_b2532564' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_B2532564),
            'tiger_team_D_216029' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D_216029),
            'tiger_team_D_225000' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TEAM_D_225000),
            'tiger_team_D_227679' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D_227679),
            'techtitans_D_192487' => (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_192487),
            'tiger_team_E_469378_u2q_pickup' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_E_469378_U2Q_PICKUP),
            'mazegeeks_e_482379_allow_customer_to_choose_production_location_updates' => (bool) $this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_E_482379_ALLOW_CUSTOMER_TO_CHOOSE_PRODUCTION_LOCATION_UPDATES),
            'sgc_D_236651' => (bool) $this->toggleConfig->getToggleConfigValue(self::SGC_D_236651),
            'tech_titans_D_238830' => (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_238830),
            'tech_titans_D_238086' => (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_238086)
        ];
    }

    /**
     * Get is Logged in
     */
    public function isLoggedIn($isEnableFcl)
    {
        if ($this->selfregHelper->isSelfRegCustomer()) {
            return false;
        }

        return $isEnableFcl;
    }

    /**
     * Get is Pick Up
     */
    public function isFromPickup()
    {
        if ($this->helper->getIsPickup()) {
            return true;
        }

        return false;
    }

    /**
     * Get is from Delivery
     */
    public function isFromDelivery()
    {
        if ($this->helper->getIsDelivery()) {
            return true;
        }

        return false;
    }

    /**
     * Get is from Both
     */
    public function isFromBoth()
    {
        if ($this->helper->getIsPickup() * $this->helper->getIsDelivery()) {
            return true;
        }

        return false;
    }

    /**
     * Get is from FCL Customer
     */
    public function isFromFCLCustomer()
    {
        $sdeFcl = (bool) $this->sdeHelper->getIsRequestFromSdeStoreFclLogin();
        $isCommercialEnhancedProfile = ($sdeFcl || $this->selfregHelper->isSelfRegCustomerWithFclEnabled());
        if ($this->customerSession->getCustomerId() &&
            (!$this->customerSession->getCustomerCompany() || $isCommercialEnhancedProfile)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get is from FCL Customer
     */
    public function isFromSelfRegFcl()
    {
        if ($this->selfregHelper->isSelfRegCustomerWithFclEnabled()) {
            return true;
        }

        return false;
    }


    /**
     * Get default shipping address
     *
     * @return array
     */
    public function getDefaultShippingAddress()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId) {
            if ($this->accountManagement->getDefaultShippingAddress($customerId)) {
                $data = $this->getDefaultShippingAdddressData($customerId);
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Customer has not set default shipping address.');
                $data = [self::STATUS => 'Failure', 'message' => 'Customer has not set default'];
            }
        } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Customer has not set default shipping address.');
            $data = [self::STATUS => 'Failure', 'message' => 'Customer has not set default'];
        }

        return $data;
    }

    /**
     * Get Data for Default Shipping Address
     */
    public function getDefaultShippingAdddressData($customerId)
    {
        $shippingAddress = $this->accountManagement->getDefaultShippingAddress($customerId)->__toArray();

        $firstname = isset($shippingAddress[self::FIRST_NAME]) ? $shippingAddress[self::FIRST_NAME] : '';
        $lastname = isset($shippingAddress[self::LAST_NAME]) ? $shippingAddress[self::LAST_NAME] : '';
        $email = isset($shippingAddress[self::CUSTOM_ATTRIBUTES]['email_id'][self::VALUE]) ?
        $shippingAddress[self::CUSTOM_ATTRIBUTES]['email_id'][self::VALUE] : '';

        $company = isset($shippingAddress[self::COMPANY]) ? $shippingAddress[self::COMPANY] : '';
        $city = isset($shippingAddress['city']) ? $shippingAddress['city'] : '';
        $region = isset($shippingAddress[self::REGION]['region_id']) ?
        $shippingAddress[self::REGION]['region_id'] : '';



        if (($region == 0 || !$region)) {
            $regionName = isset($shippingAddress[self::REGION]['region']) ?
            $shippingAddress[self::REGION]['region'] : '';

            $countryId = isset($shippingAddress['country_id']) ? $shippingAddress['country_id'] : '';

            if ($regionName && $countryId) {
                $region = $this->regionFactory
                    ->create()
                    ->loadByName(strtolower($regionName), $countryId)->getId();
            }
        }
        $postcode = isset($shippingAddress[self::POSTCODE]) ? $shippingAddress[self::POSTCODE] : '';

        $telephone = isset($shippingAddress[self::TELEPHONE]) ?
        '(' . substr($shippingAddress[self::TELEPHONE], 0, 3) . ') '
        . substr($shippingAddress[self::TELEPHONE], 3, 3) . '-'
        . substr($shippingAddress[self::TELEPHONE], 6, 4) : '';

        $ext = isset($shippingAddress[self::CUSTOM_ATTRIBUTES]['ext'][self::VALUE]) ?
        $shippingAddress[self::CUSTOM_ATTRIBUTES]['ext'][self::VALUE] : '';
        $streetOne = isset($shippingAddress[self::STREET][0]) ? $shippingAddress[self::STREET][0] : '';
        $streetTwo = isset($shippingAddress[self::STREET][1]) ? $shippingAddress[self::STREET][1] : '';

        return [
            self::STATUS => 'success',
            self::FIRST_NAME => $firstname,
            self::LAST_NAME => $lastname,
            'email' => $email,
            self::COMPANY => $company,
            'city' => $city,
            self::REGION => $region,
            self::POSTCODE => $postcode,
            self::TELEPHONE => $telephone,
            'ext' => $ext,
            'streetOne' => $streetOne,
            'streetTwo' => $streetTwo,
        ];
    }

    /**
     * Get Combined discount messages
     *
     * @return string
     */
    public function getCombinedDiscountMessage()
    {
        return $this->scopeConfig->getValue(self::NOTIFICATION_PROMOCODE_EDITOR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Terms and condition url
     *
     * @return string
     */
    public function getTermsAndConditionUrl()
    {
        return $this->scopeConfig->getValue(self::TERMS_AND_CONDITION_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $preselectMethod
     * @param $paymentInfo
     * @return array|string|void|null
     */
    public function getPaymentMethodDefaultValue($preselectMethod, $paymentInfo) {


        if ($paymentInfo && (
            (is_array($paymentInfo) && isset($paymentInfo[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                && $preselectMethod == PaymentOptions::FEDEX_ACCOUNT_NUMBER
            )
            || is_string($paymentInfo))) {

            $fedexAccountNumber = $paymentInfo[PaymentOptions::FEDEX_ACCOUNT_NUMBER] ?? '';
            if (!$fedexAccountNumber && is_string($paymentInfo) && $paymentInfo != '') {
                $fedexAccountNumber = $paymentInfo;
            } elseif(!$fedexAccountNumber) {
                $personalAccountList = $this->accountHelper->getActivePersonalAccountList('PAYMENT');
                if (!empty($personalAccountList)) {

                    $fedexAccountNumber = array_search(
                        1,
                        array_column($personalAccountList, 'selected', 'account_number')
                    );
                    if (!$fedexAccountNumber) {
                        $fedexAccountNumber = array_first($personalAccountList)['account_number'];
                    }
                }
            }

            return (string)$fedexAccountNumber;
        } elseif ($paymentInfo && is_array($paymentInfo) && $preselectMethod == PaymentOptions::CREDIT_CARD) {

            $cc = $paymentInfo[PaymentOptions::CREDIT_CARD] ?? '';
            if (!$cc && isset($paymentInfo['ccNumber'])) {
                $cc = $paymentInfo;
            }

            return $cc;
        }

        return null;
    }

    /**
     * Get Shipping Account Acknowledgement Message
     *
     * @return bool
     */
    public function getShippingAccountAcknowledgementMessage()
    {
        return $this->scopeConfig->getValue(self::SHIPPING_ACCOUNT_ACKNOWLEDGEMENT_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Shipping Account Acknowledgement Error Message
     *
     * @return bool
     */
    public function getShippingAccountAcknowledgementErrorMessage()
    {
        return $this->scopeConfig->getValue(self::SHIPPING_ACCOUNT_ACKNOWLEDGEMENT_ERROR_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

}
