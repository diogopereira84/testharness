<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Ui\Helper;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Fedex\CmsImportExport\Helper\Data;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $writeInterface;
    protected $filesystem;
    protected $blockFactory;
    protected $blockCms;
    protected $pageFactory;
    protected $pageCms;
    protected $collectionFactory;
    protected $collectionCategory;
    protected $storeRepositoryInterface;
    protected $productRepositoryInterface;
    protected $productInterface;
    /**
     * @var (\Magento\Framework\App\Filesystem\DirectoryList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryList;
    /**
     * @var (\Magento\Framework\App\Helper\AbstractHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractHelper;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    protected $driverInterface;
    protected $themeFactory;
    protected $themeCollection;
    protected $themeModelMock;
    protected $helperData;
    /**
     * @var Index
     */
    protected $controller;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->getMockBuilder(Context::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->writeInterface = $this->getMockBuilder(WriteInterface::class)
                ->setMethods(['getAbsolutePath'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
                ->setMethods(['getDirectoryWrite','getAbsolutePath'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->blockFactory = $this->getMockBuilder(BlockFactory::class)
                ->setMethods(['load', 'create', 'getId'])
                ->disableOriginalConstructor()
                ->getMock();


        $this->blockCms = $this->getMockBuilder(\Magento\Cms\Model\Block::class)
                ->setMethods(['load', 'create', 'getId'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
                ->setMethods(['load', 'create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->pageCms = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
                ->setMethods(['load', 'create', 'getId'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
                ->setMethods(['addAttributeToFilter','create','getCategoryCollection', 'getCollection'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->collectionCategory = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
                ->setMethods(['addAttributeToFilter','create','getCategoryCollection','getCollection','setPageSize','getEntityId'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->storeRepositoryInterface = $this->getMockBuilder(StoreRepositoryInterface::class)
                ->setMethods(['getCode', 'getId'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->productRepositoryInterface = $this->getMockBuilder(ProductRepositoryInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->productInterface = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();


        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->abstractHelper = $this->getMockBuilder(AbstractHelper::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->driverInterface = $this->getMockBuilder(File::class)
                ->setMethods(['fileOpen', 'fileGetCsv', 'fileClose'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();


        $this->themeFactory = $this->getMockBuilder(\Magento\Theme\Model\ResourceModel\Theme\CollectionFactory ::class)
                ->setMethods(['getCollection','addFieldToFilter','setPageSize','create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->themeCollection = $this->getMockBuilder(\Magento\Theme\Model\ResourceModel\Theme\Collection::class)
                ->setMethods(['getCollection','addFieldToFilter','setPageSize','create','getCode','getId', 'getIterator'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->themeModelMock = $this->getMockBuilder(\Magento\Theme\Model\Theme::class)
                ->setMethods(['getCode','getId', 'getData'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->helperData = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'fileSystem' => $this->filesystem,
                'driverInterface' => $this->driverInterface,
                'blockFactory' => $this->blockFactory,
                'pageFactory' => $this->pageFactory,
                'collectionFactory' => $this->collectionFactory,
                'storeRepositoryInterface' => $this->storeRepositoryInterface,
                'logger' => $this->logger,
                'productRepository' => $this->productRepositoryInterface,
                'themeFactory' => $this->themeFactory
            ]
        );
    }

    /**
     * Get filesystem directory path to save import Csv
     *
     */
    public function testGetDestinationPath()
    {
        $response = 'var/cms';
        $this->filesystem->expects($this->any())->method('getDirectoryWrite')->with('var')->willReturn($this->writeInterface);
        $this->writeInterface->expects($this->any())->method('getAbsolutePath')->willReturn('var/cms');
        $this->assertSame($response, $this->helperData->getDestinationPath());
    }

    /**
     * Get Import Csv header row data
     *
     */
    public function testGetCsvHeader()
    {
        $uploadedFile = "/var/www/html/shop-staging2.fedex.com/var/cms/filedata.csv";
        $response = ["type","title"];
        $this->driverInterface->expects($this->any())->method('fileOpen')->with($uploadedFile)->willReturn($this->driverInterface);
        $this->driverInterface->expects($this->any())->method('fileGetCsv')->with($this->driverInterface)->willReturn($response);
        $this->driverInterface->expects($this->any())->method('fileClose')->with($this->driverInterface)->willReturn('content');
        $this->assertSame($response, $this->helperData->getCsvHeader($uploadedFile));
    }

    /**
     * Get Import Csv header row data
     *
     */
    public function testGetCsvHeaderWithNoFile()
    {
        $uploadedFile = $response = null;
        $this->driverInterface->expects($this->any())->method('fileOpen')->with($uploadedFile)->willReturn($this->driverInterface);
        $this->driverInterface->expects($this->any())->method('fileGetCsv')->with($this->driverInterface)->willReturn($response);
        $this->assertSame($response, $this->helperData->getCsvHeader($uploadedFile));
    }

    /**
     * Get filesystem directory path to save import Csv
     *
     */
    // public function testConvertCsvToArray()
    // {
    //     //Need to be discuss
    //     $uploadedFile = "/var/www/html/shop-staging2.fedex.com/var/cms/filedata.csv";
    //     $expectedResult = ["type","title"];
    //     $this->driverInterface->expects($this->any())->method('fileOpen')->with($uploadedFile)->willReturn($this->driverInterface);
    //     $this->driverInterface->expects($this->any())->method('fileGetCsv')->with($this->driverInterface)->willReturn($expectedResult);
    //     //$this->helperData->convertCsvToArray($uploadedFile);
    //     //$this->assertSame($expectedResult, $this->helperData->convertCsvToArray($uploadedFile));
    // }

    /**
     * Update import csv content to save based on block id identifier
     *
     */
    public function testGetBlockUpdateContentWithWidget()
    {
        $content = $response = "Block Content";
        $blockIdentifier = "=>catalog_events_lister|=>block2";
        $type = "widget";
        $this->blockFactory->expects($this->any())->method('create')->willReturn($this->blockCms);
        $this->blockCms->expects($this->any())->method('load')->willReturnSelf();
        $this->blockCms->expects($this->any())->method('getId')->willReturn(1);
        $this->assertSame($response, $this->helperData->getBlockUpdateContent($content, $blockIdentifier, $type));
    }

    /**
     * Update import csv content to save based on block id identifier
     *
     */
    public function testGetBlockUpdateContentWithoutWidget()
    {
        $content = $response = "Block Content";
        $blockIdentifier = "=>catalog_events_lister|=>block2";
        $type = "block";
        $this->blockFactory->expects($this->any())->method('create')->willReturn($this->blockCms);
        $this->blockCms->expects($this->any())->method('load')->willReturnSelf();
        $this->assertSame($response, $this->helperData->getBlockUpdateContent($content, $blockIdentifier, $type));
    }

    /**
     * Update import csv content to save based on block id identifier
     *
     */
    public function testGetBlockUpdateContentWithException()
    {
        $content = "Block Content";
        $blockIdentifier = "";
        $type = "block";
        $response = null;
        $this->blockFactory->expects($this->any())->method('create')->willReturn($this->blockCms);
        $this->blockCms->expects($this->any())->method('load')->willReturnSelf();
        $this->assertSame($response, $this->helperData->getBlockUpdateContent($content, $blockIdentifier, $type));
    }

    /**
     * Update import csv content to save based on page identifier
     *
     */
    public function testGetPageUpdateContentWithWidget()
    {
        $content = $response = "Page Content";
        $pageIdentifier = "=>catalog_events_lister|=>block2";
        $type = "widget";
        $this->pageFactory->expects($this->any())->method('create')->willReturn($this->pageCms);
        $this->pageCms->expects($this->any())->method('load')->willReturnSelf();
        $this->pageCms->expects($this->any())->method('getId')->willReturn(1);
        $this->assertSame($response, $this->helperData->getPageUpdateContent($content, $pageIdentifier, $type));
    }

    /**
     * Update import csv content to save based on page identifier
     *
     */
    public function testGetPageUpdateContentWithoutWidget()
    {
        $content = $response = "Page Content";
        $pageIdentifier = "=>catalog_events_lister|=>block2";
        $type = "page";
        $this->pageFactory->expects($this->any())->method('create')->willReturn($this->pageCms);
        $this->pageCms->expects($this->any())->method('load')->willReturnSelf();
        $this->assertSame($response, $this->helperData->getPageUpdateContent($content, $pageIdentifier, $type));
    }

    /**
     * Update import csv content to save based on page identifier
     *
     */
    public function testGetPageUpdateContentWithException()
    {
        $content = "Page Content";
        $pageIdentifier = "";
        $type = "page";
        $response = null;
        $this->pageFactory->expects($this->any())->method('create')->willReturn($this->pageCms);
        $this->pageCms->expects($this->any())->method('load')->willReturnSelf();
        $this->assertSame($response, $this->helperData->getPageUpdateContent($content, $pageIdentifier, $type));
    }

    /**
     * Update import csv content to save for widget based on product sku
     *
     */
    public function testGetProductUpdateContentWithWidget()
    {
        $content = $response = "Product Contanet";
        $productSku = "=>SKU|=>SKU1";
        $type = "widget";
        $this->productRepositoryInterface->expects($this->any())->method('get')->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getId')->willReturn(0);
        $this->assertSame($response, $this->helperData->getProductUpdateContent($content, $productSku, $type));
    }

    /**
     * Update import csv content to save for widget based on product sku
     *
     */
    public function testGetProductUpdateContentWithoutWidget()
    {
        $content = $response = "Product Contanet";
        $productSku = "=>SKU|=>SKU1";
        $type = "page";
        $this->productRepositoryInterface->expects($this->any())->method('get')->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->assertSame($response, $this->helperData->getProductUpdateContent($content, $productSku, $type));
    }

    /**
     * Update import csv content to save for widget based on product sku
     *
     */
    public function testGetProductUpdateContentWithException()
    {
        $content = "Product Content";
        $productSku = "";
        $type = "page";
        $response = null;
        $this->productRepositoryInterface->expects($this->any())->method('get')->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->assertSame($response, $this->helperData->getProductUpdateContent($content, $productSku, $type));
    }

    /**
     * Update import csv content to save for widget based on product sku
     *
     */
    public function testGetProductWidgetUpdateContentWithId()
    {
        $widgetEntitiesData = 'widget';
        $response = '1';
        $this->productRepositoryInterface->expects($this->any())->method('get')->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->assertSame($response, $this->helperData->getProductWidgetUpdateContent($widgetEntitiesData));
    }

    /**
     * Update import csv content to save for widget based on product sku
     *
     */
    public function testGetProductWidgetUpdateContentWithoutId()
    {
        $widgetEntitiesData = 'widget';
        $response = '';
        $this->productRepositoryInterface->expects($this->any())->method('get')->willReturn($this->productInterface);
        $this->productInterface->expects($this->any())->method('getId')->willReturn(0);
        $this->assertSame($response, $this->helperData->getProductWidgetUpdateContent($widgetEntitiesData));
    }

    /**
     * Get store data in array form
     *
     */
    public function testGetStoreDataDefaultStore()
    {
        $storeId = 0;
        $response[] = 0;
        $this->storeRepositoryInterface->expects($this->any())->method('get')->with($storeId)->willReturnSelf();
        $this->storeRepositoryInterface->expects($this->any())->method('getId')->willReturn($storeId);
        $this->assertSame($response, $this->helperData->getStoreData($storeId));
    }

    /**
     * Get store data in array form
     *
     */
    public function testGetStoreDataOtherStore()
    {
        $storeId = 1;
        $response[] = 1;
        $this->storeRepositoryInterface->expects($this->any())->method('get')->with($storeId)->willReturnSelf();
        $this->storeRepositoryInterface->expects($this->any())->method('getId')->willReturn($storeId);
        $this->assertSame($response, $this->helperData->getStoreData($storeId));
    }

    /**
     * Get store data in array form
     *
     */
    public function testGetStoreDataOtherStoreWithException()
    {
        $storeId = ['1'];
        $response = null;
        $this->storeRepositoryInterface->expects($this->any())->method('get')->with($storeId)->willReturnSelf();
        $this->storeRepositoryInterface->expects($this->any())->method('getId')->willReturn($storeId);
        $this->assertSame($response, $this->helperData->getStoreData($storeId));
    }


    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryUpdateContentWithoutCategoryData()
    {
        $content = $response = "Block Content";
        $categoryName = "=>category/category1/category2";
        $type = "page";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([$this->collectionCategory]);
        $this->assertSame($response, $this->helperData->getCategoryUpdateContent($content, $categoryName, $type));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryUpdateContent()
    {
        $content = $response = "Block Content";
        $categoryName = "=>category1|=>category2";
        $type = "widget";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([$this->collectionCategory]);
        $this->assertSame($response, $this->helperData->getCategoryUpdateContent($content, $categoryName, $type));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryUpdateContentWithoutWidget()
    {
        $content = $response = "Block Content";
        $categoryName = "=>category1|=>category2";
        $type = "page";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([$this->collectionCategory]);
        $this->assertSame($response, $this->helperData->getCategoryUpdateContent($content, $categoryName, $type));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryUpdateContentWithoutKey()
    {
        $content = $response = "Block Content";
        $categoryName = "=>widget/widget1";
        $type = "page";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([]);
        $this->assertSame($response, $this->helperData->getCategoryUpdateContent($content, $categoryName, $type));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryUpdateContentWithException()
    {
        $content = "Block Content";
        $categoryName = ["category1"];
        $type = "page";
        $response = null;
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([$this->collectionCategory]);
        $this->assertSame($response, $this->helperData->getCategoryUpdateContent($content, $categoryName, $type));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryWidgetUpdateContent()
    {
        $response = null;
        $widgetEntitiesData = "=>widget|=>widget1";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([$this->collectionCategory]);
        $this->assertSame($response, $this->helperData->getCategoryWidgetUpdateContent($widgetEntitiesData));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryWidgetUpdateContentWithoutData()
    {
        $response = null;
        $widgetEntitiesData = "=>widget|=>widget1";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([]);
        $this->assertSame($response, $this->helperData->getCategoryWidgetUpdateContent($widgetEntitiesData));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryWidgetUpdateContentWithoutKey()
    {
        $response = null;
        $widgetEntitiesData = "widget/widget1";
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([$this->collectionCategory]);
        $this->assertSame($response, $this->helperData->getCategoryWidgetUpdateContent($widgetEntitiesData));
    }

    /**
     * Update import csv content to save based on category id path
     *
     */
    public function testGetCategoryWidgetUpdateContentWithException()
    {
        $response = null;
        $widgetEntitiesData = ["widget1"];
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionCategory);
        $this->collectionCategory->expects($this->any())->method('addAttributeToFilter')->will($this->returnSelf());
        $this->collectionCategory->expects($this->any())->method('setPageSize')->willReturn([]);
        $this->assertSame($response, $this->helperData->getCategoryWidgetUpdateContent($widgetEntitiesData));
    }

    /**
     * Get Theme By Code Id
     *
     */
    public function testGetThemeCodeById()
    {
        $themeId = 1;
        $response = 'Magento/luma';

        $this->themeFactory->expects($this->any())->method('create')->willReturn($this->themeCollection);
        $this->themeCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->themeCollection->expects($this->any())->method('setPageSize')->willReturn([$this->themeModelMock]);

        $this->themeCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->themeModelMock]));
        $this->themeModelMock->expects($this->any())->method('getCode')->willReturn('Magento/luma');


        $this->assertSame($response, $this->helperData->getThemeCodeById($themeId));
    }

    /**
     * Get Theme By Code Id
     *
     */
    public function testGetThemeCodeByIdWithException()
    {
        $themeId = 1;
        $response = null;
        $exception = new \Exception();
        $this->themeFactory->expects($this->any())->method('create')->willThrowException($exception);

        $this->assertSame($response, $this->helperData->getThemeCodeById($themeId));
    }

    /**
     * Get theme id based on theme code
     *
     */
    public function testGetThemeDetail()
    {
        $themeId = '2';
        $response = '2';
        $this->themeFactory->expects($this->any())->method('create')->willReturn($this->themeCollection);
        $this->themeCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->themeCollection->expects($this->any())->method('setPageSize')->willReturn($this->themeCollection);
        $this->themeCollection->expects($this->any())->method('setPageSize')->willReturn([$this->themeModelMock]);

        $this->themeCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->themeModelMock]));
        $this->themeModelMock->expects($this->any())->method('getId')->willReturn($themeId);

        $this->assertSame($response, $this->helperData->getThemeDetail($themeId));
    }

    /**
     * Get theme id based on theme code
     *
     */
    public function testGetThemeDetailWithException()
    {
        $code = 'default';
        $response = null;
        $exception = new \Exception();
        $this->themeFactory->expects($this->any())->method('create')->willThrowException($exception);

        $this->assertSame($response, $this->helperData->getThemeDetail($code));
    }

    /**
     * Get Theme By Code Id
     *
     */
    public function testGetStoreCodeById()
    {
        $storeId = 1;
        $response = 'base';
        $this->storeRepositoryInterface->expects($this->any())->method('get')->with($storeId)->willReturnSelf();
        $this->storeRepositoryInterface->expects($this->any())->method('getCode')->willReturn("base");
        $this->assertSame($response, $this->helperData->getStoreCodeById($storeId));
    }

    /**
     * Get Theme By Code Id
     *
     */
    public function testGetStoreCodeByIdEmptyId()
    {
        $storeId = '';
        $response = '';
        $this->storeRepositoryInterface->expects($this->any())->method('get')->with($storeId)->willReturnSelf();
        $this->assertSame($response, $this->helperData->getStoreCodeById($storeId));
    }

    /**
     * Get Theme By Code Id
     *
     */
    public function testGetStoreCodeByIdEmptyIds()
    {
        $storeId = 1;
        $response = null;
        $this->storeRepositoryInterface->expects($this->any())->method('get')->with(null)->willReturnSelf();
        $this->assertSame($response, $this->helperData->getStoreCodeById($storeId));
    }
}
