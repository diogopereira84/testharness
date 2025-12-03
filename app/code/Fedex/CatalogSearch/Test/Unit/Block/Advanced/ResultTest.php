<?php
/**
 * @category    Fedex
 * @package     Fedex_CatalogSearch
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types = 1);

namespace Fedex\CatalogSearch\Test\Unit\Block\Advanced;

use Magento\Framework\View\Page\Title;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\View\LayoutInterface;
use Magento\Theme\Block\Html\Breadcrumbs;
use Fedex\CatalogSearch\Block\Advanced\Result;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Page\Config;
use Magento\Catalog\Model\Category;
use Magento\CatalogSearch\Model\Advanced;
use \ReflectionMethod;

class ResultTest extends TestCase
{
    const PRODUCT_COLLECTION_COUNT = 5;
    const GET_DATA_METHOD = 'getData';

    /**
     * @var Breadcrumbs
     */
    private Breadcrumbs $breadcrumbs;

    /**
     * @var LayoutInterface
     */
    private LayoutInterface $layout;

    /**
     * @var Result
     */
    private Result $result;

    /**
     * @var ReflectionMethod
     */
    private ReflectionMethod $prepareLayoutMethod;

    /**
     * Test setup
     * @return void
     */
    public function setUp(): void
    {
        $this->breadcrumbs = $this->createMock(Breadcrumbs::class);
        $this->breadcrumbs->method('addCrumb')->willReturnSelf();
        $this->layout = $this->createMock(LayoutInterface::class);
        $title = $this->createMock(Title::class);
        $storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->addMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->method("getBaseUrl")->willReturn("test.com");
        $storeManager->method('getStore')->willReturn($store);

        $pageConfig = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $pageConfig->method('getTitle')->willReturn($title);
    
        $this->result = $this->getMockBuilder(Result::class)
            ->addMethods(['setAvailableOrders', 'setDefaultSortBy', 'setResultCount'])
            ->onlyMethods(['getLayout', 'getPageTitle','getSearchCriterias', 'getChildBlock', 'getData', 'getSearchModel'])
            ->disableOriginalConstructor()
            ->getMock();
        $category = $this->createMock(Category::class);
        $category->method('getAvailableSortByOptions')->willReturn([
            'position' => 1
        ]);

        $catalogLayer = $this->createMock(Layer::class);
        $catalogLayer->method('getCurrentCategory')->willReturn($category);
    
        $reflection = new \ReflectionClass(Result::class);

        $storeManagerReflection = $reflection->getProperty('_storeManager');
        $storeManagerReflection->setAccessible(true);
        $storeManagerReflection->setValue($this->result, $storeManager);

        $pageConfigReflection = $reflection->getProperty('pageConfig');
        $pageConfigReflection->setAccessible(true);
        $pageConfigReflection->setValue($this->result, $pageConfig);

        $catalogLayerReflection = $reflection->getProperty('_catalogLayer');
        $catalogLayerReflection->setAccessible(true);
        $catalogLayerReflection->setValue($this->result, $catalogLayer);
    
        $this->result->method('getLayout')->willReturn($this->layout);

        $this->prepareLayoutMethod = new \ReflectionMethod(
            Result::class,
            '_prepareLayout'
        );
        $this->prepareLayoutMethod->setAccessible(true);

        $this->result->method('getSearchCriterias')->willReturn([
            'left' => ['test','test'],
            'right' => ['test', 'test']
        ]);

        $this->result->method('getChildBlock')->willReturnSelf();
        $this->result->method('setAvailableOrders')->willReturnSelf();
        $this->result->method('setDefaultSortBy')->willReturnSelf();
        $this->result->method('setResultCount')->willReturnSelf();      

        $searchModel = $this->createMock(Advanced::class);
        $productCollection = $this->createMock(Collection::class);
        $productCollection->method('count')->willReturn(self::PRODUCT_COLLECTION_COUNT);
        $searchModel->method('getProductCollection')->willReturn($productCollection);
        $this->result->method('getSearchModel')->willReturn($searchModel);
    }

    /**
     * Test _prepareLayout function result when active
     * @return void
     */
    public function testPrepareLayout(): void
    {
        $this->layout->method('getBlock')->willReturn($this->breadcrumbs);
        $result = $this->prepareLayoutMethod->invoke($this->result);
        $this->assertEquals($this->result, $result);
    }

    /**
     * Test _prepareLayout function result when inactive
     * @return void
     */
    public function testPrepareLayoutNegative(): void
    {
        $this->layout->method('getBlock')->willReturn(false);
        $result = $this->prepareLayoutMethod->invoke($this->result);
        $this->assertEquals($this->result, $result);
    }

    /**
     * Test setListOrders function result
     * @return void
     */
    public function testSetListOrders(): void
    {
        $result = $this->result->setListOrders();
        $this->assertEquals(null, $result);
    }

    /**
     * Test getResultCount function result when data is present
     * @return void
     */
    public function testGetResultCountPositive(): void
    {
        $this->result->method(self::GET_DATA_METHOD)->willReturn(self::PRODUCT_COLLECTION_COUNT);
        $result = $this->result->getResultCount();
        $this->assertEquals(self::PRODUCT_COLLECTION_COUNT, $result);
        $this->assertIsInt($result);
    }

    /**
     * Test getResultCount function result when data is missing
     * @return void
     */
    public function testGetResultCountNegative(): void
    {
        $this->result->method(self::GET_DATA_METHOD)->willReturn(false);
        $result = $this->result->getResultCount();
        $this->assertEquals(false, $result);
    }
}
