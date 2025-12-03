<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Plugin;

use Fedex\MarketplaceCheckout\Plugin\OfferCollectorPlugin;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Catalog\Model\Product;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\Quote\Cache;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory;
use Mirakl\MMP\Front\Domain\Shipping\OfferQuantityShippingTypeTuple;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OfferCollectorPluginTest extends TestCase
{
    /** @var OfferCollectorPlugin */
    private $plugin;

    /** @var MarketplaceConfig|MockObject */
    private $marketplaceConfig;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\ItemFactory|MockObject */
    private $quoteItemResourceFactory;

    /** @var OfferFactory|MockObject */
    private $offerFactory;

    /** @var Cache|MockObject */
    private $cache;

    /** @var Data|MockObject */
    private $helperData;

    protected function setUp(): void
    {
        $this->marketplaceConfig        = $this->createMock(MarketplaceConfig::class);
        $this->quoteItemResourceFactory = $this->createMock(ItemFactory::class);
        $this->offerFactory             = $this->createMock(OfferFactory::class);
        $this->cache                    = $this->createMock(Cache::class);
        $this->helperData               = $this->createMock(Data::class);

        $this->plugin = new OfferCollectorPlugin(
            $this->marketplaceConfig,
            $this->quoteItemResourceFactory,
            $this->offerFactory,
            $this->cache,
            $this->helperData
        );
    }

    /**
     * Test retrieval of quote items from the registry.
     * @return void
     */
    public function testGetQuoteItemsFromRegistry(): void
    {
        $quoteId     = 123;
        $registryKey = sprintf('mirakl_quote_items_%d', $quoteId);
        $quoteItems  = ['item1', 'item2'];

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $this->cache->expects($this->once())
            ->method('registry')
            ->with($registryKey)
            ->willReturn($quoteItems);

        $quote->expects($this->never())
            ->method('getAllItems');

        $this->cache->expects($this->never())
            ->method('register');

        $result = $this->plugin->getQuoteItems($quote);
        $this->assertSame($quoteItems, $result);
    }

    /**
     * Test retrieval of quote items when they are not in the registry.
     * @return void
     */
    public function testGetQuoteItemsNotInRegistry(): void
    {
        $quoteId     = 123;
        $registryKey = sprintf('mirakl_quote_items_%d', $quoteId);
        $quoteItems  = ['item1', 'item2'];

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $this->cache->expects($this->once())
            ->method('registry')
            ->with($registryKey)
            ->willReturn(null);

        $quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn($quoteItems);

        $this->cache->expects($this->once())
            ->method('register')
            ->with($registryKey, $quoteItems);

        $result = $this->plugin->getQuoteItems($quote);
        $this->assertSame($quoteItems, $result);
    }

    /**
     * Test retrieval of quote items with offers.
     * @return void
     */
    public function testGetItemsWithOffer(): void
    {
        $quoteItem1 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted', 'getProduct', 'getId'])
            ->getMock();
        $quoteItem1->method('getId')->willReturn(1);
        $quoteItem1->method('isDeleted')->willReturn(false);
        $quoteItem1->setData('parent_item_id', null);
        $quoteItem1->setData('item_id', 1);

        $product1       = $this->createMock(Product::class);
        $offerOption1   = $this->createMock(QuoteItemOption::class);
        $offerOption1->method('getValue')->willReturn('{"id":"offer1"}');
        $product1->method('getCustomOption')
            ->with('mirakl_offer')
            ->willReturn($offerOption1);
        $quoteItem1->method('getProduct')->willReturn($product1);

        $quoteItem2 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted'])
            ->getMock();
        $quoteItem2->method('isDeleted')->willReturn(true);

        $quoteItem3 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted'])
            ->getMock();
        $quoteItem3->method('isDeleted')->willReturn(false);
        $quoteItem3->setData('parent_item_id', 55);

        $quoteItem4 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted', 'getProduct'])
            ->getMock();
        $quoteItem4->method('isDeleted')->willReturn(false);
        $quoteItem4->setData('parent_item_id', null);
        $product4 = $this->createMock(Product::class);
        $product4->method('getCustomOption')
            ->with('mirakl_offer')
            ->willReturn(null);
        $quoteItem4->method('getProduct')->willReturn($product4);

        $allItems = [$quoteItem1, $quoteItem2, $quoteItem3, $quoteItem4];

        $pluginPartial = $this->getMockBuilder(OfferCollectorPlugin::class)
            ->setConstructorArgs([
                $this->marketplaceConfig,
                $this->quoteItemResourceFactory,
                $this->offerFactory,
                $this->cache,
                $this->helperData
            ])
            ->onlyMethods(['getQuoteItems'])
            ->getMock();

        $pluginPartial->expects($this->once())
            ->method('getQuoteItems')
            ->willReturn($allItems);

        $offerStub = $this->createMock(Offer::class);
        $offerStub->method('getId')->willReturn('offer1');
        $this->offerFactory
            ->expects($this->once())
            ->method('fromJson')
            ->with('{"id":"offer1"}')
            ->willReturn($offerStub);

        $quoteModel = $this->createMock(Quote::class);

        $result = $pluginPartial->getItemsWithOffer($quoteModel);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertSame($quoteItem1, $result[1]);

        $this->assertSame($offerStub, $quoteItem1->getData('offer'));
    }

    /**
     * Test insertion of leadtime to ship into offers.
     * @return void
     */
    public function testInsertLeadtimeToShip(): void
    {
        $quote = $this->createMock(Quote::class);

        $item1 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted'])
            ->getMock();
        $item1->setData('mirakl_offer_id', 'offer1');
        $item1->setData('sku', 'sku1');
        $item1->setData('mirakl_shop_id', 'shop1');
        $item1->setData('mirakl_leadtime_to_ship', 5);
        $item1->method('isDeleted')->willReturn(false);

        $item2 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted'])
            ->getMock();
        $item2->setData('mirakl_offer_id', 'offer2');
        $item2->setData('sku', 'sku2');
        $item2->setData('mirakl_shop_id', 'shop1');
        $item2->setData('mirakl_leadtime_to_ship', 7);
        $item2->method('isDeleted')->willReturn(false);

        $item3 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isDeleted'])
            ->getMock();
        $item3->setData('mirakl_offer_id', null);
        $item3->method('isDeleted')->willReturn(false);

        $quote->method('getAllItems')
            ->willReturn([$item1, $item2, $item3]);

        $this->marketplaceConfig
            ->method('getShopCustomAttributesByProductSku')
            ->willReturnMap([
                ['sku1', ['shipping-rate-options' => 'mirakl-shipping-rates']],
                ['sku2', ['shipping-rate-options' => 'mirakl-shipping-rates']],
            ]);

        $offerQty1 = $this->getMockBuilder(OfferQuantityShippingTypeTuple::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOfferId', 'setLeadtimeToShip'])
            ->getMock();
        $offerQty1->method('getOfferId')->willReturn('offer1');
        $offerQty1->expects($this->once())
            ->method('setLeadtimeToShip')
            ->with(7)
            ->willReturnSelf();

        $offerQty2 = $this->getMockBuilder(OfferQuantityShippingTypeTuple::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOfferId', 'setLeadtimeToShip'])
            ->getMock();
        $offerQty2->method('getOfferId')->willReturn('offer2');
        $offerQty2->expects($this->once())
            ->method('setLeadtimeToShip')
            ->with(7)
            ->willReturnSelf();

        $offerQty3 = $this->getMockBuilder(OfferQuantityShippingTypeTuple::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOfferId', 'setLeadtimeToShip'])
            ->getMock();
        $offerQty3->method('getOfferId')->willReturn('not_in_list');
        $offerQty3->expects($this->never())
            ->method('setLeadtimeToShip');

        $offersWithQty = [$offerQty1, $offerQty2, $offerQty3];
        $result        = $this->plugin->insertLeadtimeToShip($quote, $offersWithQty);

        $this->assertSame($offersWithQty, $result);
    }

    /**
     * Test aroundGetOffersWithQty method.
     * @return void
     */
    public function testAroundGetOffersWithQty(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllItems'])
            ->getMock();
        $quote->method('getId')->willReturn(555);

        $quoteItem1 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $quoteItem1->method('getId')->willReturn(1);
        $fakeOfferA = $this->createMock(Offer::class);
        $fakeOfferA->method('getId')->willReturn('offerA');
        $quoteItem1->setData('offer', $fakeOfferA);
        $quoteItem1->setData('qty', 2);
        $quoteItem1->setData('mirakl_shipping_type', 'FAST');
        $quoteItem1->setData('item_id', 1);

        $quoteItem2 = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $quoteItem2->method('getId')->willReturn(2);
        $fakeOfferB = $this->createMock(Offer::class);
        $fakeOfferB->method('getId')->willReturn('offerB');
        $quoteItem2->setData('offer', $fakeOfferB);
        $quoteItem2->setData('qty', 3);
        $quoteItem2->setData('mirakl_shipping_type', 'SLOW');
        $quoteItem2->setData('item_id', 2);

        $pluginPartial = $this->getMockBuilder(OfferCollectorPlugin::class)
            ->setConstructorArgs([
                $this->marketplaceConfig,
                $this->quoteItemResourceFactory,
                $this->offerFactory,
                $this->cache,
                $this->helperData
            ])
            ->onlyMethods(['getItemsWithOffer', 'insertLeadtimeToShip'])
            ->getMock();

        $pluginPartial->expects($this->once())
            ->method('getItemsWithOffer')
            ->with($quote)
            ->willReturn([$quoteItem1, $quoteItem2]);

        $expectedReturn = ['DONE'];
        $pluginPartial->expects($this->once())
            ->method('insertLeadtimeToShip')
            ->willReturn($expectedReturn);

        $subject = $this->createMock(OfferCollector::class);
        $proceed = function () {
            $this->fail('aroundGetOffersWithQty should not call $proceed');
        };

        $actual = $pluginPartial->aroundGetOffersWithQty($subject, $proceed, $quote);
        $this->assertSame($expectedReturn, $actual);
    }

    /**
     * Test aroundGetOffersWithQty with null leadtime to ship.
     * @return void
     */
    public function testAroundGetOffersWithQtyWithNullLeadtimeToShip(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllItems'])
            ->getMock();
        $quote->method('getId')->willReturn(777);

        $quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();

        $quoteItem->method('getId')->willReturn(1);
        $quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();

        $quoteItem->method('getId')->willReturn(1);
        $fakeOffer = $this->createMock(Offer::class);
        $fakeOffer->method('getId')->willReturn('offerX');
        $quoteItem->setData('offer', $fakeOffer);
        $quoteItem->setData('qty', 5);
        $quoteItem->setData('mirakl_shipping_type', 'NORMAL');

        $pluginPartial = $this->getMockBuilder(OfferCollectorPlugin::class)
            ->setConstructorArgs([
                $this->marketplaceConfig,
                $this->quoteItemResourceFactory,
                $this->offerFactory,
                $this->cache,
                $this->helperData
            ])
            ->onlyMethods(['getItemsWithOffer', 'insertLeadtimeToShip'])
            ->getMock();

        $pluginPartial->expects($this->once())
            ->method('getItemsWithOffer')
            ->with($quote)
            ->willReturn([$quoteItem]);

        $pluginPartial->expects($this->once())
            ->method('insertLeadtimeToShip')
            ->willReturn(null);

        $subject = $this->createMock(OfferCollector::class);
        $proceed = function () {
            $this->fail('aroundGetOffersWithQty should not call $proceed');
        };

        $returned = $pluginPartial->aroundGetOffersWithQty($subject, $proceed, $quote);

        $this->assertIsArray($returned);
        $this->assertCount(1, $returned);

        $tuple = $returned[0];
        $this->assertInstanceOf(OfferQuantityShippingTypeTuple::class, $tuple);
        $this->assertEquals('offerX', $tuple->getOfferId());
        $this->assertEquals(5, $tuple->getQuantity());
        $this->assertEquals('NORMAL', $tuple->getShippingTypeCode());
    }
}
