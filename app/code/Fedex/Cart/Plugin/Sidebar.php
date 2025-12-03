<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\Plugin;

use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\Catalog\Model\Config as CatalogConfig;
use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\Recaptcha\Api\Data\ConfigInterface as RecaptchaConfigInterface;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Fedex\UploadToQuote\Model\Config;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\FormatInterface as LocaleFormat;
use Magento\Framework\View\Asset\Repository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/*
 * Sidebar plugin class
 */
class Sidebar
{
    /* Get product engine url*/
    public const MIX_CART_PRODUCT_ENGINE_URL = 'product_engine/general/url';
    public const BACKEND_DUNC_CALL = 'by_backend_dunc_call_optimization';
    public const HAWKS_REMOVE_DUPLICATE_LOGIN_CALLS = 'hawks_remove_duplicate_login_calls';
    public const XMEN_UPLOAD_TO_QUOTE = 'xmen_upload_to_quote';
    public const USER_ROLES_PERMISSION = 'change_customer_roles_and_permissions';
    public const COMPANY_SETTINGS_TOGGLE = 'explorers_company_settings_customer_admin';

    public const EXPLORERS_DELETE_CART_ITEM_CONFIMATION_MODAL = 'explorers_delete_cart_items_confirmation_modal';
    public const TECHTITANS_FIX_ALLOW_FILE_UPLOAD_ISSUE = 'tech_titans_d_177591_fix_allow_file_upload_issue';
    public const TECHTITANS_REMOVE_NEW_PROJECT_CTA = 'tech_titans_d_182865_remove_new_project_cta';
    public const XMEN_ORDER_CONFIRMATION_FIX = 'xmen_order_confirmation_fix';
    public const SGC_ENABLE_EXPECTED_DELIVERY = 'sgc_enable_expected_delivery_date';
    public const TECHTITANS_CALENDER_HIDE_ISSUE_TOGGLE = 'tech_titans_d_187090';
    public const SGC_REVIEW_SUBMIT_ORDER_CONFIRMATION_CANCELLATION_MESSAGE =
        'fedex/marketplace_configuration/review_and_submit_and_order_confirmation_cancellation_message';
    public const EXPLORERS_EPRO_U2Q = 'explorers_epro_upload_to_quote';
    public const EXPLORERS_D190723_FIX = 'd_190723_fix';
    public const EXPLORERS_ALLOW_FILE_UPLOAD_CATALOG_FLOW = 'explorers_allow_file_upload_catalog_flow';
    public const EXPLORERS_PERSONAL_ADDRESS_BOOK = 'explorers_e_450676_personal_address_book';
    public const REMOVE_BASE64_TOGGLE = 'is_remove_base64_image';
    public const IS_B2421984_ENABLED = 'tech_titans_b_2421984_remove_preview_calls_from_catalog_flow';
    public const TIGER_B_2386818 = 'tiger_b2386818';
    public const TIGER_E_468338 = 'tiger_e468338';
    public const TIGER_D_213919 = 'tiger_d_213919_marketplace_seller_downtime_message_fix';

    public const TIGER_TOP_MENU_EXCLUDED_CLASSES = 'ondemand_setting/top_menu_settings/excluded_classes';
    public const TIGER_D219954 = 'tiger_d219954';
    private const TECH_TITANS_D217639 = 'tech_titans_d_217639';

    public const TIGER_TEAM_D_217182_FIX = 'tiger_team_d_217182';
    
    // Tech Titans Toggle D-220270 List View Not working After Switching from Grid View
    private const TECH_TITANS_D220270 = 'tech_titans_d_220270';

    /**
     * Sidebar Constructor
     *
     * @param SdeHelper $sdeHelper
     * @param CheckoutConfig $checkoutConfig
     * @param SsoConfiguration $ssoConfiguration
     * @param Data $deliveryHelper
     * @param ToggleConfig $toggleConfig
     * @param Session $customerSession
     * @param SubmitOrderModelAPI $submitOrderModelAPI
     * @param StoreManagerInterface $storeManager
     * @param HandleMktCheckout $handleMktCheckout
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param ConfigInterface $marketinOptInConfig
     * @param RecaptchaConfigInterface $recaptchaConfig
     * @param Repository $assetRepository
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param CatalogConfig $catalogConfig
     * @param LocaleFormat $localeFormat
     * @param FuseBidViewModel $fuseBidViewModel
     * @param NonCustomizableProduct $nonCustomizableProduct
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        protected SdeHelper $sdeHelper,
        protected CheckoutConfig $checkoutConfig,
        protected SsoConfiguration $ssoConfiguration,
        protected Data $deliveryHelper,
        protected ToggleConfig $toggleConfig,
        protected Session $customerSession,
        protected SubmitOrderModelAPI $submitOrderModelAPI,
        protected StoreManagerInterface $storeManager,
        private HandleMktCheckout $handleMktCheckout,
        private ScopeConfigInterface $scopeConfigInterface,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected ConfigInterface $marketinOptInConfig,
        protected RecaptchaConfigInterface $recaptchaConfig,
        protected Repository $assetRepository,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        protected CatalogConfig $catalogConfig,
        protected LocaleFormat $localeFormat,
        protected FuseBidViewModel $fuseBidViewModel,
        private NonCustomizableProduct $nonCustomizableProduct,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * Set document office api url
     *
     * @param object $subject
     * @param array $result
     * @return array $result
     */
    public function afterGetConfig(\Magento\Checkout\Block\Cart\Sidebar $subject, $result)
    {
        $mixCartProductEngineURL = $this->toggleConfig->getToggleConfig(self::MIX_CART_PRODUCT_ENGINE_URL);
        $duncCallBackEnd = (bool) $this->toggleConfig->getToggleConfigValue(self::BACKEND_DUNC_CALL);
        $isUploadToQuoteEnable = $this->uploadToQuoteViewModel->isUploadToQuoteEnable();
        $explorersDeletedCartItemConfimationModal = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_DELETE_CART_ITEM_CONFIMATION_MODAL
        );
        $isUserRolesPermission = (bool) $this->toggleConfig->getToggleConfigValue(
            self::USER_ROLES_PERMISSION
        );
        $explorersCompanySettingsToggle = (bool) $this->toggleConfig->getToggleConfigValue(
            self::COMPANY_SETTINGS_TOGGLE
        );
        $arrUploadToQuoteConfigValues = [
            'enableUploadToQuote' => $isUploadToQuoteEnable,
            'title' => $this->uploadToQuoteViewModel->getUploadToQuoteConfigValue('quote_request_title'),
            'message' => $this->uploadToQuoteViewModel->getUploadToQuoteConfigValue('quote_request_message'),
            'nonStandardSizeWarningMessage' => $this->uploadToQuoteViewModel->getUploadToQuoteConfigValue(
                'standard_size_warning_message'
            ),
        ];
        $deleteItemAlertIconImage = $this->assetRepository->getUrl('Fedex_Cart::images/warning-icon.png');
        $result['dunc_office_api_url'] =  $this->checkoutConfig->getDocumentOfficeApiUrl() ?? '';
        $result['is_fcl_customer'] = $this->ssoConfiguration->isFclCustomer();
        $result['is_commercial'] = $this->deliveryHelper->isCommercialCustomer();
        $result["mix_cart_product_engine_url"] = $mixCartProductEngineURL;
        $result["is_out_sourced"] =  $this->deliveryHelper->isOurSourced();
        $result['is_sde_store'] = $this->sdeHelper->getIsSdeStore();
        $canShowSensativeMessage = $this->sdeHelper->isFacingMsgEnable() && $this->sdeHelper->getIsSdeStore();
        $result['can_show_sensative_message'] = $canShowSensativeMessage;
        $result['is_retail'] = $this->ssoConfiguration->isRetail();
        $result['retail_profile_session'] = $this->customerSession->getProfileSession();
        $result['fedex_account_number'] = $this->getFedExAccount();
        $result['transaction_response'] = $this->submitOrderModelAPI->getTransactionAPIResponse(
            $this->checkoutConfig->getCurrentActiveQuote(),
            null,
            false
        );
        $result['store_code'] = $this->storeManager->getStore()->getCode();
        $result['dunc_request_response_data'] = $this->customerSession->getDuncResponse();
        $result['by_backend_dunc_call_optimization'] = $duncCallBackEnd;
        $result['hawks_remove_duplicate_login_calls'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::HAWKS_REMOVE_DUPLICATE_LOGIN_CALLS
        );
        $result['xmen_upload_to_quote'] = $this->uploadToQuoteViewModel->isUploadToQuoteEnable();
        $result['marketing_opt_in'] = $this->getMarketingOptInInfo();
        $result['upload_to_quote_config_values'] = json_encode($arrUploadToQuoteConfigValues);
        $result['user_roles_permission'] = $isUserRolesPermission;
        $result['explorers_company_settings_toggle'] = $explorersCompanySettingsToggle;
        $result['tiger_google_recaptcha_site_key'] = $this->recaptchaConfig->getPublicKey();
        $result['tiger_display_unit_cost_3p_1p_products_toggle'] = $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts();
        $result['priceFormat'] = $this->localeFormat->getPriceFormat();
        $result['is_quote_price_is_dashable'] = $this->uploadToQuoteViewModel->checkoutQuotePriceisDashable();
        $result['explorers_delete_cart_items_confirmation_modal'] = $explorersDeletedCartItemConfimationModal;
        $result['alert_icon_image'] = $deleteItemAlertIconImage;
        $result['fcl_cookie_config_value'] = $this->ssoConfiguration->getFCLCookieNameToggle()?
        $this->ssoConfiguration->getFCLCookieConfigValue() : 'fdx_login';
        $result['b2b_order_scucess_toast_msg'] = $this->orderApprovalViewModel->getB2bOrderApprovalConfigValue('order_success_toast_msg');
        $result['info_icon_image'] = $this->assetRepository->getUrl('images/commercial-order-approval/info-icon.png');;
        $result['xmen_jump_link_tab'] = (bool) $this->toggleConfig->getToggleConfigValue(
            'xmen_jump_link_tab');
        $result['fix_allow_file_upload_issue'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TECHTITANS_FIX_ALLOW_FILE_UPLOAD_ISSUE
        );
        $result['xmen_order_confirmation_fix'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::XMEN_ORDER_CONFIRMATION_FIX
        );
        $result['is_expected_delivery_date_enabled'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::SGC_ENABLE_EXPECTED_DELIVERY
        );
        $result['remove_new_project_cta'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TECHTITANS_REMOVE_NEW_PROJECT_CTA
        );
        $result['is_calender_open_issue_toggle_enabled'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::TECHTITANS_CALENDER_HIDE_ISSUE_TOGGLE
        );
        $result['order_confirmation_cancellation_message'] = (string) $this->toggleConfig->getToggleConfig(
            self::SGC_REVIEW_SUBMIT_ORDER_CONFIRMATION_CANCELLATION_MESSAGE
        );
	$result['is_u2q_toggle_enabled'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_EPRO_U2Q
        );
        $result['d_190723_fix'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_D190723_FIX
        );

        $result['is_ten_categories_fix_toggle_enable'] = (bool) $this->toggleConfig->getToggleConfigValue('tech_titans_d_182861');
        $result['is_cbb_toggle_enable'] = $this->nonCustomizableProduct->isMktCbbEnabled();

        $result['is_fusebid_toggle_enabled'] = $this->fuseBidViewModel->isFuseBidToggleEnabled();
        $result['my_quotes_maitenace_fix_toggle'] = (bool) $this->toggleConfig->getToggleConfigValue(
            'mazegeek_team_d193943_my_quotes_maitenace_fix'
        );
        $result['mazegeeks_improving_update_item_qty_cart'] = (bool) $this->toggleConfig->getToggleConfigValue(
            'mazegeeks_improving_update_item_qty_cart'
        );
        $result['tiger_enable_essendant'] = (bool) $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
        $order = $this->getLastOrderFromCustomerSession();
        $result['only_non_customizable_cart'] = $this->marketplaceCheckoutHelper->isEssendantToggleEnabled() && $order &&
            $this->marketplaceCheckoutHelper->checkIfItemsAreAllNonCustomizableProduct($order);
        $result['allow_file_upload_catalog_flow'] = (bool) $this->toggleConfig->getToggleConfigValue(
            self::EXPLORERS_ALLOW_FILE_UPLOAD_CATALOG_FLOW
        );
        $result['explorers_personal_address_book'] = (bool) $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_PERSONAL_ADDRESS_BOOK);

        /** B-2353103 : Return if Base64 Img Toggle Enabled*/
        $result['is_remove_base64_image'] = (bool) $this->toggleConfig->getToggleConfigValue(self::REMOVE_BASE64_TOGGLE);
        /** B-2353103 : Return if Base64 Img Toggle Enabled*/
        $result['preview_api_url'] = $this->checkoutConfig->getDocumentImagePreviewUrl() ?? '';
        $result['document_image_preview_url'] = $this->checkoutConfig->getDocumentImagePreviewUrl() ?? '';
        $result['tech_titans_b_2421984_remove_preview_calls_from_catalog_flow'] = (bool) $this->toggleConfig->getToggleConfigValue(self::IS_B2421984_ENABLED);
        $result['tiger_d_213919_marketplace_seller_downtime_message_fix'] = (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D_213919);
        /** D-217896 - Top Menu optimization */
        $result['tiger_top_menu_excluded_classes'] = $this->toggleConfig->getToggleConfig(SELF::TIGER_TOP_MENU_EXCLUDED_CLASSES) ?? '';
        $result['tiger_d219954'] = $this->toggleConfig->getToggleConfigValue(self::TIGER_D219954) ?? '';
        $result['tech_titans_d_217639'] = (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_D217639);

        $result['tiger_team_d_217182'] = (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TEAM_D_217182_FIX);
        // Tech Titans Toggle D-220270 List View Not working After Switching from Grid View
        $result['tech_titans_d220270'] = (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_D220270);
        $result['tiger_e468338'] =  (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_E_468338);
        
        return $result;
    }

    /**
     * Get FedEx Account
     *
     * @return string
     */
    public function getFedExAccount()
    {
        $quote = $this->checkoutConfig->getCurrentActiveQuote();
        return $quote->getData("fedex_account_number");
    }

    /**
     * @return array
     */
    protected function getMarketingOptInInfo() {
        return [
            'enabled' => $this->marketinOptInConfig->isMarketingOptInEnabled(),
            'url' => $this->marketinOptInConfig->getMarketingOptInUrlSuccessPage()
        ];
    }

    private function getLastOrderFromCustomerSession()
    {
        if($this->customerSession->getLastOrderId()) {
            $lastOrderId = $this->customerSession->getLastOrderId();
            try {
                return $this->orderRepository->get($lastOrderId);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
