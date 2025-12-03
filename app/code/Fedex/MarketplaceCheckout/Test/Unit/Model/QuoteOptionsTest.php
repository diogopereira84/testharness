<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

class QuoteOptionsTest extends TestCase
{
    /**
     * @var CartItemRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private CartItemRepositoryInterface $cartItemRepository;

    /**
     * @var QuoteOptions|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteOptions;

    /**
     * @var MarketplaceHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var MarketplaceHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var PackagingCheckoutPricing|\PHPUnit\Framework\MockObject\MockObject
     */
    private PackagingCheckoutPricing $packagingCheckoutPricing;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
        $this->marketplaceHelper = $this->createMock(MarketplaceHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->packagingCheckoutPricing = $this->createMock(PackagingCheckoutPricing::class);
        $this->quoteOptions = new QuoteOptions(
            $this->cartItemRepository,
            $this->marketplaceHelper,
            $this->logger,
            $this->packagingCheckoutPricing
        );
    }

    /**
     * Test setMktShipMethodDataItemOptions enable mkt checkout true.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testSetMktShipMethodDataItemOptionsMktCheckoutIsActive()
    {
        $additionalData = '
                {
                    "supplierPartAuxiliaryID":"ad1616fc-99ca-4666-a704-e08b4d904e3b",
                    "seller_sku":"RBC-S1-FF-FED3","offer_id":
                    "2553","isMarketplaceProduct":"true", 
                    "mirakl_shipping_data": "test"
                }
            ';

        $quote = $this->createMock(Quote::class);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getAdditionalData', 'setAdditionalData', 'getMiraklOfferId'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItem->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn(1);

        $shipMethodData = '{"item_id": 1, "carrier_code":"marketplace_2553", "method_code":"F2D"}';

        $quote->expects($this->any())
            ->method('getItemById')
            ->with(1)
            ->willReturn($quoteItem);

        $quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$quoteItem]);

        $quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData);

        $this->cartItemRepository->expects($this->exactly(2))
            ->method('save')
            ->with($quoteItem);

        $this->assertNull($this->quoteOptions->setMktShipMethodDataItemOptions($shipMethodData, $quote));
    }

    /**
     * @return void
     */
    public function testSetMktShipMethodDataItemOptionsWithEmptyData()
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->never())
            ->method('getItemById');
        $quote->expects($this->never())
            ->method('getAllItems');
        $this->cartItemRepository->expects($this->never())
            ->method('save');

        $shipMethodData = '';
        $result = $this->quoteOptions->setMktShipMethodDataItemOptions($shipMethodData, $quote);
        $this->assertSame($this->quoteOptions, $result);
    }

    /**
     * Test setMktShipMethodDataItemOptions with no item_id in shipMethodData.
     *
     * @return void
     */
    public function testNoMarketplaceItemsDoesNothing(): void
    {
        $this->quoteOptions = $this->getMockBuilder(QuoteOptions::class)
            ->setConstructorArgs([
                $this->cartItemRepository,
                $this->marketplaceHelper,
                $this->logger,
                $this->packagingCheckoutPricing
            ])
            ->onlyMethods(['saveQuoteItemMiraklShippingPrices', 'saveQuoteMiraklShippingPrices'])
            ->getMock();

        $quote = $this->createMock(Quote::class);
        $plainItem = $this->createMock(QuoteItem::class);
        $plainItem->method('getData')->with('mirakl_offer_id')->willReturn(null);

        $quote->method('getAllItems')->willReturn([$plainItem, $plainItem]);

        $this->quoteOptions
            ->expects($this->never())
            ->method('saveQuoteItemMiraklShippingPrices');
        $this->quoteOptions
            ->expects($this->never())
            ->method('saveQuoteMiraklShippingPrices');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Before Update Mirakl Shipping prices - 0'));

        $result = $this->quoteOptions->setMktShippingAndTaxInfo($quote);
        $this->assertNull($result, 'Expected setMktShippingAndTaxInfo to return null when info is never logged.');
    }

    /**
     * Test setMktShippingAndTaxInfo with a single marketplace item.
     *
     * @return void
     */
    public function testSingleMarketplaceItem(): void
    {
        $this->quoteOptions = $this->getMockBuilder(QuoteOptions::class)
            ->setConstructorArgs([
                $this->cartItemRepository,
                $this->marketplaceHelper,
                $this->logger,
                $this->packagingCheckoutPricing
            ])
            ->onlyMethods(['saveQuoteItemMiraklShippingPrices', 'saveQuoteMiraklShippingPrices'])
            ->getMock();

        $quote = $this->createMock(Quote::class);

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getId'])
            ->addMethods([
                'getMiraklShopName',
                'getMiraklLeadtimeToShip',
                'getMiraklShippingType',
                'getMiraklShippingTypeLabel',
                'getAdditionalData'
            ])
            ->getMock();

        $item->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('OFFER');
        $item->method('getMiraklShopName')->willReturn('Shop A');
        $item->method('getMiraklLeadtimeToShip')->willReturn('1 day');
        $item->method('getMiraklShippingType')->willReturn('Std');
        $item->method('getMiraklShippingTypeLabel')->willReturn('Standard');
        $item->method('getId')->willReturn(123);

        $item->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => ['amount' => 7.77]]));

        $quote->method('getAllItems')->willReturn([$item]);

        $this->quoteOptions
            ->expects($this->once())
            ->method('saveQuoteItemMiraklShippingPrices')
            ->with(
                $item,
                $this->equalTo(7.77),
                'Shop A',
                '1 day',
                'Std',
                'Standard'
            );

        $this->quoteOptions
            ->expects($this->never())
            ->method('saveQuoteMiraklShippingPrices');

        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('Before Update Mirakl Shipping prices - 7.77')],
                [$this->stringContains('After Update Mirakl Shipping prices - 7.77 for Quote Item ID 123')]
            );

        $result = $this->quoteOptions->setMktShippingAndTaxInfo($quote);
        $this->assertNull($result, 'Expected setMktShippingAndTaxInfo to return null.');
    }

    /**
     * Test setMktShippingAndTaxInfo with two marketplace items that split evenly.
     *
     * @return void
     */
    public function testTwoMarketplaceItemsSplitsEvenlyAndSavesQuoteTotal(): void
    {
        $this->quoteOptions = $this->getMockBuilder(QuoteOptions::class)
            ->setConstructorArgs([
                $this->cartItemRepository,
                $this->marketplaceHelper,
                $this->logger,
                $this->packagingCheckoutPricing
            ])
            ->onlyMethods(['saveQuoteItemMiraklShippingPrices', 'saveQuoteMiraklShippingPrices'])
            ->getMock();

        $quote = $this->createMock(Quote::class);

        $item1 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods([
                'getMiraklShopId',
                'getAdditionalData',
                'getMiraklShopName',
                'getMiraklLeadtimeToShip',
                'getMiraklShippingType',
                'getMiraklShippingTypeLabel'
            ])
            ->getMock();

        $item2 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods([
                'getMiraklShopId',
                'getAdditionalData',
                'getMiraklShopName',
                'getMiraklLeadtimeToShip',
                'getMiraklShippingType',
                'getMiraklShippingTypeLabel'
            ])
            ->getMock();

        foreach ([$item1, $item2] as $it) {
            $it->method('getData')
                ->with('mirakl_offer_id')
                ->willReturn('OFFER');
            $it->method('getMiraklShopId')->willReturn(77);
            $it->method('getMiraklShopName')->willReturn('ShopX');
            $it->method('getMiraklLeadtimeToShip')->willReturn('2 days');
            $it->method('getMiraklShippingType')->willReturn('Xpress');
            $it->method('getMiraklShippingTypeLabel')->willReturn('Xpress Label');
        }

        $item1->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => ['amount' => 10.00]]));
        $item2->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => ['amount' => 10.00]]));

        $quote->method('getAllItems')->willReturn([$item1, $item2]);

        $this->quoteOptions
            ->expects($this->exactly(2))
            ->method('saveQuoteItemMiraklShippingPrices')
            ->withConsecutive(
                [$item1, 5.00, 'ShopX', '2 days', 'Xpress', 'Xpress Label'],
                [$item2, 5.00, 'ShopX', '2 days', 'Xpress', 'Xpress Label']
            );

        $this->quoteOptions
            ->expects($this->once())
            ->method('saveQuoteMiraklShippingPrices')
            ->with($quote, 10.00);

        $this->logger->expects($this->never())->method('info');

        $result = $this->quoteOptions->setMktShippingAndTaxInfo($quote);
        $this->assertNull($result, 'Expected setMktShippingAndTaxInfo to return null when info is never logged.');
    }

    /**
     * Test setMktShippingAndTaxInfo with three marketplace items that distribute remainder correctly.
     *
     * @return void
     */
    public function testShippingCostWithRemainderDistributesCorrectly(): void
    {
        $this->quoteOptions = $this->getMockBuilder(QuoteOptions::class)
            ->setConstructorArgs([
                $this->cartItemRepository,
                $this->marketplaceHelper,
                $this->logger,
                $this->packagingCheckoutPricing
            ])
            ->onlyMethods(['saveQuoteItemMiraklShippingPrices', 'saveQuoteMiraklShippingPrices'])
            ->getMock();

        $quote = $this->createMock(Quote::class);

        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[$i] = $this->getMockBuilder(QuoteItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getData'])
                ->addMethods([
                    'getMiraklShopId',
                    'getMiraklShopName',
                    'getMiraklLeadtimeToShip',
                    'getMiraklShippingType',
                    'getMiraklShippingTypeLabel',
                    'getAdditionalData'
                ])
                ->getMock();

            $items[$i]->method('getData')
                ->with('mirakl_offer_id')
                ->willReturn('OFFER');
            $items[$i]->method('getMiraklShopId')->willReturn(100);
            $items[$i]->method('getMiraklShopName')->willReturn('TestShop');
            $items[$i]->method('getMiraklLeadtimeToShip')->willReturn('3 days');
            $items[$i]->method('getMiraklShippingType')->willReturn('Standard');
            $items[$i]->method('getMiraklShippingTypeLabel')->willReturn('Standard Shipping');
        }

        $shippingAmount = 10.01;
        $items[0]->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => ['amount' => $shippingAmount]]));

        for ($i = 1; $i < 3; $i++) {
            $items[$i]->method('getAdditionalData')
                ->willReturn(json_encode(['mirakl_shipping_data' => []]));
        }

        $quote->method('getAllItems')->willReturn($items);

        $this->quoteOptions
            ->expects($this->exactly(3))
            ->method('saveQuoteItemMiraklShippingPrices')
            ->withConsecutive(
                [$items[0], 3.34, 'TestShop', '3 days', 'Standard', 'Standard Shipping'],
                [$items[1], 3.34, 'TestShop', '3 days', 'Standard', 'Standard Shipping'],
                [$items[2], 3.33, 'TestShop', '3 days', 'Standard', 'Standard Shipping']
            );

        $this->quoteOptions
            ->expects($this->once())
            ->method('saveQuoteMiraklShippingPrices')
            ->with($quote, $shippingAmount);

        $result = $this->quoteOptions->setMktShippingAndTaxInfo($quote);
        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function testSetMktShipMethodDataItemOptionsWithAddressCheckEnabled()
    {
        $quote = $this->createMock(Quote::class);
        $quoteItem = $this->getMockBuilder(Item::class)
            ->addMethods(['getAdditionalData', 'setAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();

        $shipMethodData = '{"item_id": 1, "carrier_code":"marketplace_2553", "method_code":"F2D"}';

        $additionalData = json_encode([
            'mirakl_shipping_data' => [
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'postcode' => '12345'
                ]
            ]
        ]);

        $quote->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$quoteItem]);

        $quote->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$quoteItem]);

        $quote->expects($this->once())
            ->method('getItemById')
            ->with(1)
            ->willReturn($quoteItem);

        $quoteItem->expects($this->exactly(2))
            ->method('getAdditionalData')
            ->willReturnOnConsecutiveCalls(
                $additionalData,
                '{"mirakl_shipping_data":{}}'
            );

        $quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($data) {
                $decodedData = json_decode($data, true);
                return isset($decodedData['mirakl_shipping_data']['address']['street']) &&
                    $decodedData['mirakl_shipping_data']['address']['street'] === '123 Main St';
            }));

        $result = $this->quoteOptions->setMktShipMethodDataItemOptions($shipMethodData, $quote);
        $this->assertNull($result, 'Expected setMktShipMethodDataItemOptions to return null.');
    }

    /**
     * @return void
     */
    public function testSetMktShipMethodDataItemOptionsWithAddressCheckDisabled()
    {
        $quote = $this->createMock(Quote::class);
        $quoteItem = $this->getMockBuilder(Item::class)
            ->addMethods(['getAdditionalData', 'setAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();

        $shipMethodData = '{"item_id": 1, "carrier_code":"marketplace_2553", "method_code":"F2D"}';

        $additionalData = json_encode([
            'mirakl_shipping_data' => [
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'postcode' => '12345'
                ]
            ]
        ]);

        $quote->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$quoteItem]);

        $quote->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$quoteItem]);

        $quote->expects($this->once())
            ->method('getItemById')
            ->with(1)
            ->willReturn($quoteItem);

        $quoteItem->expects($this->exactly(2))
            ->method('getAdditionalData')
            ->willReturnOnConsecutiveCalls(
                $additionalData,
                '{"mirakl_shipping_data":{}}'
            );

        $quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($data) {
                $decodedData = json_decode($data, true);
                return isset($decodedData['mirakl_shipping_data']['address']['street']) &&
                    $decodedData['mirakl_shipping_data']['address']['street'] === '123 Main St';
            }));

        $result = $this->quoteOptions->setMktShipMethodDataItemOptions($shipMethodData, $quote);
        $this->assertNull($result, 'Expected setMktShipMethodDataItemOptions to return null.');
    }

    /**
     * @dataProvider exceptionProvider
     * @param string $exceptionClass
     * @return void
     */
    public function testMktUpdateLogsException($exceptionClass)
    {
        $shipData = json_encode([
            'item_id'   => 123,
            'seller_id' => 42,
        ]);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('simple');

        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->addMethods((['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData']))
            ->onlyMethods(['getData', 'getProduct'])
            ->getMock();

        $item->method('getData')
            ->with('mirakl_shop_id')
            ->willReturn(42);
        $item->method('getMiraklOfferId')
            ->willReturn('offer-xyz');
        $item->method('getProduct')
            ->willReturn($product);
        $item->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => []]));

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->marketplaceHelper
            ->expects($this->exactly(2))
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);
        $quote->method('getAllVisibleItems')->willReturn([]);
        $quote->method('getAllItems')->willReturn([$item]);
        $quote->method('getItemById')->with(123)->willReturn($item);

        $this->cartItemRepository
            ->expects($this->once())
            ->method('save')
            ->with($item)
            ->willThrowException(
                new $exceptionClass(new \Magento\Framework\Phrase('uh-oh'))
            );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('while updating quote options facing exception'));

        $this->assertNull(
            $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated($shipData, $quote)
        );
    }

    /**
     * Data provider for exception tests.
     *
     * @return array
     */
    public function exceptionProvider(): array
    {
        return [
            [CouldNotSaveException::class],
            [InputException::class],
            [NoSuchEntityException::class],
        ];
    }

    /**
     * @return void
     */
    public function testSaveQuoteItemMiraklShippingPrices()
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->addMethods(['setMiraklBaseShippingFee', 'setMiraklShippingFee'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItem->expects($this->once())
            ->method('setMiraklBaseShippingFee')
            ->with($this->equalTo(10.00));

        $quoteItem->expects($this->once())
            ->method('setMiraklShippingFee')
            ->with($this->equalTo(10.00));

        $this->cartItemRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($quoteItem));

        $result = $this->quoteOptions->saveQuoteItemMiraklShippingPrices(
            $quoteItem,
            10.00,
            'Shop Name',
            'Lead Time',
            'Shipping Type',
            'Shipping Type Label'
        );

        $this->assertNull($result, 'saveQuoteItemMiraklShippingPrices should return null');
    }

    /**
     * @return void
     */
    public function testSaveQuoteItemMiraklShippingPricesWithFreightItem(): void
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProduct'])
            ->addMethods([
                'setMiraklBaseShippingFee',
                'getMiraklShopId',
                'setAdditionalData',
                'getAdditionalData',
                'setMiraklShippingFee',
                'setMiraklBaseShippingExclTax',
                'setMiraklShippingExclTax',
                'setMiraklBaseShippingInclTax',
                'setMiraklShippingInclTax',
                'setMiraklShippingTaxPercent',
                'setMiraklBaseShippingTaxAmount',
                'setMiraklShippingTaxAmount',
                'setMiraklShippingTaxApplied',
                'setMiraklShopName',
                'setMiraklLeadtimeToShip',
                'setMiraklShippingType',
                'setMiraklShippingTypeLabel'
            ])
            ->getMock();

        $shippingAmount = 25.00;
        $shopName = 'Freight Shop';
        $leadTime = '5 days';
        $shippingType = 'Freight';
        $shippingTypeLabel = 'LTL Freight';
        $shopId = 123;

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $quoteItem->method('getProduct')->willReturn($product);
        $quoteItem->method('getMiraklShopId')->willReturn($shopId);

        $additionalData = [
            'punchout_enabled' => true,
            'packaging_data' => ['some' => 'data'],
            'mirakl_shipping_data' => ['amount' => $shippingAmount]
        ];
        $quoteItem->method('getAdditionalData')
            ->willReturn(json_encode($additionalData));

        $freightInfo = [
            ['seller_id' => 456, 'packaging' => ['item1']],
            ['seller_id' => $shopId, 'packaging' => ['weight' => 500, 'dimensions' => '48x48x48']]
        ];
        $this->packagingCheckoutPricing->expects($this->once())
            ->method('getPackagingItems')
            ->willReturn($freightInfo);

        $sellerPackage = [
            ['packaging' => ['weight' => 500, 'dimensions' => '48x48x48']]
        ];
        $this->packagingCheckoutPricing->expects($this->once())
            ->method('findSellerRecord')
            ->with($shopId, $freightInfo)
            ->willReturn($sellerPackage);

        $quoteItem->expects($this->once())->method('setMiraklBaseShippingFee')->with($shippingAmount);
        $quoteItem->expects($this->once())->method('setMiraklShippingFee')->with($shippingAmount);
        $quoteItem->expects($this->once())->method('setMiraklBaseShippingExclTax')->with($shippingAmount);
        $quoteItem->expects($this->once())->method('setMiraklShippingExclTax')->with($shippingAmount);
        $quoteItem->expects($this->once())->method('setMiraklBaseShippingInclTax')->with($shippingAmount);
        $quoteItem->expects($this->once())->method('setMiraklShippingInclTax')->with($shippingAmount);
        $quoteItem->expects($this->once())->method('setMiraklShippingTaxPercent')->with(0);
        $quoteItem->expects($this->once())->method('setMiraklBaseShippingTaxAmount')->with(0);
        $quoteItem->expects($this->once())->method('setMiraklShippingTaxAmount')->with(0);
        $quoteItem->expects($this->once())->method('setMiraklShippingTaxApplied')->with(null);
        $quoteItem->expects($this->once())->method('setMiraklShopName')->with($shopName);
        $quoteItem->expects($this->once())->method('setMiraklLeadtimeToShip')->with($leadTime);
        $quoteItem->expects($this->once())->method('setMiraklShippingType')->with($shippingType);
        $quoteItem->expects($this->once())->method('setMiraklShippingTypeLabel')->with($shippingTypeLabel);

        $quoteItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return
                    isset($data['punchout_enabled']) &&
                    isset($data['packaging_data']) &&
                    isset($data['mirakl_shipping_data']) &&
                    isset($data['freight_data']) &&
                    $data['freight_data'] === ['weight' => 500, 'dimensions' => '48x48x48'];
            }));

        $this->marketplaceHelper->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->cartItemRepository->expects($this->once())
            ->method('save')
            ->with($quoteItem);

        $result = $this->quoteOptions->saveQuoteItemMiraklShippingPrices(
            $quoteItem,
            $shippingAmount,
            $shopName,
            $leadTime,
            $shippingType,
            $shippingTypeLabel
        );

        $this->assertNull($result, 'saveQuoteItemMiraklShippingPrices (freight) should return null');
    }

    /**
     * @return void
     */
    public function testSaveQuoteItemMiraklShippingPricesWithEssendantEnabledAndConfigurableItem(): void
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('configurable');

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'getProduct'])
            ->addMethods([
                'setMiraklBaseShippingFee',
                'setMiraklShippingFee',
                'setMiraklBaseShippingExclTax',
                'setMiraklShippingExclTax',
                'setMiraklBaseShippingInclTax',
                'setMiraklShippingInclTax',
                'setMiraklShippingTaxPercent',
                'setMiraklBaseShippingTaxAmount',
                'setMiraklShippingTaxAmount',
                'setMiraklShippingTaxApplied',
                'setMiraklShopName',
                'setMiraklLeadtimeToShip',
                'setMiraklShippingType',
                'setMiraklShippingTypeLabel',
                'getAdditionalData'
            ])
            ->getMock();

        $quoteItem->method('getProduct')->willReturn($product);

        $quoteItem->method('getAdditionalData')->willReturn('{}');

        $quoteItem->expects($this->once())->method('setMiraklBaseShippingFee')->with(15.00);
        $quoteItem->expects($this->once())->method('setMiraklShippingFee')->with(15.00);
        $quoteItem->expects($this->once())->method('setMiraklBaseShippingExclTax')->with(15.00);
        $quoteItem->expects($this->once())->method('setMiraklShippingExclTax')->with(15.00);
        $quoteItem->expects($this->once())->method('setMiraklBaseShippingInclTax')->with(15.00);
        $quoteItem->expects($this->once())->method('setMiraklShippingInclTax')->with(15.00);
        $quoteItem->expects($this->once())->method('setMiraklShippingTaxPercent')->with(0);
        $quoteItem->expects($this->once())->method('setMiraklBaseShippingTaxAmount')->with(0);
        $quoteItem->expects($this->once())->method('setMiraklShippingTaxAmount')->with(0);
        $quoteItem->expects($this->once())->method('setMiraklShippingTaxApplied')->with(null);
        $quoteItem->expects($this->once())->method('setMiraklShopName')->with('Premium Shop');
        $quoteItem->expects($this->once())->method('setMiraklLeadtimeToShip')->with('2 days');
        $quoteItem->expects($this->once())->method('setMiraklShippingType')->with('Premium');
        $quoteItem->expects($this->once())->method('setMiraklShippingTypeLabel')->with('Premium Shipping');

        $quoteItem->expects($this->once())->method('save');

        $this->cartItemRepository->expects($this->never())->method('save');

        $this->marketplaceHelper
            ->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $result = $this->quoteOptions->saveQuoteItemMiraklShippingPrices(
            $quoteItem,
            15.00,
            'Premium Shop',
            '2 days',
            'Premium',
            'Premium Shipping'
        );

        $this->assertNull($result, 'saveQuoteItemMiraklShippingPrices (configurable) should return null');
    }

    /**
     * @return void
     */
    public function testFindSellerSummary()
    {
        $shopId = 123;
        $quote = [
            ['seller_id' => 123, 'other_info' => 'value1'],
            ['seller_id' => 456, 'other_info' => 'value2']
        ];

        $result = $this->quoteOptions->findSellerSummary($shopId, $quote);

        $this->assertIsArray($result);
        $this->assertEquals(123, $result['seller_id']);
    }

    /**
     * @return void
     */
    public function testFindSellerSummaryReturnsNullWhenNotFound(): void
    {
        $shopId = 999;
        $quote = [
            ['seller_id' => 123, 'other_info' => 'value1'],
            ['seller_id' => 456, 'other_info' => 'value2']
        ];

        $result = $this->quoteOptions->findSellerSummary($shopId, $quote);

        $this->assertNull($result, 'findSellerSummary should return null when no matching seller is found');
    }

    /**
     * @return void
     */
    public function testSaveQuoteMiraklShippingPrices()
    {
        $shippingPrice = 15.99;

        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'setMiraklBaseShippingFee',
                'setMiraklShippingFee',
                'setMiraklBaseShippingExclTax',
                'setMiraklShippingExclTax',
                'setMiraklBaseShippingInclTax',
                'setMiraklShippingInclTax',
            ])
            ->onlyMethods(['save'])
            ->getMock();

        $quote->expects($this->once())
            ->method('setMiraklBaseShippingFee')
            ->with($this->equalTo($shippingPrice))
            ->willReturnSelf();

        $quote->expects($this->once())
            ->method('setMiraklShippingFee')
            ->with($this->equalTo($shippingPrice))
            ->willReturnSelf();

        $quote->expects($this->once())
            ->method('setMiraklBaseShippingExclTax')
            ->with($this->equalTo($shippingPrice))
            ->willReturnSelf();

        $quote->expects($this->once())
            ->method('setMiraklShippingExclTax')
            ->with($this->equalTo($shippingPrice))
            ->willReturnSelf();

        $quote->expects($this->once())
            ->method('setMiraklBaseShippingInclTax')
            ->with($this->equalTo($shippingPrice))
            ->willReturnSelf();

        $quote->expects($this->once())
            ->method('setMiraklShippingInclTax')
            ->with($this->equalTo($shippingPrice))
            ->willReturnSelf();

        $quote->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $result = $this->quoteOptions->saveQuoteMiraklShippingPrices($quote, $shippingPrice);
        $this->assertNull($result, 'saveQuoteMiraklShippingPrices should return null');
    }

    /**
     * Test setMktShipMethodDataItemOptionsUpdated with empty data.
     *
     * @return void
     */
    public function testEmptyDataReturnsSelf(): void
    {
        $quote = $this->createMock(Quote::class);
        $result = $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated('', $quote);
        $this->assertSame($this->quoteOptions, $result);
    }

    /**
     * Test setMktShipMethodDataItemOptionsUpdated with no item_id in the payload.
     *
     * @return void
     */
    public function testNoItemIdDoesNothing()
    {
        $quote = $this->createMock(Quote::class);

        $this->marketplaceHelper
            ->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([]);

        $this->cartItemRepository->expects($this->never())
            ->method('save');

        $shipMethodData = json_encode(['foo' => 'bar']);
        
        $result = $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated($shipMethodData, $quote);
        $this->assertNull($result, 'Expected null when there is no item_id in the payload.');
    }

    /**
     * Test setMktShipMethodDataItemOptionsUpdated with Essendant enabled and configurable item.
     *
     * @return void
     */
    public function testEssendantTrueConfigurableItemCallsItemSave(): void
    {
        $shipData = json_encode([
            'item_id'   => 10,
            'seller_id' => 55,
            'foo'       => 'bar'
        ]);
        $quote = $this->createMock(Quote::class);

        $this->marketplaceHelper
            ->expects($this->exactly(2))
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('configurable');

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'save', 'getProduct'])
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->getMock();

        $item->method('getData')->with('mirakl_shop_id')->willReturn(55);
        $item->method('getMiraklOfferId')->willReturn('offer');
        $item->method('getProduct')->willReturn($product);
        $item->method('getAdditionalData')->willReturn(json_encode([
            'mirakl_shipping_data' => ['address' => ['a' => 'b']]
        ]));

        $quote->method('getAllVisibleItems')->willReturn([$item]);
        $quote->method('getAllItems')->willReturn([]);
        $quote->method('getItemById')->with(10)->willReturn($item);

        $item->expects($this->once())->method('save');
        $this->cartItemRepository->expects($this->never())->method('save');

        $result = $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated($shipData, $quote);
        $this->assertNull($result, 'Expected null after handling configurable save.');
    }

    /**
     * Test setMktShipMethodDataItemOptionsUpdated with Essendant disabled and simple item.
     *
     * @return void
     */
    public function testEssendantFalseSimpleItemCallsCartRepositorySave(): void
    {
        $shipData = json_encode([
            'item_id'   => 20,
            'seller_id' => 99
        ]);
        $quote = $this->createMock(Quote::class);

        $this->marketplaceHelper
            ->expects($this->exactly(2))
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('simple');

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'save', 'getProduct'])
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->getMock();

        $item->method('getData')->with('mirakl_shop_id')->willReturn(99);
        $item->method('getMiraklOfferId')->willReturn('OFFER123');
        $item->method('getProduct')->willReturn($product);
        $item->method('getAdditionalData')->willReturn(json_encode([
            'mirakl_shipping_data' => []
        ]));

        $quote->method('getAllVisibleItems')->willReturn([]); // unused
        $quote->method('getAllItems')->willReturn([$item]);
        $quote->method('getItemById')->with(20)->willReturn($item);

        $this->cartItemRepository->expects($this->once())->method('save')->with($item);
        $item->expects($this->never())->method('save');

        $result = $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated($shipData, $quote);
        $this->assertNull($result, 'Expected null after repository save.');
    }

    /**
     * Test address check on and off branches.
     *
     * @return void
     */
    public function testAddressCheckOnAndOffBranches(): void
    {
        $shipData = json_encode([
            'item_id'   => 5,
            'seller_id' => 7
        ]);
        $quote = $this->createMock(Quote::class);

        foreach ([true, false] as $checkOn) {
            $this->marketplaceHelper->method('isEssendantToggleEnabled')->willReturn(false);

            $product = $this->createMock(\Magento\Catalog\Model\Product::class);
            $product->method('getTypeId')->willReturn('simple');

            $item = $this->getMockBuilder(QuoteItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getData', 'getProduct'])
                ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
                ->getMock();

            $existingAddr = ['street' => '1', 'city' => 'C'];
            $item->method('getData')->with('mirakl_shop_id')->willReturn(7);
            $item->method('getMiraklOfferId')->willReturn('O');
            $item->method('getProduct')->willReturn($product);

            $item->method('getAdditionalData')
                ->willReturn(json_encode(['mirakl_shipping_data' => ['address' => $existingAddr]]));

            $item->method('setAdditionalData')
                ->with($this->callback(function (string $json) use ($existingAddr) {
                    $decoded = json_decode($json, true);
                    return isset($decoded['mirakl_shipping_data']['address'])
                        && $decoded['mirakl_shipping_data']['address'] === $existingAddr;
                }));

            $quote->method('getAllItems')->willReturn([$item]);
            $quote->method('getItemById')->willReturn($item);

            $this->cartItemRepository->expects($this->once())->method('save');

            $result = $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated($shipData, $quote);
            $this->assertNull($result, 'Expected setMktShipMethodDataItemOptionsUpdated to return null.');

            $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
            $this->quoteOptions = new QuoteOptions(
                $this->cartItemRepository,
                $this->marketplaceHelper,
                $this->logger,
                $this->packagingCheckoutPricing
            );
        }
    }

    /**
     * Test resetShipInfoInAdditionalData uses correct items based on Essendant toggle.
     *
     * @return void
     */
    public function testResetShipInfoUsesCorrectItemsBasedOnEssendantToggle(): void
    {
        foreach ([true, false] as $essendantEnabled) {
            $quote = $this->createMock(Quote::class);
            $this->marketplaceHelper = $this->createMock(MarketplaceHelper::class);
            $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
            $this->logger = $this->createMock(LoggerInterface::class);
            $this->packagingCheckoutPricing = $this->createMock(PackagingCheckoutPricing::class);

            $quoteOptions = new QuoteOptions(
                $this->cartItemRepository,
                $this->marketplaceHelper,
                $this->logger,
                $this->packagingCheckoutPricing
            );

            $this->marketplaceHelper
                ->expects($this->once())
                ->method('isEssendantToggleEnabled')
                ->willReturn($essendantEnabled);

            $item = $this->getMockBuilder(QuoteItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getData'])
                ->addMethods(['getAdditionalData', 'setAdditionalData'])
                ->getMock();

            $item->method('getData')->willReturn(null);
            $item->method('getAdditionalData')->willReturn('{"mirakl_shipping_data":{}}');

            if ($essendantEnabled) {
                $quote->expects($this->once())
                    ->method('getAllVisibleItems')
                    ->willReturn([$item]);

                $quote->expects($this->never())
                    ->method('getAllItems');
            } else {
                $quote->expects($this->once())
                    ->method('getAllItems')
                    ->willReturn([$item]);

                $quote->expects($this->never())
                    ->method('getAllVisibleItems');
            }

            $reflectionClass = new \ReflectionClass(QuoteOptions::class);
            $method = $reflectionClass->getMethod('resetShipInfoInAdditionalData');
            $method->setAccessible(true);

            $invocationResult = $method->invoke($quoteOptions, $quote);
            $this->assertNull($invocationResult, 'resetShipInfoInAdditionalData should return null.');
        }
    }

    /**
     * @return void
     */
    public function testResetShipInfoDirectSaveWithEssendantEnabled(): void
    {
        $quote = $this->createMock(Quote::class);
        $this->marketplaceHelper = $this->createMock(MarketplaceHelper::class);
        $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->packagingCheckoutPricing = $this->createMock(PackagingCheckoutPricing::class);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getTypeId')->willReturn('configurable');

        $quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'save', 'getProduct'])
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->getMock();

        $quoteItem->method('getMiraklOfferId')->willReturn('OFFER123');
        $quoteItem->method('getProduct')->willReturn($product);
        $quoteItem->method('getAdditionalData')
            ->willReturn(json_encode(['mirakl_shipping_data' => ['amount' => 10.00]]));

        $quoteOptions = new QuoteOptions(
            $this->cartItemRepository,
            $this->marketplaceHelper,
            $this->logger,
            $this->packagingCheckoutPricing
        );

        $this->marketplaceHelper
            ->expects($this->exactly(2))
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $quoteItem->expects($this->once())->method('save');

        $this->cartItemRepository->expects($this->never())->method('save');

        $quote->method('getAllVisibleItems')->willReturn([$quoteItem]);

        $reflectionClass = new \ReflectionClass(QuoteOptions::class);
        $method = $reflectionClass->getMethod('resetShipInfoInAdditionalData');
        $method->setAccessible(true);

        $invocationResult = $method->invoke($quoteOptions, $quote);
        $this->assertNull($invocationResult, 'resetShipInfoInAdditionalData should return null.');
    }
}
