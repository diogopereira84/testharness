<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Model\OrderBundleInfoProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Catalog\Model\Product\Type;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderBundleInfoProviderTest extends TestCase
{
    private OrderBundleInfoProvider $provider;
    private CustomerSession|MockObject $customerSession;
    private OrderRepositoryInterface|MockObject $orderRepository;
    private ProductInfoHandler|MockObject $productInfoHandler;
    private MediaConfig|MockObject $mediaConfig;
    private ConfigInterface|MockObject $productBundleConfig;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['getLastOrderId'])
            ->getMock();

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->mediaConfig = $this->createMock(MediaConfig::class);
        $this->productInfoHandler = $this->createMock(ProductInfoHandler::class);
        $this->productBundleConfig = $this->createMock(ConfigInterface::class);

        $this->provider = new OrderBundleInfoProvider(
            $this->customerSession,
            $this->orderRepository,
            $this->mediaConfig,
            $this->productInfoHandler,
            $this->productBundleConfig
        );
    }

    public function testReturnsEmptyArrayWhenToggleDisabled(): void
    {
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(false);

        $result = $this->provider->getBundleItemsSuccessPage();

        $this->assertSame([], $result);
    }

    public function testReturnsEmptyArrayWhenNoLastOrderId(): void
    {
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $this->customerSession->method('getLastOrderId')->willReturn(null);

        $result = $this->provider->getBundleItemsSuccessPage();

        $this->assertSame([], $result);
    }

    public function testReturnsEmptyArrayWhenOrderNotFound(): void
    {
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $this->customerSession->method('getLastOrderId')->willReturn(123);

        $this->orderRepository->method('get')
            ->willThrowException(new NoSuchEntityException());

        $result = $this->provider->getBundleItemsSuccessPage();

        $this->assertSame([], $result);
    }

    public function testReturnsBundleAndNonBundleItems(): void
    {
        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $this->customerSession->method('getLastOrderId')->willReturn(123);

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllItems'])
            ->getMock();
        $this->orderRepository->method('get')->with(123)->willReturn($orderMock);

        // Mock bundle product
        $bundleProduct = $this->createConfiguredMock(Product::class, [
            'getName' => 'Bundle Product',
            'getId' => 11
        ]);

        // Mock child product
        $childProduct = $this->createConfiguredMock(Product::class, [
            'getName' => 'Child Product',
            'getId' => 22,
            'getImage' => 'image.jpg'
        ]);

        // Mock bundle order item
        $bundleItem = $this->getMockBuilder(OrderItemInterface::class)
            ->addMethods(['getId','getProduct','getChildrenItems', 'getImage'])
            ->getMockForAbstractClass();
        $bundleItem->method('getId')->willReturn(1);
        $bundleItem->method('getItemId')->willReturn(123);
        $bundleItem->method('getParentItem')->willReturn(null);
        $bundleItem->method('getProductType')->willReturn(Type::TYPE_BUNDLE);
        $bundleItem->method('getProduct')->willReturn($bundleProduct);
        $bundleItem->method('getName')->willReturn('Bundle Item Name');
        $bundleItem->method('getPrice')->willReturn(15);
        $bundleItem->method('getRowTotal')->willReturn(15);
        $bundleItem->method('getDiscountAmount')->willReturn(2);
        $bundleItem->method('getImage')->willReturn(null);

        // Mock child order item
        $childItem = $this->getMockBuilder(OrderItemInterface::class)
            ->addMethods(['getId', 'getProduct', 'getImage'])
            ->getMockForAbstractClass();
        $childItem->method('getId')->willReturn(2);
        $childItem->method('getParentItem')->willReturn($bundleItem);
        $childItem->method('getProductType')->willReturn('simple');
        $childItem->method('getProduct')->willReturn($childProduct);
        $childItem->method('getQuoteItemId')->willReturn(123);
        $childItem->method('getName')->willReturn('Item Name');
        $childItem->method('getQtyOrdered')->willReturn(5);
        $childItem->method('getPrice')->willReturn(15);
        $childItem->method('getRowTotal')->willReturn(15);
        $childItem->method('getDiscountAmount')->willReturn(2);
        $bundleItem->method('getImage')->willReturn(null);

        $bundleItem->method('getChildrenItems')->willReturn([$childItem]);

        $orderMock->method('getAllItems')->willReturn([$bundleItem, $childItem]);

        $this->mediaConfig->expects($this->once())
            ->method('getMediaUrl')
            ->with('image.jpg')
            ->willReturn('http://example.com/media/catalog/product/image.jpg');

        $result = $this->provider->getBundleItemsSuccessPage();

        $bundleProductData = [
            'id' => 123,
            'type'=> Type::TYPE_BUNDLE,
            'name' => 'Bundle Item Name',
            'price' => 15,
            'subtotal' => 13,
            'discount' => 2,
            'image' => null,
            'child_ids' => [0 => 123],
            'children_data' => [
                123 => [
                    'name' => 'Item Name',
                    'image' => 'http://example.com/media/catalog/product/image.jpg',
                    'preview_url' => null,
                    'qty' => 5,
                    'price' => 15,
                    'subtotal' => 15,
                    'discount' => 2
                ]
            ]
        ];

        $this->assertCount(1, $result);
        $this->assertSame([$bundleProductData], $result);
    }
}
