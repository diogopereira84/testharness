<?php

/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Block\Order\Email;

use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Block\Order\Email\Items;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class ItemsTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Pricing\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $priceHelperMock;
    /**
     * @var (\Magento\Store\Model\App\Emulation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $emulationMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    /**
     * @var (\Fedex\MarketplaceRates\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketPlacehelper;
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $itemsBlockMock;
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    private $marketplaceCheckoutHelper;

    /**
     * Sets up the test environment before each test is executed.
     *
     * This method is automatically invoked before every test method to initialize
     * necessary objects and configurations for consistent test execution.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceHelperMock = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->emulationMock = $this->getMockBuilder(Emulation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->onlyMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketPlacehelper = $this->getMockBuilder(MarketplaceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);

        $this->itemsBlockMock = $this->getMockBuilder(Items::class)
            ->onlyMethods(['getViewFileUrl'])
            ->addMethods(['getShipmentItems'])
            ->setConstructorArgs(
                [
                    $this->contextMock,
                    $this->priceHelperMock,
                    $this->emulationMock,
                    $this->toggleConfigMock,
                    $this->marketPlacehelper,
                    $this->marketplaceCheckoutHelper
                ]
            )
            ->getMock();
    }

    /**
     * Tests the formatted currency value.
     *
     * This method validates that the currency value is formatted correctly according to the expected locale and format.
     *
     * @return void
     */
    public function testFormattedCurrencyValue()
    {
        $value = 100.00;
        $formattedValue = '$100.00';

        $this->priceHelperMock->expects($this->once())
            ->method('currency')
            ->with($value, true, false)
            ->willReturn($formattedValue);

        $result = $this->itemsBlockMock->formattedCurrencyValue($value);

        $this->assertEquals($formattedValue, $result);
    }

    /**
     * Test that the getViewFileUrl method returns the correct view file URL.
     *
     * This unit test ensures that the logic for generating the view file URL
     * is functioning as expected. It verifies that given specific input parameters,
     * the method constructs the proper URL string for rendering the view.
     *
     * @return void
     */
    public function testGetViewFileUrl()
    {
        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMock();
        $requestMock->method('isSecure')->willReturn(false);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->method('getRequest')->willReturn($requestMock);

        $assetRepoMock = $this->getMockBuilder(\Magento\Framework\View\Asset\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $assetRepoMock->method('getUrlWithParams')
            ->willReturn('https://example.com/static/frontend/image.png');
        $this->contextMock->method('getAssetRepository')->willReturn($assetRepoMock);

        $itemsBlock = $this->getMockBuilder(Items::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->priceHelperMock,
                $this->emulationMock,
                $this->toggleConfigMock,
                $this->marketPlacehelper,
                $this->marketplaceCheckoutHelper
            ])
            ->addMethods(['_getViewFileUrl'])
            ->getMock();

        $itemsBlock->method('_getViewFileUrl')
            ->willReturn('https://example.com/static/frontend/image.png');

        $fileId = 'image.png';
        $params = [];
        $expectedFileUrl = 'https://example.com/static/frontend/image.png';

        $this->emulationMock->expects($this->once())
            ->method('startEnvironmentEmulation')
            ->with(0, \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $this->emulationMock->expects($this->once())
            ->method('stopEnvironmentEmulation');

        $result = $itemsBlock->getViewFileUrl($fileId, $params);

        $this->assertEquals($expectedFileUrl, $result);
    }

    /**
     * Tests that the getShipmentItemsFormatted method correctly formats shipment items.
     *
     * This test verifies that the output of getShipmentItemsFormatted meets the expected format.
     * It checks for proper handling of shipment data, ensuring that items are correctly processed
     * and returned in a formatted string or array as required.
     *
     * @return void
     */
    public function testGetShipmentItemsFormatted()
    {
        $shipmentItems = [
            ['item' => 'Item 3'],
            ['mirakl_shop_name' => 'Shop A', 'item' => 'Item 1'],
            ['mirakl_shop_name' => 'Shop B', 'item' => 'Item 2'],
        ];

        $this->itemsBlockMock->method('getShipmentItems')->willReturn($shipmentItems);

        $expectedResult = [
            '1p' => [['item' => 'Item 3']],
            '3p' => [
                'Shop A' => [['mirakl_shop_name' => 'Shop A', 'item' => 'Item 1']],
                'Shop B' => [['mirakl_shop_name' => 'Shop B', 'item' => 'Item 2']],
            ],
        ];

        $result = $this->itemsBlockMock->getShipmentItemsFormatted();

        $this->assertEquals($expectedResult, $result);
    }
    /**
     * Test the formatting of the shipping method name.
     *
     * This test case verifies that the method responsible for formatting
     * shipping method names produces the expected formatted string. It ensures that
     * the shipping method name used in the email items is properly transformed to meet display standards.
     *
     * @return void
     */
    public function testFormatShippingMethodName()
    {
        $this->assertEquals(
            'FedEx Some Method',
            $this->itemsBlockMock->formatShippingMethodName('Some Method')
        );
        $this->assertEquals(
            'FedEx Some Method',
            $this->itemsBlockMock->formatShippingMethodName('FedEx Some Method')
        );
    }

    /**
     * Test isExpectedDeliveryDateEnabled() method
     *
     * @return void
     */
    public function testIsExpectedDeliveryDateEnabled()
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->itemsBlockMock->isExpectedDeliveryDateEnabled());
    }

    /**
     * Test isEssendantToggleEnabled() method
     *
     * @return void
     */
    public function testIsEssendantToggleEnabled()
    {
        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $this->assertTrue($this->itemsBlockMock->isEssendantToggleEnabled());

        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->itemsBlockMock = $this->getMockBuilder(Items::class)
            ->onlyMethods(['getViewFileUrl'])
            ->addMethods(['getShipmentItems'])
            ->setConstructorArgs(
                [
                    $this->contextMock,
                    $this->priceHelperMock,
                    $this->emulationMock,
                    $this->toggleConfigMock,
                    $this->marketPlacehelper,
                    $this->marketplaceCheckoutHelper
                ]
            )
            ->getMock();

        $this->assertFalse($this->itemsBlockMock->isEssendantToggleEnabled());
    }

    /**
     * Test isCBBToggleEnabled() method
     *
     * @return void
     */
    public function testIsCBBToggleEnabled()
    {
        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isCBBToggleEnabled')
            ->willReturn(true);

        $this->assertTrue($this->itemsBlockMock->isCBBToggleEnabled());
    }

    /**
     * Test isFreightShippingEnabled() method
     *
     * @return void
     */
    public function testIsFreightShippingEnabled()
    {
        $this->marketPlacehelper->expects($this->once())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->assertTrue($this->itemsBlockMock->isFreightShippingEnabled());
    }

    /**
     * Test getFreightShippingSurchargeText() method
     *
     * @return void
     */
    public function testGetFreightShippingSurchargeText()
    {
        $expectedText = 'Additional charges may apply';

        $this->marketPlacehelper->expects($this->once())
            ->method('getFreightShippingSurchargeText')
            ->willReturn($expectedText);

        $this->assertEquals($expectedText, $this->itemsBlockMock->getFreightShippingSurchargeText());
    }
}
