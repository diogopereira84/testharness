<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\CartInfoGrouper;
use Fedex\MarketplaceCheckout\Model\Constants\DateConstants;
use Fedex\MarketplaceCheckout\Model\Constants\UnitConstants;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceProduct\Model\Shop;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fedex\MarketplaceCheckout\Model\CartInfoGrouper
 */
class CartInfoGrouperTest extends TestCase
{
    /**
     * @var ShopRepositoryInterface|MockObject
     */
    private $shopRepositoryMock;

    /**
     * @var CartInfoGrouper
     */
    private CartInfoGrouper $cartInfoGrouper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->shopRepositoryMock = $this->getMockBuilder(ShopRepositoryInterface::class)
            ->addMethods(['getByIds'])
            ->getMockForAbstractClass();

        $this->cartInfoGrouper = new CartInfoGrouper(
            $this->shopRepositoryMock
        );
    }

    /**
     * Test getMarketplaceCartInfoGroupedBySeller method.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetMarketplaceCartInfoGroupedBySeller(): void
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllItems'])
            ->getMock();

        $itemMethodsToMock = [
            'real' => ['getData', 'getQty'],
            'magic' => ['getMiraklShopId', 'getAdditionalData', 'getWeight']
        ];

        // Item 1: Marketplace, Shop 10, LBS weight
        $item1Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item1Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-123');
        $item1Mock->method('getMiraklShopId')->willReturn(10);
        $item1Mock->method('getQty')->willReturn(1);
        $item1Mock->method('getWeight')->willReturn(2.5);
        $item1Mock->method('getAdditionalData')->willReturn(json_encode([
            'weight_unit' => 'LBS',
            DateConstants::BUSINESS_DAYS => 2
        ]));

        // Item 2: Marketplace, Shop 10, OZ weight
        $item2Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item2Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-456');
        $item2Mock->method('getMiraklShopId')->willReturn(10);
        $item2Mock->method('getQty')->willReturn(2);
        $item2Mock->method('getWeight')->willReturn(32.0);
        $item2Mock->method('getAdditionalData')->willReturn(json_encode([
            'weight_unit' => UnitConstants::WEIGHT_OZ_UNIT,
            DateConstants::BUSINESS_DAYS => 3
        ]));

        // Item 3: Marketplace, Shop 20, default LBS weight
        $item3Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item3Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-789');
        $item3Mock->method('getMiraklShopId')->willReturn(20);
        $item3Mock->method('getQty')->willReturn(1);
        $item3Mock->method('getWeight')->willReturn(5.0);
        $item3Mock->method('getAdditionalData')->willReturn(json_encode([
            DateConstants::BUSINESS_DAYS => 5
        ]));

        // Item 4: Non-marketplace item
        $item4Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $item4Mock->method('getData')->with('mirakl_offer_id')->willReturn(null);

        // Item 5: Shop 20, getAdditionalData() is null
        $item5Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item5Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-abc');
        $item5Mock->method('getMiraklShopId')->willReturn(20);
        $item5Mock->method('getQty')->willReturn(2);
        $item5Mock->method('getWeight')->willReturn(1.5);
        $item5Mock->method('getAdditionalData')->willReturn(null);

        // Item 6: Shop 20, additional data exists, but no business_days
        $item6Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item6Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-def');
        $item6Mock->method('getMiraklShopId')->willReturn(20);
        $item6Mock->method('getQty')->willReturn(1);
        $item6Mock->method('getWeight')->willReturn(0.5);
        $item6Mock->method('getAdditionalData')->willReturn(json_encode(['another_key' => 'value']));

        // Item 7: Shop 20, business_days is 0
        $item7Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item7Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-ghi');
        $item7Mock->method('getMiraklShopId')->willReturn(20);
        $item7Mock->method('getQty')->willReturn(3);
        $item7Mock->method('getWeight')->willReturn(1.0);
        $item7Mock->method('getAdditionalData')->willReturn(json_encode([DateConstants::BUSINESS_DAYS => 0]));

        // Item 8: Shop 30, business_days is 0
        $item8Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item8Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-jkl');
        $item8Mock->method('getMiraklShopId')->willReturn(30);
        $item8Mock->method('getQty')->willReturn(1);
        $item8Mock->method('getWeight')->willReturn(1.0);
        $item8Mock->method('getAdditionalData')->willReturn(json_encode([DateConstants::BUSINESS_DAYS => 0]));

        // Item 9: Shop 30, no business_days in additional data
        $item9Mock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $item9Mock->method('getData')->with('mirakl_offer_id')->willReturn('offer-mno');
        $item9Mock->method('getMiraklShopId')->willReturn(30);
        $item9Mock->method('getQty')->willReturn(1);
        $item9Mock->method('getWeight')->willReturn(1.0);
        $item9Mock->method('getAdditionalData')->willReturn(json_encode(['another_key' => 'value']));

        $quoteMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item1Mock, $item2Mock, $item3Mock, $item4Mock, $item5Mock, $item6Mock, $item7Mock, $item8Mock, $item9Mock]);

        $shop10Mock = $this->createMock(Shop::class);
        $shop20Mock = $this->createMock(Shop::class);
        $shop30Mock = $this->createMock(Shop::class);

        $this->shopRepositoryMock->expects($this->once())
            ->method('getByIds')
            ->with([10, 20, 30])
            ->willReturn([
                10 => $shop10Mock,
                20 => $shop20Mock,
                30 => $shop30Mock
            ]);

        set_error_handler(static fn () => true, E_NOTICE);
        $result = $this->cartInfoGrouper->getMarketplaceCartInfoGroupedBySeller($quoteMock);
        restore_error_handler();

        $this->assertCount(3, $result);
        $this->assertArrayHasKey(10, $result);
        $this->assertArrayHasKey(20, $result);
        $this->assertArrayHasKey(30, $result);

        $shop10Data = $result[10];
        $this->assertSame($shop10Mock, $shop10Data['shop']);
        $this->assertCount(2, $shop10Data['items']);
        $this->assertSame([$item1Mock, $item2Mock], $shop10Data['items']);
        $this->assertEquals([2, 3], $shop10Data[DateConstants::BUSINESS_DAYS]);
        $this->assertEqualsWithDelta(6.5, $shop10Data['weight'], 0.001);

        $shop20Data = $result[20];
        $this->assertSame($shop20Mock, $shop20Data['shop']);
        $this->assertCount(4, $shop20Data['items']);
        $this->assertSame([$item3Mock, $item5Mock, $item6Mock, $item7Mock], $shop20Data['items']);
        $this->assertEquals([5], $shop20Data[DateConstants::BUSINESS_DAYS]);
        $this->assertEqualsWithDelta(11.5, $shop20Data['weight'], 0.001);

        $shop30Data = $result[30];
        $this->assertSame($shop30Mock, $shop30Data['shop']);
        $this->assertCount(2, $shop30Data['items']);
        $this->assertSame([$item8Mock, $item9Mock], $shop30Data['items']);
        $this->assertEmpty($shop30Data[DateConstants::BUSINESS_DAYS]);
        $this->assertEqualsWithDelta(2.0, $shop30Data['weight'], 0.001);
    }

    /**
     * Test grouping with an empty quote.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetMarketplaceCartInfoGroupedBySellerWithEmptyQuote(): void
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllItems'])
            ->getMock();
        $quoteMock->method('getAllItems')->willReturn([]);

        $this->shopRepositoryMock->expects($this->once())
            ->method('getByIds')
            ->with([])
            ->willReturn([]);

        $result = $this->cartInfoGrouper->getMarketplaceCartInfoGroupedBySeller($quoteMock);

        $this->assertEmpty($result);
    }

    /**
     * Test grouping with an invalid shop ID.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetMarketplaceCartInfoGroupedBySellerWithInvalidShop(): void
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllItems'])
            ->getMock();

        $itemMethodsToMock = [
            'real' => ['getData', 'getQty'],
            'magic' => ['getMiraklShopId', 'getAdditionalData', 'getWeight']
        ];

        $itemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods($itemMethodsToMock['real'])
            ->addMethods($itemMethodsToMock['magic'])
            ->getMock();
        $itemMock->method('getData')->with('mirakl_offer_id')->willReturn('offer-999');
        $itemMock->method('getMiraklShopId')->willReturn(99);
        $itemMock->method('getQty')->willReturn(1);
        $itemMock->method('getWeight')->willReturn(1.0);
        $itemMock->method('getAdditionalData')->willReturn(json_encode([DateConstants::BUSINESS_DAYS => 1]));

        $quoteMock->method('getAllItems')->willReturn([$itemMock]);

        $this->shopRepositoryMock->expects($this->once())
            ->method('getByIds')
            ->with([99])
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);

        $this->cartInfoGrouper->getMarketplaceCartInfoGroupedBySeller($quoteMock);
    }
}