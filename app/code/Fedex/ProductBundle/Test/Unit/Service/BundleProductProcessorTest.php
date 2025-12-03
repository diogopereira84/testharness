<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Service;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\ProductBundle\Service\BundleProductProcessor;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BundleProductProcessorTest extends TestCase
{
    private SerializerInterface|MockObject $serializer;
    private UploadToQuoteViewModel|MockObject $uploadToQuoteViewModel;
    private FXORate|MockObject $fxoRateHelper;
    private FXORateQuote|MockObject $fxoRateQuote;
    private InstoreConfig|MockObject $instoreConfig;
    private OrderItemRepositoryInterface $orderItemRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private BundleProductProcessor $processor;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->uploadToQuoteViewModel = $this->createMock(UploadToQuoteViewModel::class);
        $this->fxoRateHelper = $this->createMock(FXORate::class);
        $this->fxoRateQuote = $this->createMock(FXORateQuote::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->orderItemRepository = $this->createMock(OrderItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->processor = new BundleProductProcessor(
            $this->serializer,
            $this->uploadToQuoteViewModel,
            $this->fxoRateHelper,
            $this->fxoRateQuote,
            $this->instoreConfig,
            $this->orderItemRepository,
            $this->searchCriteriaBuilder,
        );
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testProcessBundleItemsHappyPathEproCustomer(): void
    {
        $productData = [
            [
                'integratorProductReference' => 'SKU1',
                'instanceId' => 999
            ]
        ];

        $serialized = '{"external_prod":[{"integratorProductReference":"SKU1","instanceId":999,"fxoMenuId":"SKU1","isEdited":false,"isEditable":false}]}';

        $productMock = $this->createConfiguredMock(Product::class, [
            'getSku' => 'SKU1'
        ]);

        $childItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProduct', 'addOption', 'setData','save'])
            ->addMethods(['setSiType','setInstanceId'])
            ->getMock();
        $childItem->expects($this->once())
            ->method('save');
        $childItem->method('getProduct')->willReturn($productMock);
        $childItem->setProductId(123); // ensures real getProductId() works

        $childItem->expects($this->once())
            ->method('addOption')
            ->with($this->arrayHasKey('value'));
        $childItem->expects($this->once())
            ->method('setSiType')
            ->with('SITYPE');
        $childItem->expects($this->once())
            ->method('setInstanceId')
            ->with(999);

        $parentItem = $this->createConfiguredMock(Item::class, [
            'getChildren' => [$childItem]
        ]);

        $quote = $this->createMock(Quote::class);

        $this->serializer->method('serialize')->willReturn($serialized);
        $this->uploadToQuoteViewModel->method('getSiType')->willReturn('SITYPE');
        $this->fxoRateHelper->method('isEproCustomer')->willReturn(true);
        $this->fxoRateHelper->method('getFXORate')->with($quote)->willReturn(['rate' => 10]);

        $result = $this->processor->processBundleItems(json_encode($productData), $parentItem, $quote);

        $this->assertSame(['rate' => 10], $result);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testProcessBundleItemsNonEproCustomer(): void
    {
        $productData = [];
        $parentItem = $this->createConfiguredMock(Item::class, ['getChildren' => []]);
        $quote = $this->createMock(Quote::class);

        $this->fxoRateHelper->method('isEproCustomer')->willReturn(false);
        $this->fxoRateQuote->method('getFXORateQuote')->willReturn(['foo' => 'bar']);

        $result = $this->processor->processBundleItems($productData, $parentItem, $quote);
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testProcessBundleItemsHandlesGraphQlExceptionWithoutThrow(): void
    {
        $productData = [];
        $parentItem = $this->createConfiguredMock(Item::class, ['getChildren' => []]);
        $quote = $this->createConfiguredMock(Quote::class, ['getId' => 55]);

        $this->fxoRateHelper->method('isEproCustomer')->willReturn(false);

        $this->fxoRateQuote->method('getFXORateQuote')->willThrowException(new GraphQlFujitsuResponseException(__('Error')));
        $this->instoreConfig->method('isEnabledThrowExceptionOnGraphqlRequests')->willReturn(false);

        $result = $this->processor->processBundleItems($productData, $parentItem, $quote);
        $this->assertNull($result);
    }


    public function testProcessBundleItemsHandlesGraphQlExceptionWithThrow(): void
    {
        $this->expectException(LocalizedException::class);

        $productData = [];
        $parentItem = $this->createConfiguredMock(Item::class, ['getChildren' => []]);
        $quote = $this->createConfiguredMock(Quote::class, ['getId' => 55]);

        $this->fxoRateHelper->method('isEproCustomer')->willReturn(false);

        $this->fxoRateQuote->method('getFXORateQuote')->willThrowException(new GraphQlFujitsuResponseException(__('Error')));
        $this->instoreConfig->method('isEnabledThrowExceptionOnGraphqlRequests')->willReturn(true);

        $this->processor->processBundleItems($productData, $parentItem, $quote);
    }

    public function testReorderHasBundleProductsReturnsTrueWhenBundleProductExists(): void
    {
        $reorderItems = [
            1 => ['order_id' => 100],
            2 => ['order_id' => 101]
        ];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->atMost(3))
            ->method('addFilter')
            ->withConsecutive(
                [OrderItemInterface::ITEM_ID, [1,2], 'in'],
                [OrderItemInterface::ORDER_ID, [100, 101], 'in'],
                [OrderItemInterface::PRODUCT_TYPE, Type::TYPE_BUNDLE]
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteria);

        $orderItemSearchResultInterface = $this->createMock(OrderItemSearchResultInterface::class);
        $orderItemSearchResultInterface->expects($this->once())
            ->method('getItems')
            ->willReturn([1]);
        $this->orderItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($orderItemSearchResultInterface);

        $result = $this->processor->reorderHasBundleProducts($reorderItems);
        $this->assertTrue($result);
    }

    public function testReorderHasBundleProductsReturnsFalseWhenNoBundleProductExists(): void
    {
        $reorderItems = [
            1 => ['order_id' => 100],
            2 => ['order_id' => 101]
        ];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->atMost(3))
            ->method('addFilter')
            ->withConsecutive(
                [OrderItemInterface::ITEM_ID, [1,2], 'in'],
                [OrderItemInterface::ORDER_ID, [100, 101], 'in'],
                [OrderItemInterface::PRODUCT_TYPE, Type::TYPE_BUNDLE]
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteria);

        $orderItemSearchResultInterface = $this->createMock(OrderItemSearchResultInterface::class);
        $orderItemSearchResultInterface->expects($this->once())
            ->method('getItems')
            ->willReturn([]);
        $this->orderItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($orderItemSearchResultInterface);

        $result = $this->processor->reorderHasBundleProducts($reorderItems);
        $this->assertFalse($result);
    }

    public function testHandleBundleItemReorderSetsQtyAndInstanceId(): void
    {
        $orderItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getQtyOrdered', 'getProductOptionByCode'])
            ->getMock();
        $orderItemChild->method('getProductId')->willReturn(123);
        $orderItemChild->method('getQtyOrdered')->willReturn(5);
        $orderItemChild->method('getProductOptionByCode')->willReturn([
            'external_prod' => [
                ['instanceId' => 'abc']
            ]
        ]);

        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChildrenItems'])
            ->getMock();
        $orderItem->method('getChildrenItems')->willReturn([$orderItemChild]);

        $quoteItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'addOption'])
            ->addMethods(['getProductId', 'setInstanceId'])
            ->getMock();
        $quoteItemChild->method('getProductId')->willReturn(123);
        $quoteItemChild->expects($this->once())->method('setData')->with('qty', 5);
        $quoteItemChild->expects($this->once())->method('addOption')->with($this->arrayHasKey('value'));
        $quoteItemChild->expects($this->once())->method('setInstanceId')->with('abc');

        $productAdded = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren'])
            ->getMock();
        $productAdded->method('getChildren')->willReturn([$quoteItemChild]);

        $this->serializer->method('serialize')->willReturn('SERIALIZED');
        $this->uploadToQuoteViewModel->method('isUploadToQuoteEnable')->willReturn(false);

        $this->processor->handleBundleItemReorder($orderItem, $productAdded);
    }

    public function testHandleBundleItemReorderUploadToQuoteEnabledResetsSI(): void
    {
        $orderItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getQtyOrdered', 'getProductOptionByCode'])
            ->getMock();
        $orderItemChild->method('getProductId')->willReturn(123);
        $orderItemChild->method('getQtyOrdered')->willReturn(5);
        $orderItemChild->method('getProductOptionByCode')->willReturn([
            'external_prod' => [
                ['instanceId' => 'abc']
            ]
        ]);

        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChildrenItems'])
            ->getMock();
        $orderItem->method('getChildrenItems')->willReturn([$orderItemChild]);

        $quoteItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'addOption'])
            ->addMethods(['getProductId', 'setInstanceId'])
            ->getMock();
        $quoteItemChild->method('getProductId')->willReturn(123);
        $quoteItemChild->expects($this->once())->method('setData')->with('qty', 5);
        $quoteItemChild->expects($this->once())->method('addOption')->with($this->arrayHasKey('value'));
        $quoteItemChild->expects($this->once())->method('setInstanceId')->with('abc');

        $productAdded = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren'])
            ->getMock();
        $productAdded->method('getChildren')->willReturn([$quoteItemChild]);

        $this->serializer->method('serialize')->willReturn('SERIALIZED');
        $this->uploadToQuoteViewModel->method('isUploadToQuoteEnable')->willReturn(true);
        $this->uploadToQuoteViewModel->method('resetCustomerSI')->willReturn([
            'external_prod' => [
                ['instanceId' => 'abc']
            ]
        ]);

        $this->processor->handleBundleItemReorder($orderItem, $productAdded);
    }

    public function testHandleBundleItemReorderNoInstanceId(): void
    {
        $orderItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getQtyOrdered', 'getProductOptionByCode'])
            ->getMock();
        $orderItemChild->method('getProductId')->willReturn(123);
        $orderItemChild->method('getQtyOrdered')->willReturn(5);
        $orderItemChild->method('getProductOptionByCode')->willReturn([
            'external_prod' => [
                []
            ]
        ]);

        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChildrenItems'])
            ->getMock();
        $orderItem->method('getChildrenItems')->willReturn([$orderItemChild]);

        $quoteItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'addOption'])
            ->addMethods(['getProductId', 'setInstanceId'])
            ->getMock();
        $quoteItemChild->method('getProductId')->willReturn(123);
        $quoteItemChild->expects($this->once())->method('setData')->with('qty', 5);
        $quoteItemChild->expects($this->once())->method('addOption')->with($this->arrayHasKey('value'));
        $quoteItemChild->expects($this->never())->method('setInstanceId');

        $productAdded = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren'])
            ->getMock();
        $productAdded->method('getChildren')->willReturn([$quoteItemChild]);

        $this->serializer->method('serialize')->willReturn('SERIALIZED');
        $this->uploadToQuoteViewModel->method('isUploadToQuoteEnable')->willReturn(false);

        $this->processor->handleBundleItemReorder($orderItem, $productAdded);
    }

    public function testHandleBundleItemReorderNoChildren(): void
    {
        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChildrenItems'])
            ->getMock();
        $orderItem->method('getChildrenItems')->willReturn([]);

        $productAdded = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren'])
            ->getMock();
        $productAdded->method('getChildren')->willReturn([]);

        $this->processor->handleBundleItemReorder($orderItem, $productAdded);
        $this->assertTrue(true); // Should not throw
    }

    public function testHandleBundleItemReorderInfoBuyRequestMissing(): void
    {
        $orderItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getQtyOrdered', 'getProductOptionByCode'])
            ->getMock();
        $orderItemChild->method('getProductId')->willReturn(123);
        $orderItemChild->method('getQtyOrdered')->willReturn(5);
        $orderItemChild->method('getProductOptionByCode')->willReturn([]);

        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChildrenItems'])
            ->getMock();
        $orderItem->method('getChildrenItems')->willReturn([$orderItemChild]);

        $quoteItemChild = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'addOption'])
            ->addMethods(['getProductId', 'setInstanceId'])
            ->getMock();
        $quoteItemChild->method('getProductId')->willReturn(123);
        $quoteItemChild->expects($this->once())->method('setData')->with('qty', 5);
        $quoteItemChild->expects($this->once())->method('addOption')->with($this->arrayHasKey('value'));
        $quoteItemChild->expects($this->never())->method('setInstanceId');

        $productAdded = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChildren'])
            ->getMock();
        $productAdded->method('getChildren')->willReturn([$quoteItemChild]);

        $this->serializer->method('serialize')->willReturn('SERIALIZED');
        $this->uploadToQuoteViewModel->method('isUploadToQuoteEnable')->willReturn(false);

        $this->processor->handleBundleItemReorder($orderItem, $productAdded);
    }

    public function testMapProductsBySkuForQuoteApprovalWithNullReturnsEmptyArray(): void
    {
        $result = $this->processor->mapProductsBySkuForQuoteApproval(null);
        $this->assertSame([], $result);
    }

    public function testMapProductsBySkuForQuoteApprovalWithNonArrayNonStringReturnsEmptyArray(): void
    {
        $result = $this->processor->mapProductsBySkuForQuoteApproval('123');
        $this->assertSame([], $result);
    }

    public function testMapProductsBySkuForQuoteApprovalWithInvalidJsonStringReturnsEmptyArray(): void
    {
        $result = $this->processor->mapProductsBySkuForQuoteApproval('{invalid json');
        $this->assertSame([], $result);
    }

    public function testMapProductsBySkuForQuoteApprovalWithValidJsonButNotArrayReturnsEmptyArray(): void
    {
        $result = $this->processor->mapProductsBySkuForQuoteApproval(json_encode('not an array'));
        $this->assertSame([], $result);
    }

    public function testMapProductsBySkuForQuoteApprovalWithArrayMissingProductConfig(): void
    {
        $input = [
            ['foo' => 'bar'],
            ['productConfig' => []],
            ['productConfig' => ['integratorProductReference' => null]],
        ];
        $result = $this->processor->mapProductsBySkuForQuoteApproval($input);
        $this->assertSame([], $result);
    }

    public function testMapProductsBySkuForQuoteApprovalWithArraySomeProductsMissingReference(): void
    {
        $input = [
            ['productConfig' => ['integratorProductReference' => 'SKU1']],
            ['productConfig' => []],
            ['productConfig' => ['integratorProductReference' => '']],
            ['productConfig' => ['integratorProductReference' => 'SKU2']],
        ];
        $result = $this->processor->mapProductsBySkuForQuoteApproval($input);
        $this->assertArrayHasKey('SKU1', $result);
        $this->assertArrayHasKey('SKU2', $result);
        $this->assertCount(2, $result);
        $this->assertSame(json_encode($input[0]), $result['SKU1']);
        $this->assertSame(json_encode($input[3]), $result['SKU2']);
    }

    public function testMapProductsBySkuForQuoteApprovalWithArrayAllProductsHaveReference(): void
    {
        $input = [
            ['productConfig' => ['integratorProductReference' => 'SKU1']],
            ['productConfig' => ['integratorProductReference' => 'SKU2']],
        ];
        $result = $this->processor->mapProductsBySkuForQuoteApproval($input);
        $this->assertArrayHasKey('SKU1', $result);
        $this->assertArrayHasKey('SKU2', $result);
        $this->assertCount(2, $result);
        $this->assertSame(json_encode($input[0]), $result['SKU1']);
        $this->assertSame(json_encode($input[1]), $result['SKU2']);
    }

    public function testMapProductsBySkuForQuoteApprovalWithJsonStringAllProductsHaveReference(): void
    {
        $input = [
            ['productConfig' => ['integratorProductReference' => 'SKU1']],
            ['productConfig' => ['integratorProductReference' => 'SKU2']],
        ];
        $jsonInput = json_encode($input);
        $result = $this->processor->mapProductsBySkuForQuoteApproval($jsonInput);
        $this->assertArrayHasKey('SKU1', $result);
        $this->assertArrayHasKey('SKU2', $result);
        $this->assertCount(2, $result);
        $this->assertSame(json_encode($input[0]), $result['SKU1']);
        $this->assertSame(json_encode($input[1]), $result['SKU2']);
    }
}
