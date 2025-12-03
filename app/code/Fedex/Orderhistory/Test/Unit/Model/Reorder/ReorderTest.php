<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\Orderhistory\Test\Unit\Model\Reorder;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\OrderRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\Api\SearchCriteriaInterface;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Fedex\Orderhistory\Model\Reorder\Reorder;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Allows customer quickly to reorder previously added products and put them to the Cart
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReorderTest extends TestCase
{
    protected $product;
    protected $productInterface;
    protected $productCollection;
    protected $orderItemMock;
    protected $customer;
    protected $cart;
    protected $quoteItem;
    protected $storeMock;
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuoteMock;
    protected $toggleConfig;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $searchCriteria;
    protected $orderSearchResult;
    protected $order;
    protected $orderItemSearchResult;
    /**
     * @var (\Magento\Sales\Api\Data\OrderItemInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderItem;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $reorderModelMock;
    /**
     * @var \Throwable|null
     */
    private $exception;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepositoryInterface;

    /**
     * @var CustomerCartResolver|MockObject
     */
    private $customerCartProvider;

    /**
     * @var ProductCollectionFactory|MockObject
     */
    private $productCollectionFactory;

    /**
     * @var OrderItemRepositoryInterface|MockObject
     */
    private $orderItemRepository;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerInterface;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerInterface;

    /**
     * @var FXORate|MockObject
     */
    protected $fxoRateHelper;

    /**
     * @var OrderHistoryEnhacement|MockObject
     */
    protected $orderHistoryEnhacement;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->cartRepositoryInterface = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["get", "save"])
            ->getMockForAbstractClass();

        $this->customerCartProvider = $this->getMockBuilder(CustomerCartResolver::class)
            ->setMethods(['resolve'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->product = $this->createMock(Product::class);

        $this->productInterface = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getId','addCustomOption', 'getData', 'getAttributeSetId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->setMethods([
                'setStore',
                'addIdFilter',
                'addStoreFilter',
                'getItems',
                'getFirstItem',
                'addFilter',
                'joinAttribute',
                'addAttributeToSelect',
                'addOptionsToResult'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemRepository = $this->getMockBuilder(OrderItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["get", "getList"])
            ->getMockForAbstractClass();

        $this->orderItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(["getParentItem", "getProductId", "getId", "getProductOptionByCode", "getQtyOrdered", "getItemId"])
            ->getMock();

        $this->serializerInterface = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->cart = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProduct', 'getId', 'getAllVisibleItems', 'deleteItem', 'hasProductId'])
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getOptionByCode', 'getCustomPrice', 'removeOption', 'save', 'getProductId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["getStore"])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->orderHistoryEnhacement = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->setMethods(['getProductCustomAttributeValue', 'getProductAttributeSetName', 'loadProductObj'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRateHelper = $this->getMockBuilder(FXORate::class)
            ->setMethods(['getFXORate', 'isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRateQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository = $this->getMockBuilder(OrderRepository::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->setMethods(['getlist'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();;
        $this->orderSearchResult = $this->getMockBuilder(OrderSearchResultInterface::class)
            ->setMethods(['getItems'])
            ->getMockForAbstractClass();
        $this->order = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getEntityId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderItemSearchResult = $this->getMockBuilder(OrderItemSearchResultInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderItem = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderItemInterface::class)
            ->setMethods(['getItemId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);

        $this->reorderModelMock = $this->objectManager->getObject(
            Reorder::class,
            [
                'logger' => $this->logger,
                'cartRepository' => $this->cartRepositoryInterface,
                'customerCartProvider' => $this->customerCartProvider,
                'productCollectionFactory' => $this->productCollectionFactory,
                'orderItemRepository' => $this->orderItemRepository,
                'serializerInterface' => $this->serializerInterface,
                'storeManagerInterface' => $this->storeManagerInterface,
                'customerSession' => $this->customerSession,
                'orderHistoryEnhacement' => $this->orderHistoryEnhacement,
                'fxoRateQuote' => $this->fxoRateQuoteMock,
                'toggleConfig' => $this->toggleConfig,
                'orderRepository' => $this->orderRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }
    public function testExecute()
    {
        $reorderItems = [
            '8781' => [
                'order_id' => 7626,
                'product_id' => 578,
                'item_id' => 8781
            ]
        ];

        $productId = 578;
        $this->storeManagerInterface->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getId')->willReturn('1');
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn(123);
        $this->customerCartProvider->expects($this->any())->method('resolve')->with(123)->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getId')->willReturn(2222);
        $this->cartRepositoryInterface->expects($this->any())->method('get')->with(2222)->willReturn($this->cart);
        $this->cart->expects($this->any())->method('hasProductId')->with($productId)->willReturn(false);
        $this->orderItemRepository->expects($this->any())->method('get')->with(8781)->willReturn($this->orderItemMock);
        $this->orderItemMock->expects($this->any())->method('getParentItem')->willReturn(null);
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn($productId);
        $this->orderItemMock->expects($this->any())->method('getId')->willReturn(8781);
        $this->productCollectionFactory->method('create')->willReturn($this->productCollection);
        $this->productCollection->method('getItems')->willReturn([$this->product]);
        $this->productCollection->method('setStore')->willReturnSelf();
        $this->productCollection->method('addIdFilter')->willReturnSelf();
        $this->productCollection->method('addStoreFilter')->willReturnSelf();
        $this->productCollection->method('joinAttribute')->willReturnSelf();
        $this->productCollection->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollection->method('addOptionsToResult')->willReturnSelf();
        $this->productCollection->method('getFirstItem')->willReturn($this->product);
        $this->cart->expects($this->any())->method('getId')->willReturn(2222);
        $this->cartRepositoryInterface->expects($this->exactly(2))->method('save')->with($this->cart)->willReturnSelf();
        $this->cartRepositoryInterface->expects($this->any())->method('get')->with(2222)->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getCustomPrice')->willReturn('');
        $this->quoteItem->expects($this->any())->method('getProductId')->willReturn($productId);
        $this->cart->expects($this->any())->method('deleteItem')->with($this->quoteItem)->willReturnSelf();
        $this->testValidateAttributeSetName();
        $this->quoteItem->expects($this->any())->method('getOptionByCode')->willReturn(true);
        $this->quoteItem->expects($this->any())->method('removeOption')->willReturnSelf('');
        $this->quoteItem->method('save')->willReturnSelf();
        $this->cartRepositoryInterface->method('save')->willReturnSelf();
        $this->reorderModelMock->execute($reorderItems);
    }


    /**
     * @return void
     */
    public function testValidateCustomerItems()
    {
        $reorderItems = [
            1 => ['order_id' => 101],
            2 => ['order_id' => 102],
            3 => ['order_id' => 103]
        ];
        $customerId = 123;
        $requestedOrdersIds = [101, 102, 103];
        $itemsIds = [1, 2, 3];

        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                [OrderItemInterface::ITEM_ID, $itemsIds, 'in'],
                [OrderItemInterface::ORDER_ID, $requestedOrdersIds, 'in']
            )
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);


        $this->orderItemRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->orderItemSearchResult);

        $this->orderItemSearchResult->expects($this->once())
            ->method('getItems')
            ->willReturn([]);
        $result = $this->reorderModelMock->validateCustomerItems($reorderItems, $customerId);
        $this->assertFalse($result);
    }

    /**
     * @return void
     */
    public function testValidateCustomerOrder()
    {
        $requestedOrdersIds = [1, 2, 3];
        $customerId = 123;
        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['entity_id', $requestedOrdersIds, 'in'],
                ['customer_id', $customerId]
            )
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);
        $this->orderRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->orderSearchResult);
        $this->orderSearchResult->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->order, $this->order, $this->order]);
        $this->order->method('getEntityId')
            ->will($this->onConsecutiveCalls(1, 2, 3));
        $result = $this->reorderModelMock->validateCustomerOrder($requestedOrdersIds, $customerId);
        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function testValidateCustomerOrderFailure()
    {
        $requestedOrdersIds = [1, 2, 3];
        $customerId = 123;
        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['entity_id', $requestedOrdersIds, 'in'],
                ['customer_id', $customerId]
            )
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->orderRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->orderSearchResult);
        $this->orderSearchResult->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $result = $this->reorderModelMock->validateCustomerOrder($requestedOrdersIds, $customerId);
        $this->assertFalse($result);
    }


    /**
     * Add Item to the Cart
     *
     */
    public function testAddItemToCart()
    {
        $additionalOptionResult = ['label' => 'fxoProductInstance', 'value' => '57854580254633540'];

        $this->orderItemMock->expects($this->any())->method('getProductOptionByCode')->willReturnMap([
            ['info_buyRequest', []],
            ['additional_options', $additionalOptionResult]
        ]);

        $this->orderItemMock->expects($this->any())->method('getQtyOrdered')->willReturn(50);
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn(578);
        $this->testValidateAttributeSetName();
        $this->cart->expects($this->any())->method('addProduct')->willReturn('jj');
        $this->serializerInterface->expects($this->any())->method('serialize')->willReturnSelf();
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->orderItemMock->expects($this->any())->method('getProductOptionByCode')->willReturn('test');

        $this->reorderModelMock->addItemToCart($this->orderItemMock, $this->cart, $this->product);
    }

    /**
     * Add Item to the Cart
     *
     */
    public function testAddItemToCartWithLocalizedException()
    {
        $phrase = new Phrase(__('Exception message'));
        $localizedException = new LocalizedException($phrase);

        $additionalOptionResult = ['label' => 'fxoProductInstance', 'value' => '57854580254633540'];

        $this->orderItemMock->expects($this->any())->method('getProductOptionByCode')->willReturnMap([
            ['info_buyRequest', []],
            ['additional_options', $additionalOptionResult]
        ]);

        $this->orderItemMock->expects($this->any())->method('getQtyOrdered')->willReturn(50);
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn(578);
        $this->testValidateAttributeSetName();
        $this->cart->expects($this->any())->method('addProduct')->willThrowException($localizedException);
        $this->serializerInterface->expects($this->any())->method('serialize')->willReturnSelf();

        $this->reorderModelMock->addItemToCart($this->orderItemMock, $this->cart, $this->product);
    }

    /**
     * @throws \Throwable
     */
    public function testAddItemToCartWithThrowable()
    {
        $this->exception = new \Exception('Test exception');
        $additionalOptionResult = ['label' => 'fxoProductInstance', 'value' => '57854580254633540'];

        $this->orderItemMock->expects($this->any())->method('getProductOptionByCode')->willReturnMap([
            ['info_buyRequest', []],
            ['additional_options', $additionalOptionResult]
        ]);

        $this->orderItemMock->expects($this->any())->method('getQtyOrdered')->willReturn(50);
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn(578);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductCustomAttributeValue')
            ->with(578, 'customizable')
            ->willReturn(true);
        $this->orderHistoryEnhacement->expects($this->any())->method('loadProductObj')->with(578)
            ->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getAttributeSetId')->willReturn(12);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductAttributeSetName')
            ->willReturn('FXOPrintProducts');

        $this->cart->expects($this->any())->method('addProduct')->willThrowException($this->exception);
        $this->serializerInterface->expects($this->any())->method('serialize')->willReturnSelf();

        $this->reorderModelMock->addItemToCart($this->orderItemMock, $this->cart, $this->product);
    }



    /**
     * testValidateAttributeSetName
     *
     */
    public function testValidateAttributeSetName()
    {
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn(578);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductCustomAttributeValue')
            ->with(578, 'customizable')
            ->willReturn(true);

        $this->orderHistoryEnhacement->expects($this->any())->method('loadProductObj')->with(578)
            ->willReturn($this->productInterface);

        $this->productInterface->expects($this->any())->method('getAttributeSetId')->willReturn(12);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductAttributeSetName')
            ->willReturn('FXOPrintProducts');

        $this->reorderModelMock->validateAttributeSetName($this->orderItemMock);
    }

    /**
     * testValidateAttributeSetName
     *
     */
    public function testValidateAttributeSetName2()
    {
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn(88);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductCustomAttributeValue')
            ->with(88, 'customizable')
            ->willReturn(false);

        $this->orderHistoryEnhacement->expects($this->any())->method('loadProductObj')->with(88)
            ->willReturn($this->productInterface);

        $this->productInterface->expects($this->any())->method('getAttributeSetId')->willReturn(12);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductAttributeSetName')
            ->willReturn('FXOPrintProductss');

        $this->reorderModelMock->validateAttributeSetName($this->orderItemMock);
    }

    /**
     * testValidateAttributeSetName1
     *
     */
    public function testValidateAttributeSetName1()
    {
        $this->orderItemMock->expects($this->any())->method('getProductId')->willReturn(88);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductCustomAttributeValue')
            ->with(88, 'customizable')
            ->willReturn(true);
        $this->orderHistoryEnhacement->expects($this->any())->method('loadProductObj')->with(88)
            ->willReturn($this->productInterface);

        $this->productInterface->expects($this->any())->method('getAttributeSetId')->willReturn(12);
        $this->orderHistoryEnhacement->expects($this->any())->method('getProductAttributeSetName')
            ->willReturn('FXOPrintProducts');

        $this->reorderModelMock->validateAttributeSetName($this->orderItemMock);
    }
}
