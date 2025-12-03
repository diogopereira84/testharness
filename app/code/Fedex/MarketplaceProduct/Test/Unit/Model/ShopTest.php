<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Fedex\MarketplaceProduct\Model\Shop;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\Serialize\Serializer\Serialize;

class ShopTest extends \PHPUnit\Framework\TestCase
{
    private $shop;

    private $context;

    private $registry;

    private $urlInterface;

    private $abstractResource;

    private $abstractDbCollection;

    private $marketplaceConfigProvider;

    private $serializer;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractResource = $this->getMockBuilder(AbstractResource::class)
            ->setMethods(['getIdFieldName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->abstractDbCollection = $this->getMockBuilder(AbstractDbCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceConfigProvider = $this->getMockBuilder(MarketplaceConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(Serialize::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shop = new Shop(
            $this->context,
            $this->registry,
            $this->urlInterface,
            $this->serializer,
            $this->marketplaceConfigProvider,
            $this->abstractResource,
            $this->abstractDbCollection,
            []
        );
    }

    /**
     * @test
     */
    public function getIdShouldReturnShopId()
    {
        $expectedShopId = '123456789';
        $this->shop->setId($expectedShopId);
        $this->assertEquals($expectedShopId, $this->shop->getId());
    }

    /**
     * @test
     */
    public function getSellerAltNameShouldReturnSellerAltName()
    {
        $expectedAltName = 'My Seller Alt Name';
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'seller-alt-name',
                    'value' => 'My Seller Alt Name'
                ]
            ]
        ]);
        $this->assertEquals($expectedAltName, $this->shop->getSellerAltName());
    }

    /**
     * @test
     */
    public function getSellerAltNameReturnDefaultValue()
    {
        $this->assertEquals('Marketplace Seller', $this->shop->getSellerAltName());
    }

    /**
     * @test
     */
    public function getTooltipShouldReturnTooltip()
    {
        $expectedTooltip = 'My Tooltip';
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'tooltip',
                    'value' => 'My Tooltip'
                ]
            ]
        ]);
        $this->assertEquals($expectedTooltip, $this->shop->getTooltip());
    }

    /**
     * @test
     */
    public function getTooltipReturnDefaultValue()
    {
        $this->assertEquals('Marketplace Seller', $this->shop->getTooltip());
    }

    /**
     * @test
     */
    public function getCartQuantityTooltipReturnCartQuantityTooltip()
    {
        $expectedTooltip = 'My Cart Qty Tooltip';
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'cart-quantity-tooltip',
                    'value' => 'My Cart Qty Tooltip'
                ]
            ]
        ]);
        $this->assertEquals($expectedTooltip, $this->shop->getCartQuantityTooltip());
    }

    /**
     * @test
     */
    public function getCartQuantityTooltipReturnDefaultValue()
    {
        $this->marketplaceConfigProvider->expects($this->once())
            ->method('getCartQuantityTooltip')->willReturn('My Cart Qty Default Tooltip');
        $this->assertEquals('My Cart Qty Default Tooltip', $this->shop->getCartQuantityTooltip());
    }

    /**
     * @test
     */
    public function getCartExpire()
    {
        $expectedResult = 3;
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'expiry-configuration',
                    'value' => '3'
                ]
            ]
        ]);
        $this->assertEquals($expectedResult, $this->shop->getCartExpire());
    }

    /**
     * @test
     */
    public function getCartExpireSoon()
    {
        $expectedResult = 2;
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'expires-soon-configuration',
                    'value' => '2'
                ]
            ]
        ]);
        $this->assertEquals($expectedResult, $this->shop->getCartExpireSoon());
    }

    /**
     * @test
     */
    public function getShippingRateOptionShouldReturnFromFedex()
    {
        $expectedRates = [
            'shipping_rate_option' => 'fedex-shipping-rates',
            'shipping_account_number' => '123456789',
            'shipping_cut_off_time' => '12 PM',
            'shipping_seller_holidays' => '',
            'origin_shop_city' => '',
            'origin_shop_state' => '',
            'origin_shop_zipcode' => '',
            'origin_combined_offers' => 'true',
            'additional_processing_days' => 0,
            'customer_shipping_account_enabled' => false,
            'freight_enabled' => false,
            'freight_account_number' => '',
            'freight_city' => '',
            'freight_state' => '',
            'freight_postcode' => '',
            'free-shipping-no-customizable' => false
        ];
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'shipping-rate-options',
                    'value' => 'fedex-shipping-rates'
                ],
                [
                    'code' => 'shipment-account',
                    'value' => '123456789'
                ],
                [
                    'code' => 'origin-city',
                    'value' => ''
                ],
                [
                    'code' => 'origin-state',
                    'value' => ''
                ],
                [
                    'code' => 'origin-zipcode',
                    'value' => ''
                ],
                [
                    'code' => 'origin-combined-offers',
                    'value' => 'true'
                ]
            ]
        ]);
        $this->assertEquals($expectedRates, $this->shop->getShippingRateOption());
    }

    /**
     * @test
     */
    public function getShippingRateOptionReturnDefaultValue()
    {
        $expectedRates = [
            'shipping_rate_option' => 'fedex-shipping-rates',
            'shipping_account_number' => '',
            'shipping_cut_off_time' => '12 PM',
            'shipping_seller_holidays' => '',
            'origin_shop_city' => '',
            'origin_shop_state' => '',
            'origin_shop_zipcode' => '',
            'origin_combined_offers' => null,
            'additional_processing_days' => 0,
            'customer_shipping_account_enabled' => false,
            'freight_enabled' => false,
            'freight_account_number' => '',
            'freight_city' => '',
            'freight_state' => '',
            'freight_postcode' => '',
            'free-shipping-no-customizable' => false
        ];
        $this->assertEquals($expectedRates, $this->shop->getShippingRateOption());
    }

    /**
     * @test
     */
    public function testGetShippingInfoFormat()
    {
        $expectedResult = 'XML';
        $this->shop->setAdditionalInfo([
            'additional_field_values' => [
                [
                    'code' => 'shipping-info-format',
                    'value' => 'XML'
                ]
            ]
        ]);
        $this->assertEquals($expectedResult, $this->shop->getShippingInfoFormat());
    }

}
