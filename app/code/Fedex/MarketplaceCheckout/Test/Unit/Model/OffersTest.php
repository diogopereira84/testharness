<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model {

    use Fedex\MarketplaceCheckout\Model\Constants\ShippingConstants;
    use Fedex\MarketplaceCheckout\Model\Offers;
    use Fedex\MarketplaceCheckout\Model\Constants\ShippingAddressKeys;
    use Magento\Quote\Model\Quote\Item as QuoteItem;
    use Mirakl\Connector\Model\Offer;
    use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
    use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use ReflectionException;
    use ReflectionMethod;

    /**
     * Unit test for Fedex\MarketplaceCheckout\Model\Offers
     * @covers \Fedex\MarketplaceCheckout\Model\Offers
     */
    class OffersTest extends TestCase
    {
        /**
         * @var Offers
         */
        private Offers $offers;

        /**
         * @var OfferCollectionFactory|MockObject
         */
        private $offerCollectionFactoryMock;

        /**
         * @inheritdoc
         */
        protected function setUp(): void
        {
            $this->offerCollectionFactoryMock = $this->getMockBuilder(OfferCollectionFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock();

            $this->offers = new Offers(
                $this->offerCollectionFactoryMock
            );
        }

        /**
         * @return void
         */
        public function testGetOfferAddressesThrowsExceptionForMissingStates(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Missing ORIGIN_ADDRESS_STATES in offer additional info.');

            $offerMock = $this->getMockBuilder(Offer::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getAdditionalInfo'])
                ->getMock();
            $offerMock->method('getAdditionalInfo')->willReturn([]);

            $itemMock = $this->getMockBuilder(QuoteItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getQty'])
                ->getMock();

            $this->offers->getOfferAddresses([$offerMock], $itemMock, 'CA', []);
        }

        /**
         * @return void
         */
        public function testGetOfferAddressesThrowsExceptionForMissingReference(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Missing ORIGIN_ADDRESS_REFERENCE in offer');

            $offerMock = $this->getMockBuilder(Offer::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getId', 'getAdditionalInfo'])
                ->getMock();
            $offerMock->method('getAdditionalInfo')->willReturn(
                [ShippingAddressKeys::ORIGIN_ADDRESS_STATES => 'CA']
            );
            $offerMock->method('getId')->willReturn(1);

            $itemMock = $this->getMockBuilder(QuoteItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getQty'])
                ->getMock();
            $itemMock->method('getQty')->willReturn(1.0);

            $this->offers->getOfferAddresses([$offerMock], $itemMock, 'CA', []);
        }

        /**
         * @return void
         */
        public function testGetOfferItemsByOfferId(): void
        {
            $offerId = 123;
            $offerItemMock1 = $this->createMock(Offer::class);
            $offerItemMock2 = $this->createMock(Offer::class);
            $expectedItems = [$offerItemMock1, $offerItemMock2];

            $collectionMock = $this->getMockBuilder(OfferCollection::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['addFieldToFilter', 'getItems'])
                ->getMock();

            $this->offerCollectionFactoryMock->expects($this->once())
                ->method('create')
                ->willReturn($collectionMock);

            $collectionMock->expects($this->once())
                ->method('addFieldToFilter')
                ->with('offer_id', $offerId)
                ->willReturnSelf();

            $collectionMock->expects($this->once())
                ->method('getItems')
                ->willReturn($expectedItems);

            $result = $this->offers->getOfferItemsByOfferId($offerId);
            $this->assertSame($expectedItems, $result);
        }

        /**
         * @return void
         */
        public function testGetOfferItemsByOfferIdReturnsEmpty(): void
        {
            $offerId = 999;
            $collectionMock = $this->getMockBuilder(OfferCollection::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['addFieldToFilter', 'getItems'])
                ->getMock();
            $this->offerCollectionFactoryMock->expects($this->once())
                ->method('create')
                ->willReturn($collectionMock);
            $collectionMock->expects($this->once())
                ->method('addFieldToFilter')
                ->with('offer_id', $offerId)
                ->willReturnSelf();
            $collectionMock->expects($this->once())
                ->method('getItems')
                ->willReturn([]);
            $result = $this->offers->getOfferItemsByOfferId($offerId);
            $this->assertSame([], $result);
        }

        /**
         * @return void
         */
        public function testGetFilteredOffers(): void
        {
            $productSku = 'TEST-SKU';
            $shopId = 42;

            $offerMock1 = $this->getMockBuilder(Offer::class)->disableOriginalConstructor()->getMock();
            $offerMock2 = $this->getMockBuilder(Offer::class)->disableOriginalConstructor()->getMock();
            $expectedOffers = [$offerMock1, $offerMock2];

            $collectionMock = $this->getMockBuilder(OfferCollection::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['addFieldToFilter', 'getItems'])
                ->getMock();

            $this->offerCollectionFactoryMock->expects($this->once())
                ->method('create')
                ->willReturn($collectionMock);

            $collectionMock->expects($this->exactly(2))
                ->method('addFieldToFilter')
                ->withConsecutive(
                    ['product_sku', $productSku],
                    ['shop_id', $shopId]
                )
                ->willReturnSelf();

            $collectionMock->expects($this->once())
                ->method('getItems')
                ->willReturn($expectedOffers);

            $result = $this->offers->getFilteredOffers($productSku, $shopId);
            $this->assertSame($expectedOffers, $result);
        }

        /**
         * @return void
         */
        public function testGetFilteredOffersReturnsEmpty(): void
        {
            $productSku = 'NON-EXISTENT-SKU';
            $shopId = 99;

            $collectionMock = $this->getMockBuilder(OfferCollection::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['addFieldToFilter', 'getItems'])
                ->getMock();

            $this->offerCollectionFactoryMock->expects($this->once())
                ->method('create')
                ->willReturn($collectionMock);

            $collectionMock->expects($this->exactly(2))
                ->method('addFieldToFilter')
                ->withConsecutive(
                    ['product_sku', $productSku],
                    ['shop_id', $shopId]
                )
                ->willReturnSelf();

            $collectionMock->expects($this->once())
                ->method('getItems')
                ->willReturn([]);

            $result = $this->offers->getFilteredOffers($productSku, $shopId);
            $this->assertSame([], $result);
        }

        /**
         * @return void
         * @throws ReflectionException
         */
        public function testGetOriginReferenceSuccess(): void
        {
            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn([
                ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE => 'test_ref'
            ]);

            $reflection = new \ReflectionClass($this->offers);
            $method = $reflection->getMethod('getOriginReference');
            $result = $method->invoke($this->offers, $offerMock);
            $this->assertEquals('test_ref', $result);
        }

        /**
         * @return void
         * @throws ReflectionException
         */
        public function testGetOriginReferenceThrowsExceptionWhenReferenceIsMissing(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Missing ORIGIN_ADDRESS_REFERENCE in offer additional info');

            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn([]);

            $reflection = new \ReflectionClass($this->offers);
            $method = $reflection->getMethod('getOriginReference');
            $method->invoke($this->offers, $offerMock);
        }

        /**
         * @return void
         * @throws ReflectionException
         */
        public function testComputeMergedItemQuantityReturnsItemQuantityWhenNoExistingQuantity(): void
        {
            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn([
                ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE => 'ref_sum'
            ]);
            $offerMock->method('getId')->willReturn(150);

            $itemMock = $this->createMock(QuoteItem::class);
            $itemMock->method('getQty')->willReturn(2.0);

            $offerAddress = [];

            $method = new ReflectionMethod(Offers::class, 'computeMergedItemQuantity');
            $result = $method->invoke($this->offers, $offerMock, $itemMock, $offerAddress);

            $this->assertEquals(2.0, $result);
        }

        /**
         * @return void
         * @throws ReflectionException
         */
        public function testComputeMergedItemQuantityThrowsExceptionForMissingReference(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Missing ORIGIN_ADDRESS_REFERENCE in offer');

            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn([]);
            $offerMock->method('getId')->willReturn(1);

            $itemMock = $this->createMock(QuoteItem::class);
            $offerAddress = [];

            $method = new ReflectionMethod(Offers::class, 'computeMergedItemQuantity');
            $method->invoke($this->offers, $offerMock, $itemMock, $offerAddress);
        }

        /**
         * @return void
         * @throws ReflectionException
         */
        public function testGetOfferStateStringThrowsExceptionWhenStateIsMissing(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Missing ORIGIN_ADDRESS_STATES in offer additional info.');

            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn([]);

            $reflection = new \ReflectionClass($this->offers);
            $method = $reflection->getMethod('getOfferStateString');
            $method->invoke($this->offers, $offerMock);
        }

        /**
         * @return void
         * @throws ReflectionException
         */
        public function testIsRegionCodeValidWithAllStates(): void
        {
            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn([
                ShippingAddressKeys::ORIGIN_ADDRESS_STATES => ShippingConstants::ALL_STATES,
                ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE => 'ref'
            ]);

            $reflection = new \ReflectionClass($this->offers);
            $method = $reflection->getMethod('isRegionCodeValid');
            $result = $method->invoke($this->offers, $offerMock, 'ANY_STATE');
            $this->assertTrue($result);
        }

        /**
         * @return array
         */
        public function buildAddressDataProvider(): array
        {
            return [
                'offer_info_only' => [
                    'info' => [
                        ShippingAddressKeys::ORIGIN_CITY => 'Offer City',
                        ShippingAddressKeys::ORIGIN_STATE => 'OS',
                        ShippingAddressKeys::ORIGIN_ZIPCODE => '12345'
                    ],
                    'shopInfo' => [],
                    'expected' => [
                        ShippingAddressKeys::CITY => 'Offer City',
                        ShippingAddressKeys::STATE_OR_PROVINCE => 'OS',
                        ShippingAddressKeys::POSTAL_CODE => '12345'
                    ]
                ],
                'shop_info_only' => [
                    'info' => [],
                    'shopInfo' => [
                        ShippingAddressKeys::ORIGIN_SHOP_CITY => 'Shop City',
                        ShippingAddressKeys::ORIGIN_SHOP_STATE => 'SS',
                        ShippingAddressKeys::ORIGIN_SHOP_ZIPCODE => '54321'
                    ],
                    'expected' => [
                        ShippingAddressKeys::CITY => 'Shop City',
                        ShippingAddressKeys::STATE_OR_PROVINCE => 'SS',
                        ShippingAddressKeys::POSTAL_CODE => '54321'
                    ]
                ],
                'offer_overrides_shop' => [
                    'info' => [
                        ShippingAddressKeys::ORIGIN_CITY => 'Offer City',
                        ShippingAddressKeys::ORIGIN_STATE => 'OS',
                        ShippingAddressKeys::ORIGIN_ZIPCODE => '12345'
                    ],
                    'shopInfo' => [
                        ShippingAddressKeys::ORIGIN_SHOP_CITY => 'Shop City',
                        ShippingAddressKeys::ORIGIN_SHOP_STATE => 'SS',
                        ShippingAddressKeys::ORIGIN_SHOP_ZIPCODE => '54321'
                    ],
                    'expected' => [
                        ShippingAddressKeys::CITY => 'Offer City',
                        ShippingAddressKeys::STATE_OR_PROVINCE => 'OS',
                        ShippingAddressKeys::POSTAL_CODE => '12345'
                    ]
                ],
                'no_info' => [
                    'info' => [],
                    'shopInfo' => [],
                    'expected' => [
                        ShippingAddressKeys::CITY => '',
                        ShippingAddressKeys::STATE_OR_PROVINCE => '',
                        ShippingAddressKeys::POSTAL_CODE => ''
                    ]
                ]
            ];
        }

        /**
         * @param array $info
         * @param array $shopInfo
         * @param array $expected
         * @dataProvider buildAddressDataProvider
         * @throws ReflectionException
         */
        public function testBuildAddressData(array $info, array $shopInfo, array $expected): void
        {
            $offerMock = $this->createMock(Offer::class);
            $offerMock->method('getAdditionalInfo')->willReturn($info);
            $method = new ReflectionMethod(Offers::class, 'buildAddressData');
            $result = $method->invoke($this->offers, $offerMock, $shopInfo);
            $this->assertEquals($expected, $result);
        }
    }
}