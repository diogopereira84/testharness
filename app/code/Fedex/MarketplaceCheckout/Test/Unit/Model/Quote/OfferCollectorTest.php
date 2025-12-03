<?php

/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Quote;

use Magento\Catalog\Model\Product;
use Mirakl\Connector\Model\Quote\Cache;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item;
use Mirakl\Connector\Model\OfferFactory;
use Fedex\MarketplaceCheckout\Model\Quote\OfferCollector;
use Magento\Quote\Model\Quote\Item\Option;

class OfferCollectorTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Api\Data\CartInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteMock;

    /**
     * @var (\Magento\Quote\Model\ResourceModel\Quote\ItemFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteItemMock;

    /**
     * @var (\Mirakl\Connector\Model\OfferFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $offerFactoryMock;

    /**
     * @var (\Mirakl\Connector\Model\Quote\Cache & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cacheMock;

    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * Sets up the test environment before each test.
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAllItems'])
            ->getMockForAbstractClass();

        $this->quoteItemMock = $this->createMock(ItemFactory::class);
        $this->offerFactoryMock = $this->createMock(OfferFactory::class);
        $this->cacheMock = $this->createMock(Cache::class);

        $this->offerCollector = new OfferCollector(
            $this->quoteItemMock,
            $this->offerFactoryMock,
            $this->cacheMock
        );
    }

    /**
     * @return void
     */
    public function testGetItemsWithOffer(): void
    {
        $option = $this->createMock(Option::class);
        $option->expects($this->once())->method('getValue')->willReturn('some_offer');

        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('getCustomOption')
            ->with('mirakl_offer')->willReturn($option);

        $item = $this->createMock(Item::class);
        $item->expects($this->once())->method('getProduct')->willReturn($product);
        $items = [$item];

        $offer = $this->createMock(\Mirakl\Connector\Model\Offer::class);
        $offer->expects($this->once())->method('getId')->willReturn(10);
        $this->offerFactoryMock->expects($this->once())
            ->method('fromJson')->willReturn($offer);

        $this->quoteMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn($items);

        $return = $this->offerCollector->getItemsWithOffer($this->quoteMock);
        $this->assertTrue(isset($return[10]));
    }

    /**
     * @return void
     */
    public function testGetItemsWithoutOffer(): void
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('getCustomOption')
            ->with('mirakl_offer')->willReturn(null);

        $item = $this->createMock(Item::class);
        $item->expects($this->once())->method('getProduct')->willReturn($product);
        $items = [$item];

        $this->quoteMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn($items);

        $return = $this->offerCollector->getItemsWithOffer($this->quoteMock);
        $this->assertEquals([], $return);
    }

    /**
     * Test that cached results are returned when available
     *
     * @return void
     */
    public function testGetItemsWithOfferFromCache(): void
    {
        $hash = 'test_hash_value';

        $cachedResult = ['cached_offer_id' => 'cached_item'];

        $quoteId = 123;
        $this->quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $this->cacheMock->expects($this->once())
            ->method('getQuoteControlHash')
            ->with($this->quoteMock)
            ->willReturn($hash);

        $this->cacheMock->expects($this->once())
            ->method('getCachedMethodResult')
            ->with('Fedex\MarketplaceCheckout\Model\Quote\OfferCollector::getItemsWithOffer', $quoteId, $hash)
            ->willReturn($cachedResult);

        $this->quoteMock->expects($this->never())
            ->method('getAllItems');

        $result = $this->offerCollector->getItemsWithOffer($this->quoteMock);

        $this->assertSame($cachedResult, $result);
    }

    /**
     * Test that deleted items and child items are skipped
     *
     * @return void
     */
    public function testGetItemsWithDeletedOrChildItems(): void
    {
        $deletedItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted', 'getProduct'])
            ->addMethods(['getParentItemId'])
            ->getMock();
        $deletedItem->expects($this->once())
            ->method('isDeleted')
            ->willReturn(true);
        $deletedItem->expects($this->never())
            ->method('getProduct');

        $childItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted', 'getProduct'])
            ->addMethods(['getParentItemId'])
            ->getMock();
        $childItem->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);
        $childItem->expects($this->once())
            ->method('getParentItemId')
            ->willReturn(123);
        $childItem->expects($this->never())
            ->method('getProduct');

        $normalProduct = $this->createMock(Product::class);
        $normalProduct->expects($this->once())
            ->method('getCustomOption')
            ->with('mirakl_offer')
            ->willReturn(null);

        $normalItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted', 'getProduct'])
            ->addMethods(['getParentItemId'])
            ->getMock();
        $normalItem->expects($this->once())->method('isDeleted')->willReturn(false);
        $normalItem->expects($this->once())->method('getParentItemId')->willReturn(null);
        $normalItem->expects($this->once())->method('getProduct')->willReturn($normalProduct);

        $items = [$deletedItem, $childItem, $normalItem];
        $this->quoteMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn($items);

        $result = $this->offerCollector->getItemsWithOffer($this->quoteMock);

        $this->assertEquals([], $result);
    }
}
