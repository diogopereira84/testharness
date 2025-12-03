<?php
namespace Fedex\Catalog\Test\Unit\Observer;

use Fedex\Catalog\Observer\ProductSaveBeforeObserver;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\LiveSearch\Model\SharedCatalogSkip;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Catalog\Model\Config;

class ProductSaveBeforeObserverTest extends TestCase
{
    private $requestMock;
    private $loggerMock;
    private $sharedCatalogSkipMock;
    private $toggleConfigMock;
    private $catalogConfigMock;
    private $observer;
    private $productMock;
    private $productBundleConfigMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sharedCatalogSkipMock = $this->createMock(SharedCatalogSkip::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->catalogConfigMock = $this->createMock(Config::class);
        $this->productBundleConfigMock = $this->createMock(ConfigInterface::class);
        $this->observer = new ProductSaveBeforeObserver(
            $this->requestMock,
            $this->loggerMock,
            $this->sharedCatalogSkipMock,
            $this->toggleConfigMock,
            $this->catalogConfigMock,
            $this->productBundleConfigMock
        );
        $this->productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklMcmProductId', 'setUnitCost'])
            ->onlyMethods(['setData', 'getTypeId', 'setPrice', 'getData'])
            ->getMock();
    }

    public function testUpdatePageLayoutSearchSetsData()
    {
        $postData = ['product' => ['page_layout' => 'test_layout']];
        $this->productMock->expects($this->once())
            ->method('setData')
            ->with('page_layout_search', 'test_layout');
        $this->invokeMethod($this->observer, 'updatePageLayoutSearch', [$this->productMock, $postData]);
    }

    public function testSetBundleProductPriceSetsPrice()
    {
        $postData = [
            'bundle_options' => [
                'bundle_options' => [
                    [
                        'bundle_selections' => [
                            ['selection_price_value' => '10.5'],
                            ['selection_price_value' => '5.5']
                        ]
                    ]
                ]
            ]
        ];
        $this->productMock->expects($this->once())
            ->method('setPrice')
            ->with(16.0);
        $this->invokeMethod($this->observer, 'setBundleProductPrice', [$this->productMock, $postData]);
    }

    public function testSetBundleProductPriceDoesNotSetPriceIfZero()
    {
        $postData = [
            'bundle_options' => [
                'bundle_options' => [
                    [
                        'bundle_selections' => [
                            ['selection_price_value' => '0'],
                            ['selection_price_value' => '0']
                        ]
                    ]
                ]
            ]
        ];
        $this->productMock->expects($this->never())
            ->method('setPrice');
        $this->invokeMethod($this->observer, 'setBundleProductPrice', [$this->productMock, $postData]);
    }

    public function testSetUnitCostSetsUnitCost()
    {
        $this->productMock->expects($this->once())
            ->method('getData')
            ->with('price')
            ->willReturn(99.99);
        $this->productMock->expects($this->once())
            ->method('setUnitCost')
            ->with(99.99);
        $this->invokeMethod($this->observer, 'setUnitCost', [$this->productMock]);
    }

    public function testExecuteHandlesAllBranches()
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $postData = [
            'product' => ['page_layout' => 'test_layout'],
            'bundle_options' => [
                'bundle_options' => [
                    [
                        'bundle_selections' => [
                            ['selection_price_value' => '10']
                        ]
                    ]
                ]
            ]
        ];
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn($postData);
        $this->sharedCatalogSkipMock->expects($this->once())
            ->method('getLivesearchProductListingEnable')
            ->willReturn(true);
        $this->productMock->expects($this->once())
            ->method('setData')
            ->with('page_layout_search', 'test_layout');
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('bundle');
        $this->productMock->expects($this->once())
            ->method('setPrice')
            ->with(10.0);
        $this->catalogConfigMock->expects($this->once())
            ->method('getTigerDisplayUnitCost3P1PProducts')
            ->willReturn(true);
        $this->productMock->expects($this->once())
            ->method('getMiraklMcmProductId')
            ->willReturn(false);
        $this->productMock->expects($this->once())
            ->method('getData')
            ->with('price')
            ->willReturn(10.0);
        $this->productMock->expects($this->once())
            ->method('setUnitCost')
            ->with(10.0);
        $result = $this->observer->execute($observerMock);
        $this->assertSame($this->observer, $result);
    }

    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}

