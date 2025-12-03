<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Helper;

use Fedex\MarketplaceProduct\Helper\Quote;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote\Item;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\FrontendDemo\Helper\Config;
use Mirakl\FrontendDemo\Model\Quote\Loader as QuoteLoader;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class QuoteTest extends TestCase
{
    /**
     * @var MockObject|CollectionFactory
     */
    private $category;

    /**
     * @var MockObject|Item
     */
    private $itemMock;

    /**
     * @var MockObject|Quote
     */
    private $quote;
    private $marketplaceCheckoutHelper;

    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);
        $config = $this->createMock(Config::class);
        $quoteLoader = $this->createMock(QuoteLoader::class);
        $quoteSynchronizer = $this->createMock(QuoteSynchronizer::class);
        $quoteUpdater = $this->createMock(QuoteUpdater::class);
        $quoteHelper = $this->createMock(QuoteHelper::class);
        $offerCollector = $this->createMock(OfferCollector::class);
        $marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->category = $this->getMockBuilder(CollectionFactory::class)
            ->onlyMethods(['create'])
            ->addMethods(['addAttributeToSelect', 'setPageSize', 'addAttributeToFilter', 'getFirstItem', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->onlyMethods(['getName', 'getProduct', 'getItemId', 'getData'])
            ->addMethods(['getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemMock->method('getAdditionalData')->willReturn(json_encode([
            'quantity' => 2,
            'unit_price' => 10,
            'total' => 20,
            'marketplace_name' => 'nav_name'
        ]));
        $this->itemMock->method('getName')->willReturn('Sample Item');
        $this->itemMock->method('getItemId')->willReturn(1);
        $this->itemMock->method('getData')->willReturnMap([
            ['mirakl_shop_id', null, 'shop123'],
        ]);

        $this->quote = new Quote(
            $context,
            $config,
            $quoteLoader,
            $quoteSynchronizer,
            $quoteUpdater,
            $quoteHelper,
            $offerCollector,
            $this->category,
            $marketplaceCheckoutHelper
        );
    }

    /**
     * @covers \Fedex\MarketplaceProduct\Helper\Quote::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(Quote::class, $this->quote);
    }

    /**
     * @covers \Fedex\MarketplaceProduct\Helper\Quote::getMarketplaceRateQuoteRequest
     */
    public function testGetMarketplaceRateQuoteRequest(): void
    {
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $map = [
            ['map_sku', null, '39964'],
            ['product_id', null, 1]
        ];
        $productMock->expects($this->any())
             ->method('getData')
             ->willReturnMap($map);
        $this->itemMock->method('getProduct')->willReturn($productMock);

        $result = $this->quote->getMarketplaceRateQuoteRequest($this->itemMock);

        $expected = [
            "id" => 1,
            "qty" => 2,
            "name" => 'Sample Item',
            "version" => "1",
            "instanceId" => 1,
            "vendorReference" => [
                "vendorId" => 'shop123',
                "vendorProductName" => 'Sample Item',
                "vendorProductDesc" => 'Sample Item',
            ],
            "externalSkus" => [
                [
                    "skuDescription" => 'nav_name',
                    "skuRef" => '39964',
                    "code" => '39964',
                    "unitPrice" => 10,
                    "price" => 20,
                    "qty" => 2
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

     /**
     * @covers \Fedex\MarketplaceProduct\Helper\Quote::getMarketplaceRateQuoteRequest
     */
    public function testGetMarketplaceRateQuoteRequestWhenProductMapSkuEmpty(): void
    {
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->onlyMethods(['getData', 'getCategoryIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $map = [
            ['map_sku', null, null],
            ['product_id', null, 1]
        ];
        $productMock->expects($this->any())
             ->method('getData')
             ->willReturnMap($map);
        $productMock->method('getCategoryIds')->willReturn([1]);

        $this->itemMock->method('getProduct')->willReturn($productMock);

        $result = $this->quote->getMarketplaceRateQuoteRequest($this->itemMock);

        $expected = [
            "id" => 1,
            "qty" => 2,
            "name" => 'Sample Item',
            "version" => "1",
            "instanceId" => 1,
            "vendorReference" => [
                "vendorId" => 'shop123',
                "vendorProductName" => 'Sample Item',
                "vendorProductDesc" => 'Sample Item',
            ],
            "externalSkus" => [
                [
                    "skuDescription" => 'nav_name',
                    "skuRef" => null,
                    "code" => null,
                    "unitPrice" => 10,
                    "price" => 20,
                    "qty" => 2
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
