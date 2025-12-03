<?php
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Plugin\Checkout\LayoutProcessorPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Block\Checkout\LayoutProcessor;

class LayoutProcessorPluginTest extends TestCase
{
    private $toggleConfig;
    private $scopeConfig;
    private $customerSession;
    private $plugin;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->customerSession = $this->createMock(CustomerSession::class);

        $this->plugin = new LayoutProcessorPlugin(
            $this->toggleConfig,
            $this->scopeConfig,
            $this->customerSession
        );
    }

    public function testAfterProcessWithFeatureDisabled()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);
        $subject = $this->createMock(LayoutProcessor::class);
        $result = ['components' => ['checkout' => ['children' => ['steps' => ['children' => ['shipping-step' => ['children' => ['shippingAddress' => ['children' => []]]]]]]]]];
        $jsLayout = [];

        $output = $this->plugin->afterProcess($subject, $result, $jsLayout);
        $this->assertEquals($result, $output);
    }

    public function testAfterProcessWithFeatureEnabled()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->scopeConfig->method('getValue')->willReturn('Title');
        $this->customerSession->method('isLoggedIn')->willReturn(false);

        $subject = $this->createMock(LayoutProcessor::class);
        $result = ['components' => ['checkout' => ['children' => ['steps' => ['children' => ['shipping-step' => ['children' => ['shippingAddress' => ['children' => []]]]]]]]]];
        $jsLayout = [];

        $output = $this->plugin->afterProcess($subject, $result, $jsLayout);

        $this->assertArrayHasKey('fedex-shipping-account', $output['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']);
    }

    public function testGetLayoutConfigForFedexShippingAccountField()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getLayoutConfigForFedexShippingAccountField');
        $method->setAccessible(true);

        $this->scopeConfig->method('getValue')->willReturn('Title');
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->method('isLoggedIn')->willReturn(false);

        $result = $method->invoke($this->plugin);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('children', $result);
        $this->assertArrayHasKey('fedex-shipping-account', $result['children']);
    }

    public function testIsTigerE486666EnabledTrue()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('isTigerE486666Enabled');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->plugin));
    }

    public function testIsTigerE486666EnabledFalse()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('isTigerE486666Enabled');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxTitleLoggedInWithValue()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_TITLE_CUSTOMER)->willReturn('CustomerBoxTitle');

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxTitle');
        $method->setAccessible(true);

        $this->assertEquals('CustomerBoxTitle', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxTitleLoggedInWithNull()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_TITLE_CUSTOMER)->willReturn(null);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxTitle');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxTitleGuestWithValue()
    {
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_TITLE_GUEST)->willReturn('GuestBoxTitle');

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxTitle');
        $method->setAccessible(true);

        $this->assertEquals('GuestBoxTitle', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxTitleGuestWithNull()
    {
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_TITLE_GUEST)->willReturn(null);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxTitle');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxDescriptionLoggedInWithValue()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_DESCRIPTION_CUSTOMER)->willReturn('CustomerDesc');

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxDescription');
        $method->setAccessible(true);

        $this->assertEquals('CustomerDesc', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxDescriptionLoggedInWithNull()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_DESCRIPTION_CUSTOMER)->willReturn(null);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxDescription');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxDescriptionGuestWithValue()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_DESCRIPTION_GUEST)->willReturn('GuestDesc');

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxDescription');
        $method->setAccessible(true);

        $this->assertEquals('GuestDesc', $method->invoke($this->plugin));
    }

    public function testGetFedexShippingAccountBoxDescriptionGuestWithNull()
    {
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->scopeConfig->method('getValue')->with(LayoutProcessorPlugin::SHIPPING_ACCOUNT_BOX_DESCRIPTION_GUEST)->willReturn(null);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getFedexShippingAccountBoxDescription');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->plugin));
    }
}
