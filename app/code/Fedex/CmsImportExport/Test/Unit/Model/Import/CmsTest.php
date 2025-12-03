<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Model\Import;

use Fedex\CmsImportExport\Model\Import\Cms;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\Block;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use \Fedex\CmsImportExport\Helper\Data;
use Magento\PageBuilder\Model\TemplateFactory;
use Magento\PageBuilder\Model\Template;
use Magento\Widget\Model\Widget\InstanceFactory;
use Magento\Widget\Model\Widget\Instance;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Cms\Model\ResourceModel\Block\Collection as CmsBlock;
use Magento\PageBuilder\Model\ResourceModel\Template\Collection as CmsTemplateModel;
use Magento\Cms\Model\ResourceModel\Page\Collection as CmsPageModel;
use Magento\MediaStorage\Model\File\Uploader as FileDataModel;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * Test class for Fedex\Shipment\Helper\ShipmentEmail
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CmsTest extends TestCase
{
    protected $uploader;
    protected $helperData;
    protected $requestMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $block;
    protected $instance;
    /**
     * @var LocalizedException|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localizedException;
    
    /**
     * @var Cms|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cms;
    
    /**
     * @var UploaderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $uploaderFactory;
    
    /**
     * @var BlockFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $blockFactory;

    /**
     * @var PageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pageFactory;

    /**
     * @var Page|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $page;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loggerInterface;

    /**
     * @var ResultFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultFactory;

    /**
     * @var TemplateFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $templateFactory;

    /**
     * @var Template|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $template;

    /**
     * @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driverInterface;

    /**
     * @var InstanceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $instanceFactory;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $data;

    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $managerInterface;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->localizedException = $this
            ->getMockBuilder(LocalizedException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->uploaderFactory = $this->getMockBuilder(UploaderFactory::class)
        ->setMethods(['create','setAllowCreateFolders','setAllowedExtensions',
            'setAllowRenameFiles','checkAllowedExtension','getFileExtension',
            'save','getUploadedFileName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->uploader = $this
                ->getMockBuilder(FileDataModel::class)
                ->setMethods(['create','setAllowCreateFolders','setAllowedExtensions',
                'setAllowRenameFiles','checkAllowedExtension','getFileExtension',
                'save','getUploadedFileName'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->blockFactory = $this->getMockBuilder(BlockFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
        ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->templateFactory = $this->getMockBuilder(TemplateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instanceFactory = $this->getMockBuilder(InstanceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->driverInterface = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(HttpRequest::class)
                ->disableOriginalConstructor()
                ->setMethods(['getPostValue','isPost','getFiles'])
                ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->cms = $this->objectManager->getObject(
            Cms::class,
            [
                'localizedException' => $this->localizedException,
                'uploaderFactory' => $this->uploaderFactory,
                'blockFactory' => $this->blockFactory,
                'pageFactory' => $this->pageFactory,
                'loggerInterface' => $this->loggerInterface,
                'resultFactory' => $this->resultFactory,
                'managerInterface' => $this->managerInterface,
                'helper' => $this->helperData,
                'templateFactory' => $this->templateFactory,
                'instanceFactory' => $this->instanceFactory,
                'driverInterface' => $this->driverInterface,
            'request' => $this->requestMock
            ]
        );

        $this->template = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'setPageSize','setTemplate',
            'setCreatedFor', 'setPreviewImage','save'])
            ->getMock();

        $this->page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'setContent','setStores','setTitle',
            'setIsActive','setContentHeading','setMetaTitle',
            'setMetaKeywords', 'setMetaDescription', 'setLayoutUpdateXml',
            'setCustomTheme', 'setCustomRootTemplate',
            'setCustomThemeFrom', 'setCustomThemeTo','setPageLayout','save'])
            ->getMock();

        $this->block = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'setContent','setStores','setTitle',
            'setIsActive','save'])
            ->getMock();

        $this->instance = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'setContent','setStores','setTitle',
            'setIsActive','save','addFieldToFilter','load','setPageSize'])
            ->getMock();
    }

    /**
     * To import CMS Data in database and count Blocks, Pages, Templates and Widgets
     *
     */
    public function testSaveData()
    {
        $response = null;
        $fileUploadData = [
            "name" => "2021_09_06_07_18_14 cms_content_export.csv",
            "type" => "text/csv",
            "tmp_name" => "/tmp/phpeqqWtn",
            "error" => "0",
            "size" => '8504'
        ];

        $csvData = [
            "stores" => "1",
            "block_id_identifier" => "block_id_identifier",
            "content" => "content",
            "page_id_identifier" => "page_id_identifier",
            "category_id_name" => 'category_id_name',
            "product_id_sku" => 'product_id_sku',
            "type" => 'cms_blocks'
        ];
        
        $this->helperData->expects($this->any())->method('getDestinationPath')
             ->willReturn("var/cms");
        $this->requestMock->expects($this->any())->method('getFiles')
             ->willReturn($fileUploadData);
        $this->driverInterface->expects($this->any())->method('isExists')
             ->willReturn($fileUploadData['name']);
        $this->helperData->expects($this->any())->method('convertCsvToArray')
             ->willReturn([$csvData]);
        $this->uploaderFactory->expects($this->any())->method('create')
             ->willReturn($this->uploader);
        $this->uploader->expects($this->any())->method('setAllowCreateFolders')
             ->willReturnSelf();
        $this->uploader->expects($this->any())->method('setAllowedExtensions')
             ->willReturnSelf();
        $this->uploader->expects($this->any())->method('setAllowRenameFiles')
             ->willReturnSelf();
        $this->uploader->expects($this->any())->method('getFileExtension')
             ->willReturn(['csv']);
        $this->assertSame($response, $this->cms->saveData());
    }

    /**
     * To import CMS Data in database and count Blocks, Pages, Templates and Widgets
     *
     */
    public function testSaveDataWithException()
    {
        $response = null;
        $fileUploadData = [
            "name" => "2021_09_06_07_18_14 cms_content_export.csv",
            "type" => "text/csv",
            "tmp_name" => "/tmp/phpeqqWtn",
            "error" => "0",
            "size" => '8504'
        ];

        $csvData = [
            "stores" => "1",
            "block_id_identifier" => "block_id_identifier",
            "content" => "content",
            "page_id_identifier" => "page_id_identifier",
            "category_id_name" => 'category_id_name',
            "product_id_sku" => 'product_id_sku',
            "type" => 'cms_blocks'
        ];

        $uploadedFile = "/var/www/html/shop-staging2.fedex.com/var/cms/filedata.csv";
        $this->helperData->expects($this->any())->method('getDestinationPath')
             ->willReturn("var/cms");
        $this->requestMock->expects($this->any())->method('getFiles')
             ->willReturn($fileUploadData);
        //$this->driverInterface->expects($this->any())->method('isExists')->willReturn($uploadedFile);
        $this->helperData->expects($this->any())->method('convertCsvToArray')
             ->willReturn([$csvData]);
        $this->uploaderFactory->expects($this->any())->method('create')
             ->willReturn($this->uploader);
        $this->uploader->expects($this->any())->method('setAllowCreateFolders')
            ->willReturnSelf();
        $this->uploader->expects($this->any())->method('setAllowedExtensions')
            ->willReturnSelf();
        $this->uploader->expects($this->any())->method('setAllowRenameFiles')
            ->willReturnSelf();
        $this->uploader->expects($this->any())->method('getFileExtension')
            ->willReturn(['csv']);
        $this->assertSame($response, $this->cms->saveData());
    }

    /**
     * Test getWidgetParametersUpdate for block identifier function
     */
    public function testGetWidgetParametersUpdateBlockIdentifier()
    {
        $data = [
            "widget_parameters" => "1",
            "block_id_identifier" => "test"
        ];
        $this->assertEquals(null, $this->cms->getWidgetParametersUpdate($data));
    }

    /**
     * Test getWidgetParametersUpdate for page identifier function
     */
    public function testGetWidgetParametersUpdatePageIdentifier()
    {
        $data = [
            "widget_parameters" => "1",
            "page_id_identifier" => "test"
        ];
        $this->assertEquals(null, $this->cms->getWidgetParametersUpdate($data));
    }

    /**
     * Test getWidgetParametersUpdate for category id name function
     */
    public function testGetWidgetParametersUpdateCategoryIdentifier()
    {
        $data = [
            "widget_parameters" => "1",
            "category_id_name" => "test"
        ];
        $this->assertEquals(null, $this->cms->getWidgetParametersUpdate($data));
    }

    /**
     * Test getWidgetParametersUpdate for product identifier function
     */
    public function testGetWidgetParametersUpdateProductIdentifier()
    {
        $data = [
            "widget_parameters" => "1",
            "product_id_sku" => "test"
        ];
        $this->assertEquals(null, $this->cms->getWidgetParametersUpdate($data));
    }

    /**
     * Test getWidgetEntitiesUpdate for product page group function
     */
    public function testGetWidgetEntitiesUpdateProduct()
    {
        $data = [
            ["page_group" => "products",
            "all_pages" => ["for"=>"specific", "entities"=>"1"]]
        ];

        $this->assertIsArray($this->cms->getWidgetEntitiesUpdate($data));
    }

    /**
     * Test getWidgetEntitiesUpdate for categories page group function
     */
    public function testGetWidgetEntitiesUpdateCategory()
    {
        $data = [
            ["page_group" => "categories",
            "all_pages" => ["for"=>"specific", "entities"=>"1"]]
        ];

        $this->assertIsArray($this->cms->getWidgetEntitiesUpdate($data));
    }

    /**
     * Test importTemplate if template not exist function
     */
    public function testImportTemplateNotExist()
    {
        $data = ["name"=>"Homepage Template"];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];
    
        $templateCollection = $this
            ->getMockBuilder(CmsTemplateModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();
        $templateItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->templateFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->template);
        $this->template->expects($this->any())->method('getCollection')
            ->willReturn($templateCollection);
        $templateCollection->expects($this->any())->method('addFieldToFilter')
            ->with("name", "Homepage Template")->willReturnSelf();
        $templateCollection->expects($this->any())->method('load')
            ->willReturn($this->template);
        $values = [$this->template];
        $this->template->expects($this->any())->method('setPageSize')
            ->willReturn([]);
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>2,"update"=>1];
        $this->assertEquals($expectedResult, $this->cms
            ->importTemplate($rowNum, $data, $templateUpdateData));
    }

    /**
     * Test importTemplate if template exist function
     */
    public function testImportTemplateExist()
    {
        $data = ["name"=>"Homepage Template","template"=>"test",
             "created_for"=>"test","preview_image"=>"test"];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];
    
        $templateCollection = $this
            ->getMockBuilder(CmsTemplateModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $templateItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->templateFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->template);
        $this->template->expects($this->any())->method('getCollection')
            ->willReturn($templateCollection);
        $templateCollection->expects($this->any())->method('addFieldToFilter')
            ->with("name", "Homepage Template")->willReturnSelf();
        $templateCollection->expects($this->any())->method('load')
            ->willReturn($this->template);
        $values = [$this->template];
        $this->template->expects($this->any())->method('setPageSize')
            ->willReturn([$this->template]);
        $this->template->expects($this->any())->method('setTemplate')
            ->willReturnSelf();
        $this->template->expects($this->any())->method('setCreatedFor')
            ->willReturnSelf();
        $this->template->expects($this->any())->method('setPreviewImage')
            ->willReturnSelf();
        $this->template->expects($this->any())->method('save')->willReturnSelf();
        $this->loggerInterface->expects($this->any())->method('info');
        $this->cms->importTemplate($rowNum, $data, $templateUpdateData);
        $expectedResult = ["new"=>1,"update"=>2];
        $this->assertEquals($expectedResult, $this->cms
            ->importTemplate($rowNum, $data, $templateUpdateData));
    }

    /**
     * Test importTemplate if exception function
     */
    public function testImportTemplateException()
    {
        $data = ["name"=>"Homepage Template","template"=>"test",
             "created_for"=>"test","preview_image"=>"test"];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];
    
        $templateCollection = $this
            ->getMockBuilder(CmsTemplateModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();
        
        $this->templateFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->template);
        $this->template->expects($this->any())->method('getCollection')
            ->willReturn($templateCollection);
        $templateCollection->expects($this->any())->method('addFieldToFilter')
            ->with("name", "Homepage Template")->willReturnSelf();
        $templateCollection->expects($this->any())->method('load')
            ->willReturn($this->template);
        $values = [$this->template];
        $this->template->expects($this->any())->method('setPageSize')
             ->willReturn([$this->template]);
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>1, "update"=>2];
        $this->assertEquals($expectedResult, $this->cms
            ->importTemplate($rowNum, $data, $templateUpdateData));
    }

    /**
     * Test importPage if page exist function
     */
    public function testImportPageExist()
    {
        $data = [
            "content" => "Test",
            "identifier" => "Test",
            "stores" => 1,
            "title" => "Test",
            "is_active" => 1,
            "content_heading" => "Test",
            "meta_title" => "Test",
            "meta_keywords" => "Test",
            "meta_description" => "Test",
            "layout_update_xml" => "1",
            "custom_theme" => "Test",
            "custom_root_template" => "Test",
            "custom_theme_from" => "Test",
            "custom_theme_to" => "Test",
            "page_layout" => "Test"
        ];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];

        $pageCollection = $this
            ->getMockBuilder(CmsPageModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize', 'getData'])
            ->getMock();
        $this->pageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->page);
        $this->page->expects($this->any())->method('getCollection')
            ->willReturn($pageCollection);

        $pageCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(['identifier', 'test'], ['store_id',1])
            ->willReturnSelf();

        $pageCollection->expects($this->any())->method('load')->willReturn([$this->page]);
        $pageCollection->expects($this->any())->method('getData')->willReturn("['0' => ['id' => 1]]");
        $this->page->expects($this->any())->method('setContent')->willReturnSelf();
        $this->page->expects($this->any())->method('setStores')->willReturnSelf();
        $this->page->expects($this->any())->method('setTitle')->willReturnSelf();
        $this->page->expects($this->any())->method('setIsActive')->willReturnSelf();
        $this->page->expects($this->any())->method('setContentHeading')->willReturnSelf();
        $this->page->expects($this->any())->method('setMetaTitle')->willReturnSelf();
        $this->page->expects($this->any())->method('setMetaKeywords')->willReturnSelf();
        $this->page->expects($this->any())->method('setMetaDescription')->willReturnSelf();
        $this->page->expects($this->any())->method('setLayoutUpdateXml')->willReturnSelf();
        $this->page->expects($this->any())->method('setCustomTheme')->willReturnSelf();
        $this->page->expects($this->any())->method('setCustomRootTemplate')->willReturnSelf();
        $this->page->expects($this->any())->method('setCustomThemeFrom')->willReturnSelf();
        $this->page->expects($this->any())->method('setCustomThemeTo')->willReturnSelf();
        $this->page->expects($this->any())->method('setPageLayout')->willReturnSelf();
        $this->page->expects($this->any())->method('save')->willReturnSelf();
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>1,"update"=>2];

        $this->assertEquals(
            $expectedResult,
            $this->cms->importPage($rowNum, $data, $templateUpdateData)
        );
    }

    /**
     * Test importPage if page not exist function
     */
    public function testImportPageNotExist()
    {
        $data = [
            "content" => "Test",
            "identifier" => "Test",
            "stores" => 1,
            "title" => "Test",
            "is_active" => 1,
            "content_heading" => "Test",
            "meta_title" => "Test",
            "meta_keywords" => "Test",
            "meta_description" => "Test",
            "layout_update_xml" => "1",
            "custom_theme" => "Test",
            "custom_root_template" => "Test",
            "custom_theme_from" => "Test",
            "custom_theme_to" => "Test",
            "page_layout" => "Test"
        ];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];

        $pageCollection = $this
            ->getMockBuilder(CmsPageModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $this->pageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->page);
        $this->page->expects($this->any())->method('getCollection')
            ->willReturn($pageCollection);

        $pageCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(['identifier', 'test'], ['store_id',1])
            ->willReturnSelf();
        $pageCollection->expects($this->any())->method('load')->willReturn([]);
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>2,"update"=>1];
        $this->assertEquals($expectedResult, $this->cms
             ->importPage($rowNum, $data, $templateUpdateData));
    }

    /**
     * Test importPage if exception function
     */
    public function testImportPageException()
    {
        $data = [
            "content" => "Test",
            "identifier" => "Test",
            "stores" => 1,
            "title" => "Test",
            "is_active" => 1,
            "content_heading" => "Test",
            "meta_title" => "Test",
            "meta_keywords" => "Test",
            "meta_description" => "Test",
            "layout_update_xml" => "1",
            "custom_theme" => "Test",
            "custom_root_template" => "Test",
            "custom_theme_from" => "Test",
            "custom_theme_to" => "Test",
            "page_layout" => "Test"
        ];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];

        $pageCollection = $this
            ->getMockBuilder(CmsPageModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize', 'getData'])
            ->getMock();

        $this->pageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->page);
        $this->page->expects($this->any())->method('getCollection')
            ->willReturn($pageCollection);
        $pageCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(["identifier", "test"],["store_id", "1"])->willReturnSelf();

        $pageCollection->expects($this->any())->method('load')
            ->willReturn([$this->page]);
        $pageCollection->expects($this->any())->method('getData')->willReturn("['0' => ['id' => 1]]");
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>1, "update"=>2];
        $this->assertEquals($expectedResult, $this->cms
             ->importPage($rowNum, $data, $templateUpdateData));
    }

    /**
     * Test importBlock if block exist function
     */
    public function testImportBlockExist()
    {
        $data = [
            "identifier" => "Test",
            "store_id" => 1,
            "content" => "Test",
            "stores" => 1,
            "title" => "Test",
            "is_active" => 1
        ];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];
        $blockCollection = $this
            ->getMockBuilder(CmsBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $this->blockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->block);

        $this->block->expects($this->any())->method('getCollection')
            ->willReturn($blockCollection);

        $blockCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(["identifier", "Test"],["store_id", "1"])->willReturnSelf();

        $blockCollection->expects($this->any())->method('load')
            ->willReturn([$this->block]);
        $this->block->expects($this->any())->method('setContent')
            ->willReturnSelf();
        $this->block->expects($this->any())->method('setStores')
            ->willReturnSelf();
        $this->block->expects($this->any())->method('setTitle')
            ->willReturnSelf();
        $this->block->expects($this->any())->method('setIsActive')
            ->willReturnSelf();
        $this->block->expects($this->any())->method('save')
            ->willReturnSelf();
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>1,"update"=>2];

        $this->assertEquals($expectedResult, $this->cms
            ->importBlock($rowNum, $data, $templateUpdateData));
    }
    
    /**
     * Test importBlock with exception function
     */
    public function testImportBlockNotExist()
    {
        $data = [
            "identifier" => "Test",
            "store_id" => 1,
            "content" => "Test",
            "stores" => 1,
            "title" => "Test",
            "is_active" => 1
        ];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];
        
        $blockCollection = $this
            ->getMockBuilder(CmsBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize'])
            ->getMock();

        $this->blockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->block);

        $this->block->expects($this->any())->method('getCollection')
            ->willReturn($blockCollection);

        $blockCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(["identifier", "Test"],["store_id", "1"])->willReturnSelf();
        $blockCollection->expects($this->any())->method('load')->willReturn([]);
        $this->block->expects($this->any())->method('save')->willReturnSelf();
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>2,"update"=>1];
        $this->assertEquals($expectedResult, $this->cms
            ->importBlock($rowNum, $data, $templateUpdateData));
    }

    /**
     * Test importBlock if block not exist function
     */
    public function testImportBlockException()
    {
        $data = [
            "identifier" => "Test",
            "store_id" => 1,
            "content" => "Test",
            "stores" => 1,
            "title" => "Test",
            "is_active" => 1
        ];
        $rowNum = 1;
        $templateUpdateData = ["new"=>1,"update"=>1];
        $blockCollection = $this
            ->getMockBuilder(CmsBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setPageSize', 'getData'])
            ->getMock();
        $this->blockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->block);
        $this->block->expects($this->any())->method('getCollection')
            ->willReturn($blockCollection);
        $blockCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(["identifier", "Test"],["store_id", "1"])->willReturnSelf();
        $blockCollection->expects($this->any())->method('load')->willReturn([$this->block]);
        $blockCollection->expects($this->any())->method('getData')->willReturn("['0' => ['id' => 1]]");
        $this->loggerInterface->expects($this->any())->method('info');
        $expectedResult = ["new"=>1, "update"=>2];
        $this->assertEquals($expectedResult, $this->cms
            ->importBlock($rowNum, $data, $templateUpdateData));
    }

    /**
     * Import and update widget data in database
     */
    public function testImportWidgetWithData()
    {
        $data = [
            "stores" => '1',
            "title" => "Test",
            "theme_id" => "Test",
            "page_groups_json" => "{'id':'1'}",
            "widget_parameters" => "1",
            "block_id_identifier" => "Test",
            "instance_type" => "Test"
        ];

        $rowNum = 1;
        $widgetUpdateData = $response = 12;

        $this->instanceFactory->expects($this->any())->method('create')
            ->willReturn($this->instance);
        $this->instance->expects($this->any())->method('getCollection')
            ->willReturnSelf();
        $this->instance->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();
        $this->instance->expects($this->any())->method('setPageSize')
            ->willReturn([$this->instance]);
        $this->assertEquals($response, $this->cms
            ->importWidget($rowNum, $data, $widgetUpdateData));
    }

    /**
     * Import and update widget data in database
     */
    public function testImportWidgetWithoutData()
    {
        $data = [
            "stores" => '1',
            "title" => "Test",
            "theme_id" => "Test",
            "page_groups_json" => "{'id':'1'}",
            "widget_parameters" => "1",
            "block_id_identifier" => "Test",
            "instance_type" => "Test"
        ];

        $rowNum = 1;
        $widgetUpdateData = null;
        $response = null;

        $this->instanceFactory->expects($this->any())->method('create')
            ->willReturn($this->instance);
        $this->instance->expects($this->any())->method('getCollection')
            ->willReturnSelf();
        $this->instance->expects($this->any())->method('addFieldToFilter')
            ->willReturn($this->instance);
        $this->instance->expects($this->any())->method('setPageSize')
            ->willReturn([]);
        $this->assertEquals($response, $this->cms
            ->importWidget($rowNum, $data, $widgetUpdateData));
    }

    /**
     * Import and update widget data in database
     */
    public function testImportWidgetWithElseSection()
    {
        $data = [
            "stores" => null,
            "title" => "Test",
            "theme_id" => "Test",
            "page_groups_json" => null,
            "widget_parameters" => "{{id='1'}}",
            "block_id_identifier" => "=>Test|=>Test1",
            "instance_type" => "Test"
        ];

        $rowNum = 1;
        $widgetUpdateData = $response = 12;

        $this->instanceFactory->expects($this->any())->method('create')
            ->willReturn($this->instance);
        $this->instance->expects($this->any())->method('getCollection')
            ->willReturnSelf();
        $this->instance->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();
        $this->instance->expects($this->any())->method('setPageSize')
            ->willReturn([$this->instance]);
        $this->assertEquals($response, $this->cms
            ->importWidget($rowNum, $data, $widgetUpdateData));
    }
}
