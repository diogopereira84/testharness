<?php

namespace Fedex\Cart\Test\Unit\Plugin;

use Fedex\Cart\Plugin\Sidebar;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SidebarTest extends TestCase
{
    private Sidebar $sidebar;
    private $sdeHelper;
    private $checkoutConfig;
    private $ssoConfiguration;
    private $deliveryHelper;
    private $toggleConfig;
    private $customerSession;
    private $submitOrderModelAPI;
    private $storeManager;
    private $handleMktCheckout;
    private $scopeConfigInterface;
    private $uploadToQuoteViewModel;
    private $marketinOptInConfig;
    private $recaptchaConfig;
    private $assetRepository;
    private $orderApprovalViewModel;
    private $catalogConfig;
    private $localeFormat;
    private $fuseBidViewModel;
    private $nonCustomizableProduct;
    private $marketplaceCheckoutHelper;
    private $orderRepository;

    protected function setUp(): void
    {
        $this->sdeHelper = $this->createMock(\Fedex\SDE\Helper\SdeHelper::class);
        $this->checkoutConfig = $this->createMock(\Fedex\Cart\ViewModel\CheckoutConfig::class);
        $this->ssoConfiguration = $this->createMock(\Fedex\SSO\ViewModel\SsoConfiguration::class);
        $this->deliveryHelper = $this->createMock(\Fedex\Delivery\Helper\Data::class);
        $this->toggleConfig = $this->createMock(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class);
        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProfileSession', 'getDuncResponse', 'getLastOrderId'])
            ->getMock();
        $this->submitOrderModelAPI = $this->createMock(\Fedex\SubmitOrderSidebar\Model\SubmitOrderApi::class);
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->getMockForAbstractClass();
        $this->handleMktCheckout = $this->createMock(\Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout::class);
        $this->scopeConfigInterface = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->uploadToQuoteViewModel = $this->createMock(\Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel::class);
        $this->marketinOptInConfig = $this->createMock(\Fedex\Customer\Api\Data\ConfigInterface::class);
        $this->recaptchaConfig = $this->createMock(\Fedex\Recaptcha\Api\Data\ConfigInterface::class);
        $this->assetRepository = $this->createMock(\Magento\Framework\View\Asset\Repository::class);
        $this->orderApprovalViewModel = $this->createMock(\Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel::class);
        $this->catalogConfig = $this->createMock(\Fedex\Catalog\Model\Config::class);
        $this->localeFormat = $this->createMock(\Magento\Framework\Locale\FormatInterface::class);
        $this->fuseBidViewModel = $this->createMock(\Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel::class);
        $this->nonCustomizableProduct = $this->createMock(\Fedex\MarketplaceProduct\Model\NonCustomizableProduct::class);
        $this->marketplaceCheckoutHelper = $this->createMock(\Fedex\MarketplaceCheckout\Helper\Data::class);
        $this->orderRepository = $this->createMock(\Magento\Sales\Api\OrderRepositoryInterface::class);

        $this->sidebar = new Sidebar(
            $this->sdeHelper,
            $this->checkoutConfig,
            $this->ssoConfiguration,
            $this->deliveryHelper,
            $this->toggleConfig,
            $this->customerSession,
            $this->submitOrderModelAPI,
            $this->storeManager,
            $this->handleMktCheckout,
            $this->scopeConfigInterface,
            $this->uploadToQuoteViewModel,
            $this->marketinOptInConfig,
            $this->recaptchaConfig,
            $this->assetRepository,
            $this->orderApprovalViewModel,
            $this->catalogConfig,
            $this->localeFormat,
            $this->fuseBidViewModel,
            $this->nonCustomizableProduct,
            $this->marketplaceCheckoutHelper,
            $this->orderRepository
        );
    }

    public function testAfterGetConfigCoversAllBranches()
    {
        $sidebarBlock = $this->createMock(\Magento\Checkout\Block\Cart\Sidebar::class);
        $result = ['url' => 'https://magento.com'];

        $this->toggleConfig->method('getToggleConfig')->willReturnMap([
            [Sidebar::MIX_CART_PRODUCT_ENGINE_URL, 'engine_url'],
            [Sidebar::SGC_REVIEW_SUBMIT_ORDER_CONFIRMATION_CANCELLATION_MESSAGE, 'cancel_msg'],
            [Sidebar::TIGER_TOP_MENU_EXCLUDED_CLASSES, 'excluded_classes'],
        ]);
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(1);

        $this->uploadToQuoteViewModel->method('isUploadToQuoteEnable')->willReturn(true);
        $this->uploadToQuoteViewModel->method('getUploadToQuoteConfigValue')->willReturn('value');
        $this->uploadToQuoteViewModel->method('checkoutQuotePriceisDashable')->willReturn(true);

        $this->assetRepository->method('getUrl')->willReturn('image_url');

        $this->checkoutConfig->method('getDocumentOfficeApiUrl')->willReturn('api_url');
        $this->checkoutConfig->method('getCurrentActiveQuote')->willReturn($this->createConfiguredMock(\Magento\Quote\Model\Quote::class, [
            'getData' => '12345678'
        ]));
        $this->checkoutConfig->method('getDocumentImagePreviewUrl')->willReturn('preview_url');

        $this->ssoConfiguration->method('isFclCustomer')->willReturn(true);
        $this->ssoConfiguration->method('isRetail')->willReturn(true);
        $this->ssoConfiguration->method('getFCLCookieNameToggle')->willReturn(true);
        $this->ssoConfiguration->method('getFCLCookieConfigValue')->willReturn('cookie_value');

        $this->deliveryHelper->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelper->method('isOurSourced')->willReturn(true);

        $this->sdeHelper->method('getIsSdeStore')->willReturn(true);
        $this->sdeHelper->method('isFacingMsgEnable')->willReturn(true);

        $this->customerSession->method('getProfileSession')->willReturn([]);
        $this->customerSession->method('getDuncResponse')->willReturn([]);
        $this->customerSession->method('getLastOrderId')->willReturn(1);

        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderRepository->method('get')->willReturn($orderMock);

        $this->submitOrderModelAPI->method('getTransactionAPIResponse')->willReturn([]);

        $this->storeManager->method('getStore')->willReturnSelf();
        $this->storeManager->method('getCode')->willReturn('default');

        $this->marketinOptInConfig->method('isMarketingOptInEnabled')->willReturn(true);
        $this->marketinOptInConfig->method('getMarketingOptInUrlSuccessPage')->willReturn('url');

        $this->recaptchaConfig->method('getPublicKey')->willReturn('recaptcha_key');
        $this->catalogConfig->method('getTigerDisplayUnitCost3P1PProducts')->willReturn(true);
        $this->localeFormat->method('getPriceFormat')->willReturn(['pattern' => '$%s']);

        $this->orderApprovalViewModel->method('getB2bOrderApprovalConfigValue')->willReturn('toast_msg');
        $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);
        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->marketplaceCheckoutHelper->method('checkIfItemsAreAllNonCustomizableProduct')->willReturn(true);

        $finalResult = $this->sidebar->afterGetConfig($sidebarBlock, $result);

        $this->assertIsArray($finalResult);
        $this->assertArrayHasKey('dunc_office_api_url', $finalResult);
        $this->assertArrayHasKey('is_fcl_customer', $finalResult);
        $this->assertArrayHasKey('is_commercial', $finalResult);
        $this->assertArrayHasKey('mix_cart_product_engine_url', $finalResult);
        $this->assertArrayHasKey('is_out_sourced', $finalResult);
        $this->assertArrayHasKey('is_sde_store', $finalResult);
        $this->assertArrayHasKey('can_show_sensative_message', $finalResult);
        $this->assertArrayHasKey('is_retail', $finalResult);
        $this->assertArrayHasKey('retail_profile_session', $finalResult);
        $this->assertArrayHasKey('fedex_account_number', $finalResult);
        $this->assertArrayHasKey('transaction_response', $finalResult);
        $this->assertArrayHasKey('store_code', $finalResult);
        $this->assertArrayHasKey('dunc_request_response_data', $finalResult);
        $this->assertArrayHasKey('by_backend_dunc_call_optimization', $finalResult);
        $this->assertArrayHasKey('hawks_remove_duplicate_login_calls', $finalResult);
        $this->assertArrayHasKey('xmen_upload_to_quote', $finalResult);
        $this->assertArrayHasKey('marketing_opt_in', $finalResult);
        $this->assertArrayHasKey('upload_to_quote_config_values', $finalResult);
        $this->assertArrayHasKey('user_roles_permission', $finalResult);
        $this->assertArrayHasKey('explorers_company_settings_toggle', $finalResult);
        $this->assertArrayHasKey('tiger_google_recaptcha_site_key', $finalResult);
        $this->assertArrayHasKey('tiger_display_unit_cost_3p_1p_products_toggle', $finalResult);
        $this->assertArrayHasKey('priceFormat', $finalResult);
        $this->assertArrayHasKey('is_quote_price_is_dashable', $finalResult);
        $this->assertArrayHasKey('explorers_delete_cart_items_confirmation_modal', $finalResult);
        $this->assertArrayHasKey('alert_icon_image', $finalResult);
        $this->assertArrayHasKey('fcl_cookie_config_value', $finalResult);
        $this->assertArrayHasKey('b2b_order_scucess_toast_msg', $finalResult);
        $this->assertArrayHasKey('info_icon_image', $finalResult);
        $this->assertArrayHasKey('xmen_jump_link_tab', $finalResult);
        $this->assertArrayHasKey('fix_allow_file_upload_issue', $finalResult);
        $this->assertArrayHasKey('xmen_order_confirmation_fix', $finalResult);
        $this->assertArrayHasKey('is_expected_delivery_date_enabled', $finalResult);
        $this->assertArrayHasKey('remove_new_project_cta', $finalResult);
        $this->assertArrayHasKey('is_calender_open_issue_toggle_enabled', $finalResult);
        $this->assertArrayHasKey('order_confirmation_cancellation_message', $finalResult);
        $this->assertArrayHasKey('is_u2q_toggle_enabled', $finalResult);
        $this->assertArrayHasKey('d_190723_fix', $finalResult);
        $this->assertArrayHasKey('is_ten_categories_fix_toggle_enable', $finalResult);
        $this->assertArrayHasKey('is_cbb_toggle_enable', $finalResult);
        $this->assertArrayHasKey('is_fusebid_toggle_enabled', $finalResult);
        $this->assertArrayHasKey('my_quotes_maitenace_fix_toggle', $finalResult);
        $this->assertArrayHasKey('mazegeeks_improving_update_item_qty_cart', $finalResult);
        $this->assertArrayHasKey('tiger_enable_essendant', $finalResult);
        $this->assertArrayHasKey('only_non_customizable_cart', $finalResult);
        $this->assertArrayHasKey('allow_file_upload_catalog_flow', $finalResult);
        $this->assertArrayHasKey('explorers_personal_address_book', $finalResult);
        $this->assertArrayHasKey('is_remove_base64_image', $finalResult);
        $this->assertArrayHasKey('preview_api_url', $finalResult);
        $this->assertArrayHasKey('document_image_preview_url', $finalResult);
        $this->assertArrayHasKey('tech_titans_b_2421984_remove_preview_calls_from_catalog_flow', $finalResult);
        $this->assertArrayHasKey('tiger_d_213919_marketplace_seller_downtime_message_fix', $finalResult);
        $this->assertArrayHasKey('tiger_top_menu_excluded_classes', $finalResult);
        $this->assertArrayHasKey('tiger_d219954', $finalResult);
        $this->assertArrayHasKey('tech_titans_d_217639', $finalResult);
        $this->assertArrayHasKey('tech_titans_d220270', $finalResult);
    }

    public function testGetFedExAccountReturnsAccountNumber()
    {
        $quoteMock = $this->createConfiguredMock(\Magento\Quote\Model\Quote::class, [
            'getData' => '12345678'
        ]);
        $this->checkoutConfig->method('getCurrentActiveQuote')->willReturn($quoteMock);

        $this->assertEquals('12345678', $this->sidebar->getFedExAccount());
    }

    public function testGetMarketingOptInInfoReturnsArray()
    {
        $this->marketinOptInConfig->method('isMarketingOptInEnabled')->willReturn(true);
        $this->marketinOptInConfig->method('getMarketingOptInUrlSuccessPage')->willReturn('url');

        $result = (new \ReflectionClass($this->sidebar))
            ->getMethod('getMarketingOptInInfo');
        $result->setAccessible(true);

        $info = $result->invoke($this->sidebar);

        $this->assertIsArray($info);
        $this->assertTrue($info['enabled']);
        $this->assertEquals('url', $info['url']);
    }

    public function testGetLastOrderFromCustomerSessionReturnsOrder()
    {
        $this->customerSession->method('getLastOrderId')->willReturn(1);
        $orderMock = $this->createMock(\Magento\Sales\Api\Data\OrderInterface::class);
        $this->orderRepository->method('get')->willReturn($orderMock);

        $method = (new \ReflectionClass($this->sidebar))->getMethod('getLastOrderFromCustomerSession');
        $method->setAccessible(true);

        $this->assertSame($orderMock, $method->invoke($this->sidebar));
    }

    public function testGetLastOrderFromCustomerSessionReturnsNullOnException()
    {
        $this->customerSession->method('getLastOrderId')->willReturn(1);
        $this->orderRepository->method('get')->willThrowException(new \Exception('error'));

        $method = (new \ReflectionClass($this->sidebar))->getMethod('getLastOrderFromCustomerSession');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->sidebar));
    }

    public function testGetLastOrderFromCustomerSessionReturnsNullIfNoOrderId()
    {
        $this->customerSession->method('getLastOrderId')->willReturn(null);

        $method = (new \ReflectionClass($this->sidebar))->getMethod('getLastOrderFromCustomerSession');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->sidebar));
    }
}
