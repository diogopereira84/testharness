<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Controller\Adminhtml\CmsExport;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\CmsImportExport\Model\CmsPageFactory;
use Fedex\CmsImportExport\Model\CmsBlockFactory;
use Fedex\CmsImportExport\Model\CmsBuilderFactory;
use Fedex\CmsImportExport\Model\CmsWidgetFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Widget\Model\Widget\Instance;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\File\Csv;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Cms\Model\Page as cmsPage;
use Magento\Cms\Model\Block;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Fedex\CmsImportExport\Helper\Data;
use Psr\Log\LoggerInterface;
use \Datetime;
use Fedex\CmsImportExport\Model\CmsPage as FedexCmsPage;
use Magento\Catalog\Model\ResourceModel\Page\Collection as CmsPageCollection;
use Magento\Backend\Model\View\Result\RedirectFactory as RedirectView;
use Fedex\CmsImportExport\Controller\Adminhtml\CmsExport\Export as Export;
use Fedex\CmsImportExport\Model\ResourceModel\CmsBuilder\Collection as CmsBuilderModel;
use Fedex\CmsImportExport\Model\ResourceModel\CmsBlock\Collection as CmsBlockModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogModel;
use Fedex\CmsImportExport\Model\CmsBlock as CmsBlockData;
use Fedex\CmsImportExport\Model\CmsBuilder as CmsBuilderData;
use Fedex\CmsImportExport\Model\ResourceModel\CmsPage\Collection as CmsPageData;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Magento\Framework\View\Result\PageFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rawFactory;
    protected $modelCmsPageFactory;
    protected $requestMock;
    protected $collectionFactory;
    protected $modelCmsPage;
    protected $modelCmsBlockFactory;
    protected $blockCms;
    protected $modelCmsBuilderFactory;
    /**
     * @var (\Fedex\CmsImportExport\Model\CmsWidgetFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $modelCmsWidgetFactory;
    /**
     * @var (\Magento\Framework\Api\SearchCriteriaBuilder & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchCriteriaBuilder;
    /**
     * @var (\Magento\Framework\App\Response\Http\FileFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fileFactory;
    protected $productRepository;
    /**
     * @var (\Magento\Framework\View\Result\LayoutFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultLayoutFactory;
    protected $csvProcessor;
    protected $pageRepositoryMock;
    /**
     * @var (\Magento\Store\Model\StoreManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeManager;
    /**
     * @var (\Magento\Catalog\Api\CategoryRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryRepository;
    protected $categoryFactory;
    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageManager;
    protected $blockRepository;
    /**
     * @var (\Fedex\CmsImportExport\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helper;
    protected $instanceFactory;
    /**
     * @var (\Magento\Framework\App\Filesystem\DirectoryList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryList;
    protected $objLoggerInterface;
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Page\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionPage;
    protected $resultRedirectFactory;
    protected $filesystem;
    protected $fileDriver;
    protected $category;
    protected $product;
    protected $page;
    protected $block;
    protected $modelCmsBlock;
    protected $modelCmsBuilder;
    protected $instance;
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
       
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rawFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->modelCmsPageFactory = $this->getMockBuilder(CmsPageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
        ->setMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->modelCmsPage = $this->getMockBuilder(FedexCmsPage::class)
                ->setMethods(['getData','getCollection'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
                ->setMethods(['create','addFieldToFilter','addAttributeToSelect'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->modelCmsBlockFactory = $this->getMockBuilder(CmsBlockFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockCms = $this->getMockBuilder(\Magento\Cms\Model\Block::class)
                ->setMethods(['load', 'getCollection', 'create','getCatName'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->modelCmsBuilderFactory = $this->getMockBuilder(CmsBuilderFactory::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->modelCmsWidgetFactory = $this->getMockBuilder(CmsWidgetFactory::class)
                    ->disableOriginalConstructor()
                    ->getMock();
       
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->fileFactory = $this->getMockBuilder(FileFactory::class)
                    ->disableOriginalConstructor()
                    ->getMock();
        
        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
                    ->setMethods(['load','create','getById','getSku'])
                    ->disableOriginalConstructor()
                    ->getMock();
       
        $this->resultLayoutFactory = $this->getMockBuilder(LayoutFactory::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->csvProcessor = $this->getMockBuilder(Csv::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->pageRepositoryMock = $this->getMockBuilder(PageRepositoryInterface::class)
                    ->setMethods(['load','create','getById','getIdentifier'])
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->categoryRepository = $this->getMockBuilder(CategoryRepositoryInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
                    ->setMethods(['load','create'])
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->blockRepository = $this->getMockBuilder(BlockRepositoryInterface::class)
                    ->setMethods(['load','create','getBlockById','getIdentifier'])
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

        $this->helper = $this->getMockBuilder(\Fedex\CmsImportExport\Helper\Data::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->instanceFactory = $this->getMockBuilder(Instance::class)
            ->setMethods(['load'])
                    ->disableOriginalConstructor()
                    ->getMock();
        
        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->objLoggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info'])
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

        $this->collectionPage = $this->getMockBuilder(CmsPageCollection::class)
                ->setMethods(['addAttributeToFilter','create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->resultRedirectFactory = $this->getMockBuilder(RedirectView::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setRefererOrBaseUrl'])
            ->getMock();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryRead','create','isFile','readFile'])
            ->getMock();

        $this->fileDriver = $this->getMockBuilder(File::class)
                ->disableOriginalConstructor()
                ->setMethods(['isExists'])
                ->getMock();
     
        $this->controller = $this->objectManager->getObject(Export::class, [
                'pageFactory'              => $this->rawFactory,
                'modelCmsPageFactory'     => $this->modelCmsPageFactory,
                'modelCmsBlockFactory'    => $this->modelCmsBlockFactory,
                'modelCmsWidgetFactory'   => $this->modelCmsWidgetFactory,
                'modelCmsBuilderFactory'  => $this->modelCmsBuilderFactory,
                'searchCriteriaBuilder'    => $this->searchCriteriaBuilder,
                'fileFactory'              => $this->fileFactory,
                'productRepository'       => $this->productRepository,
                'storeManager'            => $this->storeManager,
                'resultLayoutFactory'      => $this->resultLayoutFactory,
                'csvProcessor'             => $this->csvProcessor,
                'directoryList'            => $this->directoryList,
                'pageRepository'           => $this->pageRepositoryMock,
                'categoryRepository'       => $this->categoryRepository,
                'categoryFactory'         => $this->categoryFactory,
                'blockRepository'          => $this->blockRepository,
                'messageManager'          => $this->messageManager,
                'resultRedirectFactory'    => $this->resultRedirectFactory,
                'collectionFactory'        => $this->collectionFactory,
                'helperData'               => $this->helper,
                'instanceFactory'          => $this->instanceFactory,
                'collectionFactory'        => $this->collectionFactory,
                '_request'                 => $this->requestMock,
                'logger'                  => $this->objLoggerInterface,
                'filesystem'               => $this->filesystem,
                'fileDriver'               => $this->fileDriver
            ]);

        $this->category = $this->getMockBuilder(Category::class)
                    ->setMethods(['load'])
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->product = $this->getMockBuilder(Product::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->page = $this->getMockBuilder(cmsPage::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->block = $this->getMockBuilder(Block::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->modelCmsBlock = $this->getMockBuilder(CmsBlockData::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getData','getCollection'])
                                ->getMock();

        $this->modelCmsBuilder = $this->getMockBuilder(CmsBuilderData::class)
                                ->setMethods(['getData','getCollection'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->instance = $this->getMockBuilder(Instance::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['getCollection', 'setContent','setStores',
                            'setTitle','setIsActive','save','addFieldToFilter',
                            'load','setPageSize'])
                            ->getMock();
    }

    /**
     * Test execute function
     */
    public function testExecute()
    {
        $postData = [
            "content-id" => "1,2",
            "content-block-id" => "1,2",
            "content-template-id" => "1,2",
            "content-widget-id" => "1,2"
            ];

        $this->requestMock->expects($this->any())->method('getPostValue')
            ->willReturn($postData);

        $pageCollection = $this
            ->getMockBuilder(CmsPageData::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $this->modelCmsPageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->modelCmsPage);

        $this->modelCmsPage->expects($this->any())->method('getCollection')
            ->willReturn($pageCollection);
    
        $pageCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturn($this->modelCmsPage);

        $pageData = [[
            "content" => "Test",
            "title" => "Test",
            "identifier" => "Test",
            "is_active" => "1",
            "code" => "Test",
            "content_heading" => "Test",
            "meta_title" => "Test",
            "meta_keywords" => "Test",
            "meta_description" => "Test",
            "layout_update_xml" => "Test",
            "custom_theme" => "Test",
            "custom_root_template" => "Test",
            "custom_theme_from" => "Test",
            "custom_theme_to" => "Test",
            "page_layout" => "Test",
            "pageIdentifier" => "Test",
            "blockIdentifier" => "Test",
            "catName" => "Test",
            "Sku" => "Test"
            ]];
    
        $this->modelCmsPage->expects($this->any())->method('getData')->willReturn($pageData);

        $blockCollection = $this
            ->getMockBuilder(CmsBlockModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $this->modelCmsBlockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->modelCmsBlock);

        $this->modelCmsBlock->expects($this->any())->method('getCollection')
            ->willReturn($blockCollection);

        $blockCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturn($this->modelCmsBlock);

        $blockData = [[
            "content" => "Test",
            "title" => "Test",
            "identifier" => "Test",
            "is_active" => 1,
            "code" => "Test",
            "pageIdentifier" => "test",
            "blockIdentifier" => "test",
            "catName" => "Test",
            "Sku" => "Test"
            ]];
        
        $this->modelCmsBlock->expects($this->any())->method('getData')
            ->willReturn($blockData);
    
        $builderCollection = $this
            ->getMockBuilder(CmsBuilderModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $this->modelCmsBuilderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->modelCmsBuilder);

        $this->modelCmsBuilder->expects($this->any())->method('getCollection')
            ->willReturn($builderCollection);

        $builderCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturn($this->modelCmsBuilder);

        $builderData = [[
            "template" => "Test",
            "name" => "Test",
            "preview_image" => "Test",
            "created_for" => "Page",
            "pageIdentifier" => "Test",
            "blockIdentifier" => "Test",
            "catName" => "Test",
            "Sku" => "Test"
            ]];

        $this->modelCmsBuilder->expects($this->any())->method('getData')
            ->willReturn($builderData);

        $this->instanceFactory->expects($this->any())
            ->method('load')
            ->willReturn($this->instance);

        $this->csvProcessor->expects($this->any())
            ->method('setEnclosure')
            ->willReturnSelf();

        $this->csvProcessor->expects($this->any())
            ->method('setDelimiter')
            ->willReturnSelf();

        $this->csvProcessor->expects($this->any())
            ->method('saveData')
            ->willReturnSelf();
    
        $this->resultRedirectFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
    
        $this->resultRedirectFactory->expects($this->any())
            ->method('setRefererOrBaseUrl')
            ->willReturnSelf();

        $this->fileDriver->expects($this->any())
            ->method('isExists')
            ->willReturnSelf();

        $this->filesystem->expects($this->any())
        ->method('getDirectoryRead')
        ->willReturnSelf();

        $this->filesystem->expects($this->any())
        ->method('isFile')
        ->willReturn('r.csv');

        $this->controller->execute();
    }

    /**
     * Test getProSku function
     */
    public function testgetProSku()
    {
        $id = 10;
        $responseContent = 'Test';
        $this->productRepository->expects($this->any())->method('create')->willReturnSelf();
        $this->productRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->productRepository->expects($this->any())->method('getSku')->willReturn('Test');
        $this->assertSame($responseContent, $this->controller->getProSku($id));
    }

    /**
     * Test testgetProSkuWithException function
     */
    public function testgetProSkuWithException()
    {
        $id = 10;
        $responseContent = 'Test';
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->productRepository->expects($this->any())->method('create')->willReturnSelf();
        $this->productRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->productRepository->expects($this->any())->method('getSku')->willThrowException($exception);
        $this->assertSame(null, $this->controller->getProSku($id));
    }

    /**
     * Test getCatName function
     */
    public function testgetCatName()
    {
        $content = 10;
        $response = null;
        $id = 100;
        $this->categoryFactory->expects($this->any())->method('create')
        ->willReturn($this->blockCms);
        $this->blockCms->expects($this->any())->method('load')->willReturnSelf();
        $this->assertSame($response, $this->controller->getCatName($id));
    }

    /**
     * Test testgetCatNameWithException function
     */
    public function testgetCatNameWithException()
    {
        $content = 10;
        $response = null;
        $id = 100;
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->categoryFactory->expects($this->any())->method('create')
        ->willReturn($this->blockCms);
        $this->blockCms->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertSame(null, $this->controller->getCatName($id));
    }

    /**
     * Test getPageById function
     */
    public function testgetPageById()
    {
        $id = 10;
        $responseContent = 'Test';
        $response = null;
        $this->pageRepositoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->pageRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();
        $this->pageRepositoryMock->expects($this->any())->method('getIdentifier')
             ->willReturn('Test');
        $this->assertSame($responseContent, $this->controller->getPageById($id));
    }

    /**
     * Test testgetPageByIdWithException function
     */
    public function testgetPageByIdWithException()
    {
        $id = 10;
        $responseContent = 'Test';
        $response = null;
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->pageRepositoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->pageRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();
        $this->pageRepositoryMock->expects($this->any())->method('getIdentifier')
                ->willThrowException($exception);;
        $this->assertSame(null, $this->controller->getPageById($id));
    }

    /**
     * Test getBlockById function
     */
    public function testgetBlockById()
    {
        $id = 100;
        $responseContent = 'Test';
        $this->blockRepository->expects($this->any())->method('create')->willReturnSelf();
        $this->blockRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->blockRepository->expects($this->any())->method('getIdentifier')
            ->willReturn('Test');
        $this->assertSame($responseContent, $this->controller->getBlockById($id));
    }

    /**
     * Test testgetBlockByIdWithException function
     */
    public function testgetBlockByIdWithException()
    {
        $id = 100;
        $responseContent = 'Test';
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->blockRepository->expects($this->any())->method('create')->willReturnSelf();
        $this->blockRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->blockRepository->expects($this->any())->method('getIdentifier')
                ->willThrowException($exception);;
        $this->assertSame(null, $this->controller->getBlockById($id));
    }

    /**
     * Test getProductsById function
     */
    public function testgetProductsById()
    {
        $ids = '1';
        $Result = '';
        $productModel = $this->getMockBuilder(CatalogModel::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['load', 'addFieldToFilter', 'addAttributeToSelect'])
                        ->getMock();
        $this->collectionFactory->expects($this->any())->method('create')
            ->willReturn($productModel);
        $productModel->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $productModel->expects($this->any())->method('addAttributeToSelect')->with("sku")
            ->willReturn($productModel);
        $productModel->expects($this->any())->method('load')->willReturnSelf();
        $this->assertSame($Result, $this->controller->getProductsById($ids));
    }

    /**
     * Test testgetProductsByIdWithException function
     */
    public function testgetProductsByIdWithException()
    {
        $ids = '1';
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $productModel = $this->getMockBuilder(CatalogModel::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['load', 'addFieldToFilter', 'addAttributeToSelect'])
                        ->getMock();                        
        $this->collectionFactory->expects($this->any())->method('create')
            ->willReturn($productModel);
        $productModel->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $productModel->expects($this->any())->method('addAttributeToSelect')->with("sku")
            ->willReturn($productModel);
        $productModel->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertSame(null, $this->controller->getProductsById($ids));
    }

    /**
     * Test getCategoryNameById function
     */
    public function testGetCategoryNameById()
    {
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" template="widget/static_block/
        default.phtml" id_path="category/2" type_name="CMS Static Block"'];
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturnSelf();

        $this->assertNotNull($this->controller
            ->getCategoryNameById($widgetContent));
    }

    /**
     * Test testGetCategoryNameByIdWithException function
     */
    public function testGetCategoryNameByIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" template="widget/static_block/
        default.phtml" id_path="category/2" type_name="CMS Static Block"'];
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->any())->method('load')
            ->willThrowException($exception);
    
        $this->assertEquals(null, $this->controller
            ->getCategoryNameById($widgetContent));
    }
   
    /**
     * Test getProductSKUById function
     */
    public function testGetProductSKUById()
    {
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" template="widget/static_block/
        default.phtml" id_path="product/2" type_name="CMS Static Block"'];
        $this->productRepository->expects($this->any())->method('getById')
            ->willReturn($this->product);
        $this->objLoggerInterface->expects($this->any())->method('info');
    
        $expectedResult = '2" type_name="CMS Static Block=>';
        $this->assertEquals($expectedResult, $this->controller
            ->getProductSKUById($widgetContent));
    }

    /**
     * Test testGetProductSKUByIdWithException function
     */
    public function testGetProductSKUByIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" template="widget/static_block/
        default.phtml" id_path="product/2" type_name="CMS Static Block"'];
        $this->productRepository->expects($this->any())->method('getById')
                ->willThrowException($exception);
        $this->assertEquals(null, $this->controller
            ->getProductSKUById($widgetContent));
    }

    /**
     * Test getPageIdentifierById function
     */
    public function testGetPageIdentifierById()
    {
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" template="widget/static_block/
        default.phtml" page_id="1" type_name="CMS Static Block"'];
        $this->pageRepositoryMock->expects($this->any())->method('getById')
            ->willReturn($this->page);
        $this->objLoggerInterface->expects($this->any())->method('info');
        $this->controller->getPageIdentifierById($widgetContent);
        $expectedResult = '1" type_name="CMS Static Block=>';
        $this->assertEquals($expectedResult, $this->controller
            ->getPageIdentifierById($widgetContent));
    }

    /**
     * Test testGetPageIdentifierByIdWithException function
     */
    public function testGetPageIdentifierByIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" template="widget/static_block/
        default.phtml" page_id="1" type_name="CMS Static Block"'];
        $this->pageRepositoryMock->expects($this->any())->method('getById')
                ->willThrowException($exception);
        $this->controller->getPageIdentifierById($widgetContent);
        $this->assertEquals(null, $this->controller
            ->getPageIdentifierById($widgetContent));
    }

    /**
     * Test getBlockIdentifierById function
     */
    public function testGetBlockIdentifierById()
    {
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" 
        template="widget/static_block/default.phtml" block_id="1" 
        type_name="CMS Static Block"'];
        $this->blockRepository->expects($this->any())->method('getById')
            ->willReturn($this->block);
        $this->objLoggerInterface->expects($this->any())->method('info');

        $expectedResult = '1=>';
        $this->assertEquals($expectedResult, $this->controller->getBlockIdentifierById($widgetContent));
    }

    /**
     * Test testGetBlockIdentifierByIdWithException function
     */
    public function testGetBlockIdentifierByIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $widgetContent = ['"Magento\Cms\Block\Widget\Block" 
        template="widget/static_block/default.phtml" block_id="1" 
        type_name="CMS Static Block"'];
        $this->blockRepository->expects($this->any())->method('getById')
                ->willThrowException($exception);
        $this->assertEquals(null, $this->controller->getBlockIdentifierById($widgetContent));
    }

    /**
     * Test getPageGroup function
     */
    public function testPageGroup()
    {
        $pageGroups = [
            [ "page_id" => 2,
            "instance_id" => 2,
            "page_group" => "all_pages",
            "layout_handle" => "default",
            "block_reference" => "content",
            "page_for" => "all",
            "entities" => "",
            "page_template" => "widget/static_block/default.phtml" ]
            ];
       $this->assertEquals(null, $this->assertIsArray($this->controller->getPageGroup($pageGroups)));
    }
    
    /**
     * Test getPageGroup if entity exist function
     */
    public function testPageGroupEntityExist()
    {
        $pageGroups = [
            [ "page_id" => 2,
            "instance_id" => 2,
            "page_group" => "all_pages",
            "layout_handle" => "default",
            "block_reference" => "content",
            "page_for" => "all",
            "entities" => "1,2",
            "page_template" => "widget/static_block/default.phtml" ]
            ];
        $this->assertEquals(null, $this->assertIsArray($this->controller->getPageGroup($pageGroups)));
    }
     
    /**
     * Test getPageGroup if entity exist function
     */
    public function testPageGroupWithLayoutLayered()
    {
        $pageGroups = [
                [ 
                    "page_id" => 2,
                    "instance_id" => 2,
                    "page_group" => "all_pages",
                    "layout_handle" => "catalog_category_view_type_layered",
                    "block_reference" => "content",
                    "page_for" => "all",
                    "entities" => "1,2",
                    "page_template" => "widget/static_block/default.phtml" 
                ]
            ];

        $categoryId = "1,2";
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturnSelf();
        $this->assertEquals(null, $this->assertIsArray($this->controller->getPageGroup($pageGroups)));
    }

    /**
     * Test getContents function
     */
    public function testGetContents()
    {
        $string = '{{widget type="Magento\Cms\Block\Widget\Block" 
        template="widget/static_block/default.phtml" 
        block_id="30" type_name="CMS Static Block"}}';
        $startData = "{{widget type=";
        $endData = "}}";
        $response[] = '"Magento\Cms\Block\Widget\Block" 
        template="widget/static_block/default.phtml" 
        block_id="30" type_name="CMS Static Block"';
        $this->assertEquals($response, $this->controller->getContents($string, $startData, $endData));
    }

    /**
     * Test getCSVDataByTemplate function
     */
    public function testGetCSVDataByTemplate()
    {
        $arrResonceData =[
            'Sku'               => "",
            'catName'           => "",
            'pageIdentifier'    => "",
            'blockIdentifier'   => ""
        ];
        $stringTemplateDetails = '{{widget type="Magento\Cms\Block\Widget\Block" 
        template="widget/static_block/default.phtml" block_ids="30" 
        type_name="CMS Static Block"}}';


        $this->assertNotNull($this->controller->getCSVDataByTemplate($stringTemplateDetails));
    }

    /**
     * Test getCategoriesName function
     */
    public function testGetCategoriesName()
    {
        $categoryId = "1,2";
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturnSelf();
        $this->assertEquals('|', $this->controller->getCategoriesName($categoryId));
    }

    /**
     * Test testGetCategoriesNameWithException function
     */
    public function testGetCategoriesNameWithException()
    {
        $categoryId = "1,2";
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertEquals(null, $this->controller->getCategoriesName($categoryId));
    }
}
