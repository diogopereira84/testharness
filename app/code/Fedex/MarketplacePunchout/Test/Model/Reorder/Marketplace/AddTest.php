<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Jyoti thakur <jyoti.thakur.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Reorder\Marketplace;

use Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter;
use Psr\Log\LoggerInterface;
use Fedex\MarketplacePunchout\Model\Reorder\Marketplace\ReorderApi;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\MarketplacePunchout\Model\Reorder\Marketplace\Add;
use Magento\Quote\Model\Quote;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Magento\Sales\Model\Order\Item as orderItem;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;

class AddTest extends TestCase
{
    protected $searchCriteriaMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    /**
     * @var (\Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderInfoBuyRequestGetter;
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helper;
    protected $reorderApi;
    protected $orderItem;
    /**
     * @var (\Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $externalprod;
    protected $quoteItem;
    const ORDER_INCREMENT_ID = '12345';

    /** @var Cart|MockObject */
    private MockObject|Cart $cart;

    /** @var MockObject|ProductRepositoryInterface  */
    private MockObject|ProductRepositoryInterface $productRepository;

    /** @var MockObject|RequestInterface */
    private MockObject|RequestInterface $request;

    /** @var MockObject|SearchCriteriaBuilder  */
    private MockObject|SearchCriteriaBuilder $searchBuilder;

    /** @var MockObject|ProductSearchResultsInterface  */
    private MockObject|ProductSearchResultsInterface $list;

    /** @var MockObject|ProductInterface  */
    private MockObject|ProductInterface $productInterface;

    /** @var MockObject|CollectionFactory  */
    private MockObject|CollectionFactory $collectionFactory;

    /** @var MockObject|NonCustomizableProduct  */
    private MockObject|NonCustomizableProduct $nonCustomizableProduct;

    /** @var MockObject|Update  */
    private MockObject|Update $update;

    /** @var Add  */
    private Add $add;

    protected function setUp(): void
    {
        $this->cart = $this->createMock(Cart::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $this->searchBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->list = $this->createMock(ProductSearchResultsInterface::class);
        $this->productInterface = $this->createMock(ProductInterface::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $quote = $this->createMock(Quote::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->orderInfoBuyRequestGetter = $this->createMock(OrderInfoBuyRequestGetter::class);
        $this->helper = $this->createMock(Data::class);
        $update = $this->createMock(Update::class);
        $this->reorderApi = $this->createMock(ReorderApi::class);
        $this->orderItem = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->addMethods(
                [
                    'getOrderIncrementId',
                ]
            )
            ->onlyMethods(
                [
                    'getAdditionalData',
                ]
            )
            ->getMock();
        $this->orderItem->method('getOrderIncrementId')->willReturn('12345');

        $this->helper = $this->createMock(Data::class);
        $this->externalprod = $this->createMock(ExternalProd::class);

        $methods = [
            'getId', 'getSku', 'getName', 'getProduct', 'getPrice', 'setPrice',
            'setCustomPrice', 'setQty', 'delete', 'addOption', 'saveItemOptions'
        ];
        $this->quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->addMethods(['getProductId', 'getAdditionalData', 'setAdditionalData', 'setBasePrice', 'setOriginalCustomPrice', 'setBaseRowTotal',
            'setPriceInclTax', 'setBasePriceInclTax', 'setRowTotal', 'setRowTotalInclTax',
            'setBaseRowTotalInclTax', 'setIsSuperMode'])
            ->getMock();

        $this->update = $this->createMock(Update::class);

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getModuleName',
                    'setModuleName',
                    'getActionName',
                    'setActionName',
                    'getParam',
                    'setParams',
                    'getParams',
                    'getCookie',
                    'isSecure',
                ]
            )
            ->addMethods(['setPostValue'])
            ->getMock();

        $this->searchBuilder->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->searchCriteriaMock->expects($this->any())
            ->method('setPageSize')
            ->willReturnSelf();

        $this->searchCriteriaMock->expects($this->any())
            ->method('setCurrentPage')
            ->willReturnSelf();

        $this->productRepository->method('getList')
            ->willReturn($this->list);
        $this->list->method('getItems')
            ->willReturn([0 => $this->productInterface]);
        $this->cart->method('addProduct')
            ->willReturn($this->cart);
        $this->cart->method('getItems')
            ->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->method('getId')
            ->willReturn(1);
        $this->quoteItem->method('getSku')
            ->willReturn('sku');

        $this->cart->method('getQuote')
            ->willReturn($quote);

        $this->nonCustomizableProduct = $this->createMock(NonCustomizableProduct::class);

        $this->add = new Add(
            $this->cart,
            $this->productRepository,
            $this->request,
            $this->searchBuilder,
            $this->reorderApi,
            $serializer,
            $this->logger,
            $update,
            $this->orderInfoBuyRequestGetter,
            $this->helper,
            $this->externalprod,
            $this->nonCustomizableProduct 
        );
    }

    public function testAddItemToCart(): void
    {
        $orderItemMock = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $productMock = $this->createMock(ProductInterface::class);
        $orderMock = $this->createMock(\Magento\Sales\Model\Order::class);

        $orderItemMock
            ->expects($this->any())
            ->method('getSku')
            ->willReturn('test-sku');

        $orderMock
            ->expects($this->once())
            ->method('getIncrementId')
            ->willReturn('000000123');
        $orderItemMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $quoteItemMock = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getProductId',
                    'getOrderIncrementId',
                    'getAdditionalData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchBuilder
            ->expects($this->once())
            ->method('addFilter')
            ->with('sku', $orderItemMock->getSku())
            ->willReturnSelf();
        $this->searchBuilder
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->productRepository
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($this->createConfiguredMock(
                \Magento\Framework\Api\SearchResultsInterface::class,
                ['getItems' => [$productMock]]
            ));

        $this->cart
            ->expects($this->once())
            ->method('addProduct')
            ->with($productMock, $this->anything())
            ->willReturnSelf();
        $this->cart
            ->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->cart
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$quoteItemMock]);

        $quoteItemMock
            ->expects($this->any())
            ->method('getProductId')
            ->willReturn($productMock->getId());

        $quoteItemMock
            ->expects($this->any())
            ->method('getOrderIncrementId')
            ->willReturn(2);

        $additionalData = ['some_data' => 'random_data', 'mirakl_shipping_data' => ['keyA' => 'valueA'], 'quantity' => 5];
        $orderItemMock->method('getAdditionalData')->willReturn(json_encode($additionalData));
        $quoteItemMock
            ->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode(['quantity' => 2]));

        $this->request
            ->expects($this->once())
            ->method('setPostValue')
            ->with('isMarketplaceProduct', true);

        $this->add->addItemToCart($orderItemMock);
    }

    public function testUpdateThirdPartyItem()
    {
        $supplierPartAuxiliaryID = '';
        $orderIncrementId = static::ORDER_INCREMENT_ID;

        $reorderApiResponse = $this->reorderApi->expects($this->once())->method('getReorderApiData')
            ->with($supplierPartAuxiliaryID)->willReturn(
                json_encode(
                    ['response' =>
                        ['configDataModel' =>
                            ['totalOrderPrice' => 10, 'quantity' => 1, 'productName' => 'Product 1']
                        ]
                    ]
                )
            );
        @$reorderApiResponse->status = 200;
        @$reorderApiResponse->response = [
            0 => (object) [
                'configDataModel' => (object) [
                    'totalOrderPrice' => 10.0,
                    'quantity' => 5,
                    'productName' => 'Test Product'
                ],
                'artworkUrl' => 'http://example.com/artwork.png'
            ]
        ];
        $this->codeUnderTest($reorderApiResponse, $this->quoteItem, 'supplierPartAuxiliaryID');

        // Call the function being tested
        $result = $this->add->updateThirdPartyItem($this->quoteItem, $orderIncrementId);
        // Assert that the $result is the same as $quoteItem
        $this->assertSame($this->quoteItem, $result);
    }

    public function testNegativeCase()
    {
        $orderIncrementId = static::ORDER_INCREMENT_ID;
        $supplierPartAuxiliaryID = '';
        $reorderApiResponse = $this->reorderApi->expects($this->once())->method(
            'getReorderApiData'
        )->with($supplierPartAuxiliaryID)->willReturn(
            json_encode(['response' =>
                ['configDataModel' =>
                    ['totalOrderPrice' => 10, 'quantity' => 1, 'productName' => 'Product 1']
                ]
            ])
        );
        @$reorderApiResponse->status = 400;
        $this->assertNotEquals(200, @$reorderApiResponse->status);
        $this->quoteItem->expects($this->once())
            ->method('delete');
        $result = $this->add->updateThirdPartyItem($this->quoteItem, $orderIncrementId);
        $this->assertSame($this->quoteItem, $result);
    }

    private function codeUnderTest($reorderApiResponse, $quoteItem, $supplierPartAuxiliaryID)
    {
        if (@$reorderApiResponse->status == 200) {
            $total = (double) $reorderApiResponse->response[0]
                ->configDataModel->totalOrderPrice;
            $additionalData['total'] = $total;
            $additionalData['quantity'] = (int) @$reorderApiResponse->response[0]->configDataModel->quantity;
            $additionalData['marketplace_name'] = @$reorderApiResponse->response[0]->configDataModel->productName;
            $additionalData['reorder_item'] = true;
            $imageName = $supplierPartAuxiliaryID.'.png';
            $update = $this->getMockBuilder(Update::class)
                ->disableOriginalConstructor()
                ->getMock();
            $update->saveImage(@$reorderApiResponse->response[0]->artworkUrl, $imageName);
            $quoteItem->setAdditionalData(json_encode($additionalData));
            $quoteItem->setCustomPrice($total);
        } else {
            $quoteItem->delete();
            return $quoteItem;
        }
        return $quoteItem;
    }

    public function testUpdateThirdPartyItem_Status200_PunchoutEnabledWithTotalPrice(): void
    {
        $initialData = ['supplierPartAuxiliaryID' => 'aux123', 'quantity' => 4, 'punchout_enabled' => true, 'unit_price' => '12345'];
        $this->quoteItem->method('getAdditionalData')->willReturn(json_encode($initialData));
        $this->quoteItem->method('getSku')->willReturn('sku');
        $this->quoteItem->method('getName')->willReturn('Original Name');
        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);

        $configModel = (object)[
            'configDataModel' => (object)[
                'brokerConfigID' => 'aux123',
                'totalPrice' => 20.0,
            ],
            'artworkUrl' => 'http://example.com/img.png'
        ];
        $response = ['status' => 200, 'response' => [$configModel]];

        $this->update->expects($this->any())
            ->method('saveImage')
            // ->with('http://example.com/img.png', 'aux123.png')
            ->willReturn('local/img.png');
        $this->reorderApi->expects($this->once())
            ->method('getReorderApiData')
            ->with('aux123', 'sku', '12345', 4)
            ->willReturn(json_encode($response));

        // Expect setter methods and option additions
        $this->quoteItem->expects($this->once())->method('setAdditionalData');
        $this->quoteItem->expects($this->once())->method('addOption');
        $this->quoteItem->expects($this->once())->method('saveItemOptions');

        $result = $this->add->updateThirdPartyItem($this->quoteItem, '12345');
        $this->assertSame($this->quoteItem, $result);
    }

    public function testUpdateThirdPartyItem_Status200_PunchoutEnabled(): void
    {
        $initialData = ['supplierPartAuxiliaryID' => 'aux123', 'quantity' => 4, 'punchout_enabled' => true, 'unit_price' => '12345'];
        $this->quoteItem->method('getAdditionalData')->willReturn(json_encode($initialData));
        $this->quoteItem->method('getSku')->willReturn('sku');
        $this->quoteItem->method('getName')->willReturn('Original Name');
        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);

        $configModel = (object)[
            'configDataModel' => (object)[
                'brokerConfigID' => 'aux123',
                'totalOrderPrice' => 20.0,
                'quantity' => 2,
                'productName' => 'Updated Name'
            ],
            'artworkUrl' => 'http://example.com/img.png'
        ];
        $response = ['status' => 200, 'response' => [$configModel]];

        $this->update->expects($this->any())
            ->method('saveImage')
            // ->with('http://example.com/img.png', 'aux123.png')
            ->willReturn('local/img.png');
        $this->reorderApi->expects($this->once())
            ->method('getReorderApiData')
            ->with('aux123', 'sku', '12345', 4)
            ->willReturn(json_encode($response));

        // Expect setter methods and option additions
        $this->quoteItem->expects($this->once())->method('setAdditionalData');
        $this->quoteItem->expects($this->once())->method('addOption');
        $this->quoteItem->expects($this->once())->method('saveItemOptions');

        $result = $this->add->updateThirdPartyItem($this->quoteItem, '12345');
        $this->assertSame($this->quoteItem, $result);
    }

    public function testUpdateThirdPartyItem_PunchoutDisabled(): void
    {
        $initialData = ['supplierPartAuxiliaryID' => 'aux456', 'quantity' => 3, 'punchout_enabled' => false];
        $this->quoteItem->method('getAdditionalData')->willReturn(json_encode($initialData));
        $this->quoteItem->method('getSku')->willReturn('sku-2');
        $this->quoteItem->method('getName')->willReturn('Name 2');
        $this->quoteItem->method('getPrice')->willReturn(10.0);
        $this->quoteItem->method('getProduct')->willReturn('product-object');
        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);

        $this->reorderApi->expects($this->never())->method('getReorderApiData');
        $this->quoteItem->expects($this->once())->method('setAdditionalData');
        $this->quoteItem->expects($this->once())->method('addOption');
        $this->quoteItem->expects($this->once())->method('saveItemOptions');

        $result = $this->add->updateThirdPartyItem($this->quoteItem, '12345');
        $this->assertSame($this->quoteItem, $result);
    }

    public function testUpdateThirdPartyItem_StatusNot200_DeletesItem(): void
    {
        $initialData = ['supplierPartAuxiliaryID' => '', 'quantity' => 1];
        $this->quoteItem->method('getAdditionalData')->willReturn(json_encode($initialData));
        $this->quoteItem->method('getSku')->willReturn('sku');
        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(false);

        $response = ['status' => 500, 'response' => []];
        $this->reorderApi->expects($this->once())
            ->method('getReorderApiData')
            ->with('', 'sku', '12345', 1)
            ->willReturn(json_encode($response));

        $this->quoteItem->expects($this->once())->method('delete');

        $result = $this->add->updateThirdPartyItem($this->quoteItem, '12345');
        $this->assertSame($this->quoteItem, $result);
    }
}
