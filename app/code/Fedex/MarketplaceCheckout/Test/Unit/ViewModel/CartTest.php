<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\ViewModel;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplaceProduct\Model\Offer;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\MarketplaceProduct\Helper\Data as MiraklHelper;
use Magento\Tax\Helper\Data as MagentoTaxHelper;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\ViewModel\Cart;
use Mirakl\Connector\Model\Offer as OfferModel;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Model\Quote\Item;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;

class CartTest extends TestCase
{
    /**
     * @var NonCustomizableProduct|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $nonCustomizableProductModel;

    /**
     * @var (\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addToCartPerformanceOptimizationToggle;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var CheckoutSession
     */
    private $checkoutSessionMock;

    /**
     * @var MiraklHelper
     */
    private $miraklHelperMock;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var MagentoTaxHelper
     */
    private $magentoTaxHelper;

    /**
     * @var MarketPlaceHelper
     */
    private $marketPlaceHelperMock;

    protected function setUp(): void
    {
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->miraklHelperMock = $this->createMock(MiraklHelper::class);
        $this->magentoTaxHelper = $this->createMock(MagentoTaxHelper::class);
        $this->nonCustomizableProductModel = $this->createMock(NonCustomizableProduct::class);
        $this->addToCartPerformanceOptimizationToggle = $this->createMock(AddToCartPerformanceOptimizationToggle::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->marketPlaceHelperMock = $this->createMock(MarketPlaceHelper::class);
        $this->cart = new Cart(
            $this->checkoutSessionMock,
            $this->miraklHelperMock,
            $this->magentoTaxHelper,
            $this->nonCustomizableProductModel,
            $this->addToCartPerformanceOptimizationToggle,
            $this->toggleConfigMock,
            $this->marketPlaceHelperMock
        );
    }

    /**
     * Test getAllSellersInCart
     *
     * @return void
     */
    public function testGetAllSellersInCart(): void
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $offerShopMock = $this->getMockBuilder(OfferModel::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->miraklHelperMock->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $this->miraklHelperMock->expects($this->once())
            ->method('getBestOffer')
            ->with($productMock)
            ->willReturn($offerShopMock);

        $this->miraklHelperMock->expects($this->once())
            ->method('getOfferShop')
            ->withAnyParameters()
            ->willReturn($offerShopMock);

        $offerShopMock->expects($this->once())
            ->method('getData')
            ->willReturn(['name' => 'seller_name']);

        $result = $this->cart->getAllSellersInCart();

        $this->assertIsArray($result);
    }

    public function testGetAllSellersInCartWithOptimizationEnabled(): void
    {
        $this->addToCartPerformanceOptimizationToggle
            ->method('isActive')
            ->willReturn(true);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();

        $storage = [];
        $productMock->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$storage) {
                $storage[$key] = $value;
            });
        $productMock->method('getData')
            ->willReturnCallback(function ($key = null) use (&$storage) {
                return $storage[$key] ?? null;
            });

        $offerShopMock = new class {
            public function getData(): array
            {
                return [
                    'name' => 'seller_one',
                    'additional_info' => [
                        'additional_field_values' => [
                            ['code' => 'tooltip', 'value' => 'tip_one']
                        ]
                    ]
                ];
            }
        };

        $this->miraklHelperMock
            ->method('hasAvailableOffersForProduct')
            ->willReturn(true);

        $offerMock = $this->createMock(\Mirakl\Connector\Model\Offer::class);
        $this->miraklHelperMock
            ->method('getBestOffer')
            ->willReturn($offerMock);

        $this->miraklHelperMock
            ->method('getOfferShop')
            ->willReturn($offerShopMock);

        $itemMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $itemMock
            ->method('getProduct')
            ->willReturn($productMock);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock
            ->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $result = $this->cart->getAllSellersInCart();

        $expected = [
            ['name' => 'FedEx Office', 'tooltip' => ''],
            ['name' => 'seller_one',  'tooltip' => 'tip_one']
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Covers the `continue` path (code != "tooltip")
     * and then finds a valid "tooltip" entry on the next iteration.
     */
    public function testGetAllSellersInCartSkipsNonTooltipThenExtractsTooltip(): void
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();

        $storage = [];
        $productMock
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$storage) {
                $storage[$key] = $value;
            });
        $productMock
            ->method('getData')
            ->willReturnCallback(function ($key = null) use (&$storage) {
                return $storage[$key] ?? null;
            });

        $offerShopMock = $this->getMockBuilder(\Mirakl\Connector\Model\Offer::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $offerData = [
            'name' => 'seller_skip_then_tooltip',
            'additional_info' => [
                'additional_field_values' => [
                    ['code' => 'not_tooltip', 'value' => 'ignored'],
                    ['code' => 'tooltip',    'value' => 'final tip!']
                ]
            ]
        ];
        $offerShopMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn($offerData);

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock
            ->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('getBestOffer')
            ->with($productMock)
            ->willReturn($offerShopMock);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('getOfferShop')
            ->with($offerShopMock)
            ->willReturn($offerShopMock);

        $this->addToCartPerformanceOptimizationToggle
            ->method('isActive')
            ->willReturn(false);

        $actual = $this->cart->getAllSellersInCart();

        $expected = [
            [
                'name'    => 'seller_skip_then_tooltip',
                'tooltip' => 'final tip!'
            ]
        ];
        $this->assertSame($expected, $actual);
    }

    /**
     * Covers the `break` path when code == "tooltip" but "value" is missing.
     */
    public function testGetAllSellersInCartBreaksOnTooltipWithNoValue(): void
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();

        $storage = [];
        $productMock
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$storage) {
                $storage[$key] = $value;
            });
        $productMock
            ->method('getData')
            ->willReturnCallback(function ($key = null) use (&$storage) {
                return $storage[$key] ?? null;
            });

        $offerShopMock = $this->getMockBuilder(\Mirakl\Connector\Model\Offer::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $offerData = [
            'name' => 'seller_tooltip_novalue',
            'additional_info' => [
                'additional_field_values' => [
                    ['code' => 'tooltip']
                ]
            ]
        ];
        $offerShopMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn($offerData);

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock
            ->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('getBestOffer')
            ->with($productMock)
            ->willReturn($offerShopMock);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('getOfferShop')
            ->with($offerShopMock)
            ->willReturn($offerShopMock);

        $this->addToCartPerformanceOptimizationToggle
            ->method('isActive')
            ->willReturn(false);

        $actual = $this->cart->getAllSellersInCart();

        $expected = [
            [
                'name'    => 'seller_tooltip_novalue',
                'tooltip' => ''
            ]
        ];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test getAllSellersInCart extracts tooltip from additional_info
     */
    public function testGetAllSellersInCartExtractsTooltipFromAdditionalInfo(): void
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();

        $storage = [];
        $productMock
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$storage) {
                $storage[$key] = $value;
            });
        $productMock
            ->method('getData')
            ->willReturnCallback(function ($key = null) use (&$storage) {
                return $storage[$key] ?? null;
            });

        $offerShopMock = $this->getMockBuilder(\Mirakl\Connector\Model\Offer::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $offerData = [
            'name' => 'seller_with_tooltip',
            'additional_info' => [
                'additional_field_values' => [
                    ['code' => 'tooltip', 'value' => 'this is my tip!'],
                    ['code' => 'other',   'value' => 'ignored']
                ]
            ]
        ];
        $offerShopMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn($offerData);

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock
            ->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('getBestOffer')
            ->with($productMock)
            ->willReturn($offerShopMock);

        $this->miraklHelperMock
            ->expects($this->once())
            ->method('getOfferShop')
            ->with($offerShopMock)
            ->willReturn($offerShopMock);

        $this->addToCartPerformanceOptimizationToggle
            ->method('isActive')
            ->willReturn(false);

        $actual = $this->cart->getAllSellersInCart();

        $expected = [
            [
                'name'    => 'seller_with_tooltip',
                'tooltip' => 'this is my tip!'
            ]
        ];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test third party only cart
     *
     * @return void
     */
    public function testIsThirdPartyOnlyCartReturnsTrue(): void
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->miraklHelperMock->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $result = $this->cart->isThirdPartyOnlyCart();

        $this->assertTrue($result);
    }

    /**
     * Test cart with first party products
     *
     * @return void
     */
    public function testIsThirdPartyOnlyCartReturnsFalse(): void
    {
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->miraklHelperMock->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(false);

        $result = $this->cart->isThirdPartyOnlyCart();

        $this->assertFalse($result);
    }

    /**
     * Test hasMiraklOffers
     *
     * @return void
     */
    public function testHasMiraklOffersReturnsTrue(): void
    {
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->miraklHelperMock->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $result = $this->cart->hasMiraklOffers($productMock);

        $this->assertTrue($result);
    }

    /**
     * Test getBestOfferData
     *
     * @return void
     */
    public function testGetBestOfferData()
    {
        $product = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $miraklHelper = $this->getMockBuilder('Fedex\MarketplaceProduct\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $miraklHelper->expects($this->any())
            ->method('hasAvailableOffersForProduct')
            ->with($product)
            ->willReturn(true);

        $miraklHelper->expects($this->any())
            ->method('getBestOffer')
            ->with($product)
            ->willReturn(['offer']);

        $offerShop = $this->getMockBuilder('Fedex\MarketplaceProduct\Model\Offer\Shop')
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $offerShop->expects($this->any())
            ->method('getData')
            ->willReturn(['data']);

        $miraklHelper->expects($this->any())
            ->method('getOfferShop')
            ->with(['offer'])
            ->willReturn($offerShop);

        $this->cart->getBestOfferData($product);
    }

    /**
     * Test getMergedCells
     *
     * @return void
     */
    public function testGetMergedCells()
    {
        $this->assertEquals(1, $this->cart->getMergedCells());
    }

    /**
     * Test getMergedCells with only prices
     *
     * @return void
     */
    public function testGetMergedCellsBothPrices()
    {
        $this->magentoTaxHelper->expects($this->once())
            ->method('displayCartBothPrices')
            ->willReturn(true);

        $this->assertEquals(2, $this->cart->getMergedCells());
    }

    /**
     * Test getFedexSellerName
     *
     * @return void
     */
    public function testGetFedexSellerName()
    {
        $this->assertEquals('FedEx Office', $this->cart->getFedexSellerName());
    }

    /**
     * Test that verifies the cart correctly identifies a third-party-only state
     * when the punchout functionality is disabled.
     * @return void
     */
    public function testIsThirdPartyOnlyCartWithAnyPunchoutDisabled()
    {
        $this->nonCustomizableProductModel->expects($this->once())
            ->method('isThirdPartyOnlyCartWithAnyPunchoutDisabled')
            ->willReturn(true);

        $this->assertTrue($this->cart->isThirdPartyOnlyCartWithAnyPunchoutDisabled());
    }

    /**
     * Test that verifies the cart correctly identifies a third-party-only state
     * when the punchout functionality is enabled.
     * @return void
     */
    public function testIsMktCbbEnabled()
    {
        $this->nonCustomizableProductModel->expects($this->once())
            ->method('isMktCbbEnabled')
            ->willReturn(true);

        $this->assertTrue($this->cart->isMktCbbEnabled());
    }

    /**
     * Test that verifies the cart correctly identifies a third-party-only state
     * when the punchout functionality is enabled.
     * @return void
     */
    public function testIsImprovingUpdateItemQtyCart()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('mazegeeks_improving_update_item_qty_cart')
            ->willReturn(true);

        $this->assertTrue($this->cart->isImprovingUpdateItemQtyCart());
    }

    /**
     * Test checkLegacyDocumentInCart
     *
     * @return void
     */
    public function testCheckLegacyDocumentInCart()
    {
        $itemMock = $this->createMock(Item::class);
        $this->marketPlaceHelperMock->expects($this->any())->method('checkItemIsLegacyDocument')->willReturn(true);
        $this->cart->checkLegacyDocumentInCart($itemMock);
    }

    /**
     * Test checkLegacyDocApiOnCartToggle
     *
     * @return void
     */
    public function testCheckLegacyDocApiOnCartToggle()
    {
        $this->marketPlaceHelperMock->expects($this->any())->method('checkLegacyDocApiOnCartToggle')->willReturn(true);
        $this->cart->checkLegacyDocApiOnCartToggle();
    }
}
