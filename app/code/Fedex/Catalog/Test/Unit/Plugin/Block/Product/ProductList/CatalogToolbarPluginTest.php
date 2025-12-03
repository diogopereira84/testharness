<?php
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Plugin\Block\Product\ProductList;

use Fedex\Catalog\Plugin\Block\Product\ProductList\CatalogToolbarPlugin;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\CatalogMvp\Helper\SharedCatalogLiveSearch;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Request\Http;
use Magento\Customer\Model\Session;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class StubCustomerSession extends Session
{
    public function getProductListLimit()
    {
        return 10;
    }
}

class CatalogToolbarPluginTest extends TestCase
{
    private DeliveryHelper $deliveryHelper;
    private SdeHelper $sdeHelper;
    private SharedCatalogLiveSearch $liveSearchHelper;
    private MvpHelper $catalogMvpHelper;
    private ToggleConfig $toggleConfig;
    private CollectionFactory $magentoCollectionFactory;
    private ProductFactory $productFactory;
    private Http $request;

    private CatalogToolbarPlugin $plugin;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->deliveryHelper = $this->createMock(DeliveryHelper::class);
        $this->sdeHelper = $this->createMock(SdeHelper::class);
        $this->liveSearchHelper = $this->createMock(SharedCatalogLiveSearch::class);
        $this->catalogMvpHelper = $this->createMock(MvpHelper::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->magentoCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->productFactory = $this->createMock(ProductFactory::class);

        $this->plugin = new CatalogToolbarPlugin(
            $this->deliveryHelper,
            $this->sdeHelper,
            $this->liveSearchHelper,
            $this->catalogMvpHelper,
            $this->toggleConfig,
            $this->magentoCollectionFactory,
            $this->productFactory
        );
    }

    public function testAfterGetFirstNumWithToggleConfigEnabled()
    {
        $this->catalogMvpHelper->method('shouldApplyCustomPagination')->willReturn(true);

        $collection = $this->createMock(Collection::class);
        $collection->method('getPageSize')->willReturn(10);
        $collection->method('getCurPage')->willReturn(3);

        $reflection = new ReflectionClass($this->plugin);
        $prop = $reflection->getProperty('collection');
        $prop->setAccessible(true);
        $prop->setValue($this->plugin, $collection);

        $toolbar = $this->createMock(Toolbar::class);

        $expectedFirstNum = 21;

        $this->assertEquals($expectedFirstNum, $this->plugin->afterGetFirstNum($toolbar, 0));
    }

    public function testAfterGetFirstNumWithIrrelevantContext()
    {
        $this->catalogMvpHelper->method('shouldApplyCustomPagination')->willReturn(false);

        $collection = $this->createMock(Collection::class);

        $reflection = new ReflectionClass($this->plugin);
        $prop = $reflection->getProperty('collection');
        $prop->setAccessible(true);
        $prop->setValue($this->plugin, $collection);

        $toolbar = $this->createMock(Toolbar::class);
        $defaultResult = 999;

        $this->assertEquals($defaultResult, $this->plugin->afterGetFirstNum($toolbar, $defaultResult));
    }

    public function testAfterGetLastNumWithCustomPaginationEnabled()
    {
        $this->catalogMvpHelper->method('shouldApplyCustomPagination')->willReturn(true);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('setPageSize')->with(10);
        $collection->method('getPageSize')->willReturn(10);
        $collection->method('getCurPage')->willReturn(2);
        $collection->method('getSize')->willReturn(15);
        $collection->method('count')->willReturn(15);

        $reflection = new ReflectionClass($this->plugin);
        $prop = $reflection->getProperty('collection');
        $prop->setAccessible(true);
        $prop->setValue($this->plugin, $collection);

        $toolbar = $this->createMock(Toolbar::class);
        $expected = min(2 * 10, 15);

        $this->assertEquals($expected, $this->plugin->afterGetLastNum($toolbar, 0));
    }

    public function testAftergetTotalNumReturnsCollectionSizeForEproCustomerNotSdeStore()
    {
        $toolbar = $this->createMock(Toolbar::class);
        $collection = $this->createMock(Collection::class);

        $this->deliveryHelper->method('isEproCustomer')->willReturn(true);
        $this->sdeHelper->method('getIsSdeStore')->willReturn(false);
        $toolbar->method('getCollection')->willReturn($collection);
        $collection->method('getSize')->willReturn(42);

        $this->assertEquals(42, $this->plugin->aftergetTotalNum($toolbar, 10));
    }

    public function testAftergetTotalNumReturnsOriginalResultIfNotEproCustomerOrSdeStore()
    {
        $toolbar = $this->createMock(Toolbar::class);

        $this->deliveryHelper->method('isEproCustomer')->willReturn(false);
        $this->sdeHelper->method('getIsSdeStore')->willReturn(true);

        $this->assertEquals(10, $this->plugin->aftergetTotalNum($toolbar, 10));
    }

    public function testAfterGetLastNumWithToggleConfigDisabled()
    {
        $this->catalogMvpHelper->method('shouldApplyCustomPagination')->willReturn(false);

        $toolbar = $this->createMock(Toolbar::class);
        $collection = $this->createMock(Collection::class);

        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getPageSize')->willReturn(5);
        $collection->method('getCurPage')->willReturn(2);
        $collection->method('getSize')->willReturn(10);
        $collection->method('count')->willReturn(10);

        $reflection = new ReflectionClass($this->plugin);
        $property = $reflection->getProperty('collection');
        $property->setAccessible(true);
        $property->setValue($this->plugin, $collection);

        $totalNumProp = $reflection->getProperty('totalNum');
        $totalNumProp->setAccessible(true);
        $totalNumProp->setValue($this->plugin, 9);

        $lastNum = $this->plugin->afterGetLastNum($toolbar, 0);

        $collectionCount = min($collection->count(), $collection->getPageSize());
        $expectedLastNum = $collection->getPageSize() * ($collection->getCurPage() - 1) + $collectionCount;
        $expectedLastNum = min($expectedLastNum, 9);

        $this->assertEquals($expectedLastNum, $lastNum);
    }

    public function testBeforeSetCollectionWithTotalCount()
    {
        $toolbar = $this->createMock(Toolbar::class);
        $totalCount = 3;

        $liveSearchData = [
            'data' => [
                'productSearch' => [
                    'total_count' => $totalCount
                ]
            ]
        ];

        $this->liveSearchHelper->method('getProductDeatils')->willReturn($liveSearchData);

        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->expects($this->exactly($totalCount))->method('addItem');

        $this->magentoCollectionFactory->method('create')->willReturn($mockCollection);

        $mockProduct = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->productFactory->method('create')->willReturn($mockProduct);

        $result = $this->plugin->beforeSetCollection($toolbar, $mockCollection);

        $this->assertIsArray($result);
        $this->assertSame([$mockCollection], $result);

        $reflection = new \ReflectionClass($this->plugin);
        
        $collectionProp = $reflection->getProperty('collection');
        $collectionProp->setAccessible(true);
        $this->assertSame($mockCollection, $collectionProp->getValue($this->plugin));

        $totalNumProp = $reflection->getProperty('totalNum');
        $totalNumProp->setAccessible(true);
        $this->assertEquals($totalCount, $totalNumProp->getValue($this->plugin));
    }

}
