<?php

namespace Fedex\Delivery\Test\Unit\Plugin\Checkout\Block\Checkout;

use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Plugin\Checkout\Block\Checkout\LayoutProcessorPlugin;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;

class LayoutProcessorPluginTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $packagingCheckoutPricing;
    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfigMock;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getLoggedAsCustomerAdminIdMock;

    /**
     * @var LayoutProcessorPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        // Mock ToggleConfig
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        // Mock GetLoggedAsCustomerAdminIdInterface
        $this->getLoggedAsCustomerAdminIdMock = $this->createMock(GetLoggedAsCustomerAdminIdInterface::class);
        $this->packagingCheckoutPricing = $this->createMock(PackagingCheckoutPricing::class);

        // Initialize the plugin with mocked dependencies
        $this->plugin = new LayoutProcessorPlugin(
            $this->toggleConfigMock,
            $this->packagingCheckoutPricing,
            $this->getLoggedAsCustomerAdminIdMock
        );
    }

    /**
     * Test the afterProcess method when impersonator is disabled
     * and no admin ID is found.
     */
    public function testAfterProcessWithoutImpersonator()
    {
        // Mock that execute() returns null (no admin ID)
        $this->getLoggedAsCustomerAdminIdMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn(0);

        // Mock that the toggleConfig returns false (impersonator disabled)
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(false);

        // Sample result array before the plugin processes it
        $result = [
            'components' => [
                'checkout' => [
                    'children' => [
                        'steps' => [
                            'children' => [
                                'shipping-step' => [
                                    'children' => [
                                        'shippingAddress' => [
                                            'children' => [
                                                'shipping-address-fieldset' => [
                                                    'children' => [
                                                        'firstname' => ['value' => 'John'],
                                                        'lastname' => ['value' => 'Doe'],
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Empty jsLayout for this test
        $jsLayout = [];

        // Execute the plugin's afterProcess method
        $actualResult = $this->plugin->afterProcess($this->createMock(LayoutProcessor::class), $result, $jsLayout);

        // Assert that the result remains unchanged
        $this->assertEquals($result, $actualResult);
    }

    /**
     * Test the afterProcess method when impersonator is enabled
     * and an admin ID is present.
     */
    public function testAfterProcessWithImpersonatorEnabledAndAdminId()
    {
        // Mock that execute() returns a valid admin ID (e.g., 1)
        $this->getLoggedAsCustomerAdminIdMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn(436);

        // Mock that the toggleConfig returns true (impersonator enabled)
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(true);
        
            // Sample result array before the plugin processes it
        $result = [
            'components' => [
                'checkout' => [
                    'children' => [
                        'steps' => [
                            'children' => [
                                'shipping-step' => [
                                    'children' => [
                                        'shippingAddress' => [
                                            'children' => [
                                                'shipping-address-fieldset' => [
                                                    'children' => [
                                                        'firstname' => ['value' => ''],
                                                        'lastname' => ['value' => ''],
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Empty jsLayout for this test
        $jsLayout = [];

        // Execute the plugin's afterProcess method
        $actualResult = $this->plugin->afterProcess($this->createMock(LayoutProcessor::class), $result, $jsLayout);

        // Assert that the result remains unchanged
        $this->assertEquals($result, $actualResult);
    }
}
               
