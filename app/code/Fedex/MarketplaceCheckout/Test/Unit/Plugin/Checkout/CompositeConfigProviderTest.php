<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Plugin\Checkout;

use Fedex\MarketplaceCheckout\Plugin\Checkout\CompositeConfigProvider;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Magento\Checkout\Model\ConfigProviderInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\ResourceModel\QuoteItemRetriever;
use Magento\Quote\Model\Quote\Item;
use Fedex\MarketplaceProduct\Model\Shop;
use Fedex\MarketplaceCheckout\Helper\Data as ToggleHelper;

class CompositeConfigProviderTest extends TestCase
{
    /** @var ToggleHelper  */
    protected $toggleHelper;

    /** @var ConfigProviderInterface  */
    private ConfigProviderInterface $configProvider;

    /** @var QuoteItemRetriever  */
    private QuoteItemRetriever $quoteItemRetriever;

    /** @var Item  */
    private Item $item;

    /** @var ShopRepositoryInterface  */
    private ShopRepositoryInterface $shopRepository;

    /** @var CompositeConfigProvider  */
    private CompositeConfigProvider $compositeConfigProvider;

    /** @var Shop $shop */
    private Shop $shop;

    /**
     * Sets up the test environment before each test method is run.
     * @return void
     */
    public function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProviderInterface::class);
        $this->quoteItemRetriever = $this->createMock(QuoteItemRetriever::class);
        $this->shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $this->shop = $this->createMock(Shop::class);
        $this->toggleHelper = $this->createMock(ToggleHelper::class);
        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();
        $this->quoteItemRetriever->method('getById')
            ->willReturn($this->item);
        $this->item->method('getAdditionalData')
            ->willReturn($this->returnAdditionalData());
        $this->shop->method('getSellerAltName')
            ->willReturn('Marketplace Seller');
        $this->shop->method('getTooltip')
            ->willReturn('Marketplace Seller');

        $this->compositeConfigProvider = new CompositeConfigProvider(
            $this->quoteItemRetriever,
            $this->shopRepository,
            $this->toggleHelper
        );
    }

    /**
     * Tests the afterGetConfig plugin method.
     * @return void
     */
    public function testAfterGetConfig()
    {
        $this->shopRepository->expects($this->once())->method('getById')->willReturn($this->shop);
        $this->assertEquals(
            $this->returnArrayWithAdditionalData(),
            $this->compositeConfigProvider->afterGetConfig(
                $this->configProvider,
                $this->returnArrayWithoutAdditionalData()
            )
        );
    }

    /**
     * Test that the afterGetConfig method correctly handles the scenario
     * when the MultiSeller Reorder feature is disabled.
     * @return void
     */
    public function testAfterGetConfigMultiSellerReorderDisabled()
    {
        $this->shopRepository->expects($this->once())->method('getById')->willReturn($this->shop);
        $this->assertEquals(
            $this->returnArrayWithAdditionalData(),
            $this->compositeConfigProvider->afterGetConfig(
                $this->configProvider,
                $this->returnArrayWithoutAdditionalData()
            )
        );
    }

    /**
     * Tests the afterGetConfig plugin method when the shop repository throws a NoSuchEntityException.
     * @return void
     */
    public function testAfterGetConfigNoSuchEntityException()
    {
        $this->shopRepository->expects($this->once())->method('getById')->willThrowException(
            new NoSuchEntityException
        );

        $this->assertEquals(
            $this->returnArrayWithAdditionalData(),
            $this->compositeConfigProvider->afterGetConfig(
                $this->configProvider,
                $this->returnArrayWithoutAdditionalData()
            )
        );
    }

    /**
     * Tests the afterGetConfig plugin method when the shop ID is not set.
     * @return void
     */
    public function testAfterGetConfigNoShopId()
    {
        $input = $this->returnArrayWithoutAdditionalData();
        $input['quoteItemData'][0]['mirakl_shop_id'] = 0;
        $this->shopRepository->expects($this->never())->method('getById');

        $result = $this->compositeConfigProvider->afterGetConfig(
            $this->configProvider,
            $input
        );

        $defaultName = Shop::DEFAULT_SELLER_ALT_NAME;
        $expected = [
            'quoteItemData' => [
                0 => [
                    'item_id' => '62484',
                    'seller_name' => $defaultName,
                    'tooltip' => $defaultName,
                    'marketplace_total' => 7.76,
                    'marketplace_unit_price' => 0.031,
                    'marketplace_image' => 'https://imageurl.test/temp/catalog/image.png',
                    'marketplace_quantity' => 250,
                    'marketplace_name' => 'Screenshot-from-2023-04-04-11-19-36.png',
                    'offer_id' => '2534',
                    'isMarketplaceProduct' => 'true',
                    'seller_sku' => 'RFST-SP6-FF-NAVI',
                    'supplierPartAuxiliaryID' => '73ecb5d1-63c5-4304-ae58-762aa50e94b4',
                    'can_edit_reorder' => false,
                    'seller_ship_account_enabled' => false,
                    'mirakl_shop_id' => 0,
                    'offer' => [
                        'shop_id' => 1015
                    ],
                    'surcharge' => 0.00
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the afterGetConfig plugin method when the shop has no additional info.
     * @return void
     */
    public function testAfterGetConfigWithSurcharge()
    {
        $itemWithSurcharge = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();

        $additionalDataWithSurcharge = json_encode([
            'total'                    => 7.76,
            'unit_price'               => 0.031,
            'image'                    => 'https://imageurl.test/temp/catalog/image.png',
            'quantity'                 => 250,
            'marketplace_name'         => 'Screenshot-from-2023-04-04-11-19-36.png',
            'offer_id'                 => '2534',
            'isMarketplaceProduct'     => 'true',
            'seller_sku'               => 'RFST-SP6-FF-NAVI',
            'supplierPartAuxiliaryID'  => '73ecb5d1-63c5-4304-ae58-762aa50e94b4',
            'can_edit_reorder'         => false,
            'mirakl_shop_id'           => 1015,
            'offer'                    => ['shop_id' => 1015],
            'mirakl_shipping_data'     => [
                'surcharge_amount' => '5.50'
            ]
        ]);

        $itemWithSurcharge->method('getAdditionalData')
            ->willReturn($additionalDataWithSurcharge);

        $quoteItemRetrieverSurcharge = $this->createMock(QuoteItemRetriever::class);
        $quoteItemRetrieverSurcharge->method('getById')
            ->with('62484')
            ->willReturn($itemWithSurcharge);

        $this->shop->method('getAdditionalInfo')
            ->willReturn(['additional_field_values' => []]);

        $this->shop->method('getShippingRateOption')
            ->willReturn(['customer_shipping_account_enabled' => false]);

        $this->shopRepository->expects($this->once())
            ->method('getById')
            ->with(1015)
            ->willReturn($this->shop);

        $compositeConfigProviderWithSurcharge = new CompositeConfigProvider(
            $quoteItemRetrieverSurcharge,
            $this->shopRepository,
            $this->toggleHelper
        );

        $result = $compositeConfigProviderWithSurcharge->afterGetConfig(
            $this->configProvider,
            $this->returnArrayWithoutAdditionalData()
        );

        $this->assertEquals(
            '5.50',
            $result['quoteItemData'][0]['surcharge'],
            'Expected surcharge to be formatted as "5.50" when mirakl_shipping_data->surcharge_amount is present.'
        );
    }

    /**
     * Tests the afterGetConfig plugin method when the shop additional info has allow-edit-reorder set to false.
     * @return void
     */
    public function testAfterGetConfigAllowEditReorderTrue()
    {
        $additionalInfo = [
            'additional_field_values' => [
                ['code' => 'allow-edit-reorder', 'value' => 'true']
            ]
        ];
        $this->shop->method('getAdditionalInfo')->willReturn($additionalInfo);
        $this->shopRepository->expects($this->once())->method('getById')->willReturn($this->shop);

        $result = $this->compositeConfigProvider->afterGetConfig(
            $this->configProvider,
            $this->returnArrayWithoutAdditionalData()
        );

        $this->assertTrue(
            $result['quoteItemData'][0]['can_edit_reorder'],
            'Expected can_edit_reorder to be true when shop additional info has allow-edit-reorder => true.'
        );
    }

    /**
     * Tests the afterGetConfig plugin method when the shop additional info has allow-edit-reorder set to false.
     * @return void
     */
    private function returnArrayWithoutAdditionalData()
    {
        return [
            'quoteItemData' => [
                0 => [
                    'item_id' => '62484',
                    'mirakl_shop_id' => 1015,
                    'offer' => [
                        'shop_id' => 1015
                    ],
                    'surcharge' => 0.00
                ],
            ],
        ];
    }

    /**
     * Returns an array with additional data for the quote item.
     * @return array
     */
    private function returnArrayWithAdditionalData()
    {
        return [
            'quoteItemData' => [
                0 => [
                    'item_id' => '62484',
                    'seller_name' => 'Marketplace Seller',
                    'tooltip' => 'Marketplace Seller',
                    'marketplace_total' => 7.76,
                    'marketplace_unit_price' => 0.031,
                    'marketplace_image' => 'https://imageurl.test/temp/catalog/image.png',
                    'marketplace_quantity' => 250,
                    'marketplace_name' => 'Screenshot-from-2023-04-04-11-19-36.png',
                    'offer_id' => '2534',
                    'isMarketplaceProduct' => 'true',
                    'seller_sku' => 'RFST-SP6-FF-NAVI',
                    'supplierPartAuxiliaryID' => '73ecb5d1-63c5-4304-ae58-762aa50e94b4',
                    'seller_ship_account_enabled' => false,
                    'can_edit_reorder' => false,
                    'mirakl_shop_id' => 1015,
                    'offer' => [
                        'shop_id' => 1015
                    ],
                    'surcharge' => 0.00
                ],
            ],
        ];
    }

    /**
     * Returns additional data required for the composite configuration provider tests.
     * @return array
     */
    private function returnAdditionalData()
    {
        return json_encode([
            'total' => 7.76,
            'unit_price' => 0.031,
            'image' => 'https://imageurl.test/temp/catalog/image.png',
            'quantity' => 250,
            'marketplace_name' => 'Screenshot-from-2023-04-04-11-19-36.png',
            'offer_id' => '2534',
            'isMarketplaceProduct' => 'true',
            'seller_sku' => 'RFST-SP6-FF-NAVI',
            'supplierPartAuxiliaryID' => '73ecb5d1-63c5-4304-ae58-762aa50e94b4',
            'can_edit_reorder' => false,
            'mirakl_shop_id' => 1015,
            'offer' => [
                'shop_id' => 1015
            ]
        ]);
    }
}
