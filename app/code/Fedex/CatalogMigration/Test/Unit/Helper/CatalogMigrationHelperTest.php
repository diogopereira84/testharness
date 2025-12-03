<?php
namespace Fedex\CatalogMigration\Helper;

use Magento\Framework\App\Helper\Context;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Fedex\CatalogMigration\Model\CatalogMigrationFactory;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Helper\Data;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Magento\Backend\Model\Auth\Session;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CatalogMigrationHelperTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogSyncQueueMock;
    /**
     * @var (\Fedex\CatalogMvp\Helper\CatalogMvp & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogMvp;
    protected $catalogSyncQueueFactoryMock;
    protected $companyRepositoryMock;
    protected $dataHelerMock;
    protected $collectionFactoryMock;
    protected $authSessionMock;
    protected $catalogSyncQueueProcessFactoryMock;
    /**
     * @var (\Magento\Catalog\Api\CategoryLinkManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryLinkManagementInterfaceMock;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Api\MessageInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageMock;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisherMock;
    protected $catalogMigrationFactoryMock;
    protected $catalogMigrationMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfacenMock;
    /**
     * @var (\Magento\Company\Api\Data\CompanyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyItem;
    /**
     * @var (\Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionMock;
    /**
     * @var (\Magento\User\Model\User & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $userMock;
    protected $productRepositoryMock;
    protected $customerRepositoryInterfaceMock;
    protected $mediaGalleryProcessorMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const STATUS_COMPLETED = 'completed';
    /**
     * @var CatalogMigrationHelper
     */
    private $catalogMigrationHelper;

    public const COMPANY_ID = 98;
    public const SHARED_CATALOG_ID = 9;
    public const STORE_ID = 1;
    public const CATALOG_SYNC_QUEUE_ID = 13;
    public const ROW_COLUMN_DATA_MULTIPLE_CATS = [
        '96338753-a78a-4a7c-b95a-97b4f2006271',
        'text n text',
        'desc',
        '0.31',
        'test',
        '{   "id" : 1696536412672,   "version" : 1,   "name" : "Catalog Product",   "qty" : 1,   "priceable" : true,   "features" : [ ],   "properties" : [ {     "id" : 1701448414260,     "name" : "CATALOG_PRODUCT_ID",     "value" : "96338753-a78a-4a7c-b95a-97b4f2006270"   }, {     "id" : 1696624628058,     "name" : "CATALOG_PRINT_OPTIONS",     "value" : "cutting : none, folding : none, color : black and white, printSides : single-sided, paperType : White (20 lb), paperSize : 8.5 x 11 (letter)"   }, {     "id" : 1696624643609,     "name" : "CATALOG_FINISHING_OPTIONS",     "value" : "drilling : none, frontCover : none, backCover : none, stapling : none, binding : none, collated : collated (sets)"   }, {     "id" : 1470151626854,     "name" : "SYSTEM_SI",     "value" : null   }, {     "id" : 1454950109636,     "name" : "USER_SPECIAL_INSTRUCTIONS",     "value" : null   } ],   "pageExceptions" : [ ],   "proofRequired" : false,   "instanceId" : 1708493315226,   "userProductName" : null,   "inserts" : [ ],   "exceptions" : [ ],   "addOns" : [ ],   "contentAssociations" : [ {     "parentContentReference" : "c851d3ef-d079-11ee-a21b-f5e2998b40de",     "contentReference" : "c851d3ef-d079-11ee-a21b-f5e2998b40de",     "contentReplacementUrl" : null,     "contentType" : "application/pdf",     "fileSizeBytes" : 0,     "fileName" : "text n text.pdf",     "printReady" : true,     "contentReqId" : 1483999952979,     "name" : null,     "desc" : null,     "purpose" : "MAIN_CONTENT",     "specialInstructions" : null,     "pageGroups" : [ {       "start" : 1,       "end" : 1,       "width" : 8.5,       "height" : 11.0,       "orientation" : null     } ],     "physicalContent" : false   } ],   "productionContentAssociations" : [ ],   "catalogReference" : null,   "products" : [ ],   "externalSkus" : [ {     "skuDescription" : null,     "skuRef" : null,     "code" : "0005",     "unitPrice" : null,     "price" : null,     "qty" : 1,     "applyProductQty" : true   } ],   "vendorReference" : null,   "isOutSourced" : false,   "contextKeys" : [ ],   "externalProductionDetails" : {     "weight" : {       "value" : 0.0136,       "units" : "LB"     },     "productionTime" : null   } }',
        'https://documentapitest.prod.fedex.com/document/fedexoffice/v2/documents/c851d3ef-d079-11ee-a21b-f5e2998b40de/previewpages/1',
        '/custom docs,/test',
        'TRUE',
        '{   "customizableFields" : [{"id":"MTMxMjA5Mzk5MzI=","name":"ImageField1","description":"aaaaa","mandatory":true,"sequence":1,"documentAssociations":[{"pageNumber":1,"documentId":"e92a2ec4-d079-11ee-a21b-39c1d630fc06"}],"inputType":"IMAGE","inputMethod":"SELECT","options":[{"imageValue":{"documentId":"facb6dbc-d079-11ee-a21b-37a9e3e36864","previewURL":"https://documentapitest.prod.fedex.com/document/fedexoffice/v2/documents/facb6dbc-d079-11ee-a21b-37a9e3e36864/previewpages/1","fileName":"image1"}}]},{"id":"MTMxMjA5Mzk5MzE=","name":"TextField1","description":"bbbb","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"e92a2ec4-d079-11ee-a21b-39c1d630fc06"}],"inputType":"TEXT","inputMethod":"SELECT"},{"id":"MTMxMjA5Mzk5MzE=","name":"TextField1","description":"bbbb","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"e92a2ec4-d079-11ee-a21b-39c1d630fc06"}],"inputType":"TEXT","inputMethod":"FREEFORM"}]}',
        'TRUE',
        '{"DLT": [{"start":1,"end":100,"production_hours":2}, {"start":101,"end":1000,"production_hours":6}]}'
    ];

    public const ROW_COLUMN_DATA = [
        '96338753-a78a-4a7c-b95a-97b4f2006271',
        'text n text',
        'desc',
        '0.31',
        'test',
        '{   "id" : 1696536412672,   "version" : 1,   "name" : "Catalog Product",   "qty" : 1,   "priceable" : true,   "features" : [ ],   "properties" : [ {     "id" : 1701448414260,     "name" : "CATALOG_PRODUCT_ID",     "value" : "96338753-a78a-4a7c-b95a-97b4f2006270"   }, {     "id" : 1696624628058,     "name" : "CATALOG_PRINT_OPTIONS",     "value" : "cutting : none, folding : none, color : black and white, printSides : single-sided, paperType : White (20 lb), paperSize : 8.5 x 11 (letter)"   }, {     "id" : 1696624643609,     "name" : "CATALOG_FINISHING_OPTIONS",     "value" : "drilling : none, frontCover : none, backCover : none, stapling : none, binding : none, collated : collated (sets)"   }, {     "id" : 1470151626854,     "name" : "SYSTEM_SI",     "value" : null   }, {     "id" : 1454950109636,     "name" : "USER_SPECIAL_INSTRUCTIONS",     "value" : null   } ],   "pageExceptions" : [ ],   "proofRequired" : false,   "instanceId" : 1708493315226,   "userProductName" : null,   "inserts" : [ ],   "exceptions" : [ ],   "addOns" : [ ],   "contentAssociations" : [ {     "parentContentReference" : "c851d3ef-d079-11ee-a21b-f5e2998b40de",     "contentReference" : "c851d3ef-d079-11ee-a21b-f5e2998b40de",     "contentReplacementUrl" : null,     "contentType" : "application/pdf",     "fileSizeBytes" : 0,     "fileName" : "text n text.pdf",     "printReady" : true,     "contentReqId" : 1483999952979,     "name" : null,     "desc" : null,     "purpose" : "MAIN_CONTENT",     "specialInstructions" : null,     "pageGroups" : [ {       "start" : 1,       "end" : 1,       "width" : 8.5,       "height" : 11.0,       "orientation" : null     } ],     "physicalContent" : false   } ],   "productionContentAssociations" : [ ],   "catalogReference" : null,   "products" : [ ],   "externalSkus" : [ {     "skuDescription" : null,     "skuRef" : null,     "code" : "0005",     "unitPrice" : null,     "price" : null,     "qty" : 1,     "applyProductQty" : true   } ],   "vendorReference" : null,   "isOutSourced" : false,   "contextKeys" : [ ],   "externalProductionDetails" : {     "weight" : {       "value" : 0.0136,       "units" : "LB"     },     "productionTime" : null   } }',
        'https://documentapitest.prod.fedex.com/document/fedexoffice/v2/documents/c851d3ef-d079-11ee-a21b-f5e2998b40de/previewpages/1',
        '/custom docs',
        'TRUE',
        '{   "customizableFields" : [{"id":"MTMxMjA5Mzk5MzI=","name":"ImageField1","description":"aaaaa","mandatory":true,"sequence":1,"documentAssociations":[{"pageNumber":1,"documentId":"e92a2ec4-d079-11ee-a21b-39c1d630fc06"}],"inputType":"IMAGE","inputMethod":"SELECT","options":[{"imageValue":{"documentId":"facb6dbc-d079-11ee-a21b-37a9e3e36864","previewURL":"https://documentapitest.prod.fedex.com/document/fedexoffice/v2/documents/facb6dbc-d079-11ee-a21b-37a9e3e36864/previewpages/1","fileName":"image1"}}]},{"id":"MTMxMjA5Mzk5MzE=","name":"TextField1","description":"bbbb","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"e92a2ec4-d079-11ee-a21b-39c1d630fc06"}],"inputType":"TEXT","inputMethod":"SELECT"},{"id":"MTMxMjA5Mzk5MzE=","name":"TextField1","description":"bbbb","mandatory":true,"sequence":2,"documentAssociations":[{"pageNumber":1,"documentId":"e92a2ec4-d079-11ee-a21b-39c1d630fc06"}],"inputType":"TEXT","inputMethod":"FREEFORM"}]}',
        'TRUE',
        '{"DLT": [{"start":1,"end":100,"production_hours":2}, {"start":101,"end":1000,"production_hours":6}]}'
    ];
    
    public const ELEVEN_COLUMN_DATA_MULTIPLE_CATS = [
        [
            'sku1',
            'Product 1',
            'Desc',
            '100',
            'http://example.com',
            '5',
            '6',
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[7],
            'true',
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[9],
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[10],
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[11],
            '12'
        ],
        [
            'sku2',
            'Product 2',
            'Desc',
            '200',
            'http://example.com',
            '5',
            '6',
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[7],
            'true',
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[9],
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[10],
            self::ROW_COLUMN_DATA_MULTIPLE_CATS[11],
            '12'
        ]
    ];

    public const ELEVEN_COLUMN_DATA = [
        [
            'sku1',
            'Product 1',
            'Desc',
            '100',
            'http://example.com',
            '5',
            '6',
            self::ROW_COLUMN_DATA[7],
            'true',
            self::ROW_COLUMN_DATA[9],
            self::ROW_COLUMN_DATA[10],
            self::ROW_COLUMN_DATA[11],
            '12'
        ],
        [
            'sku2',
            'Product 2',
            'Desc',
            '200',
            'http://example.com',
            '5',
            '6',
            self::ROW_COLUMN_DATA[7],
            'true',
            self::ROW_COLUMN_DATA[9],
            self::ROW_COLUMN_DATA[10],
            self::ROW_COLUMN_DATA[11],
            '12'
        ]
    ];

    public const MIGRATE_CATALOG_DATA = [
        "products" => [
            [
                "id"=>'', "additionalData"=>[
                    "catalogSyncQueueId" => '',
                    "sharedCatId" => '',
                    "storeId" => ''
                ]
            ]
        ]
    ];

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSyncQueueMock = $this->getMockBuilder(CatalogSyncQueue::class)
            ->setMethods([
                'setId',
                'setCompanyId',
                'setStoreId',
                'setSharedCatalogId',
                'setStatus',
                'setCreatedBy',
                'setEmailId',
                'load',
                'save'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogMvp = $this->getMockBuilder(CatalogMvp::class)
            ->setMethods(['getRootCategoryDetailFromStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSyncQueueFactoryMock = $this->getMockBuilder(CatalogSyncQueueFactory::class)
            ->setMethods(['create','setStoreId','save','setCompanyId','setSharedCatalogId','setIsImport','setStatus','setCreatedBy','getId','setEmailId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getCustomerGroupId', 'getSuperUserId'])
            ->getMockForAbstractClass();
        $this->dataHelerMock = $this->getMockBuilder(Data::class)
            ->setMethods(['getStoreId', 'getBrowseCatalogCategoryName', 'isMigrationFixToggle'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create', 'addFieldToFilter', 'getId', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->authSessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['getUser','getFirstname','getLastname', 'getEmail'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSyncQueueProcessFactoryMock = $this->getMockBuilder(CatalogSyncQueueProcessFactory::class)
            ->setMethods([
                'create',
                'setCatalogSyncQueueId',
                'setSharedCatalogId',
                'setCategoryId',
                'setStoreId',
                'setStatus',
                'setCatalogType',
                'setJsonData',
                'setActionType',
                'save',
                'getId'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryLinkManagementInterfaceMock = $this->getMockBuilder(CategoryLinkManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogMigrationFactoryMock = $this->getMockBuilder(CatalogMigrationFactory::class)
            ->setMethods(['setCatalogSyncQueueId','create','setStatus','setJsonData','save','getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogMigrationMock = $this->getMockBuilder(CatalogMigration::class)
            ->setMethods([
                'setId',
                'setStatus',
                'load',
                'save',
                'setCompanyId',
                'setStoreId',
                'setSharedCatalogId',
                'setCreatedBy',
                'setEmailId',
                'setIsImport',
                'getId',
                'setCatalogSyncQueueId',
                'setJsonData',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerInterfacenMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyItem = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['load', 'setStatus', 'save', 'addFieldToFilter', 'getData', 'getFirstItem', 'getId'])
            ->getMock();
        $this->userMock = $this->createMock(User::class);
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['get', 'getCategoryIds','getSku', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryInterfaceMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'getFirstname', 'getLastname', 'getEmail'])
            ->getMockForAbstractClass();
        $this->mediaGalleryProcessorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['removeImage'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->catalogMigrationHelper = $this->objectManager->getObject(
            CatalogMigrationHelper::class,
            [
                'context'                         => $this->contextMock,
                'catalogSyncQueueFactory'         => $this->catalogSyncQueueFactoryMock,
                'companyRepositoryInterface'      => $this->companyRepositoryMock,
                'dataHelper'                      => $this->dataHelerMock,
                'collectionFactory'               => $this->collectionFactoryMock,
                'message'                         => $this->messageMock,
                'publisher'                       => $this->publisherMock,
                'migrationProcess'                => $this->catalogMigrationFactoryMock,
                'logger'                          => $this->loggerInterfacenMock,
                'session'                         => $this->authSessionMock,
                'catalogSyncQueueProcessFactory'  => $this->catalogSyncQueueProcessFactoryMock,
                'categoryLinkManagementInterface' => $this->categoryLinkManagementInterfaceMock,
                'productRepositoryInterface'      => $this->productRepositoryMock,
                'customerRepository'              => $this->customerRepositoryInterfaceMock,
                'mediaGalleryProcessor'           => $this->mediaGalleryProcessorMock,
                'catalogMvp'                      => $this->catalogMvp
            ]
        );
    }

    /**
     * Test method for Multiple categories Fix toggle on
     */
    public function testValidateSheetDataMultipleCats()
    {
        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->dataHelerMock->expects($this->any())->method('getBrowseCatalogCategoryName')->willReturn('MG Browse Catalog');
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->authSessionMock->expects($this->any())->method('getUser')->willReturnSelf();
        $this->authSessionMock->expects($this->any())->method('getFirstname')->willReturn('test');
        $this->authSessionMock->expects($this->any())->method('getLastname')->willReturn('last');
        $this->authSessionMock->expects($this->any())->method('getEmail')->willReturn('test@123.com');
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCompanyId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setSharedCatalogId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCreatedBy')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setIsImport')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('save')->willReturnSelf();

        $this->dataHelerMock->expects($this->any())->method('isMigrationFixToggle')->willReturn(true);
        
        $this->catalogMigrationFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->catalogMigrationMock);
        $this->catalogMigrationMock->expects($this->any())->method('setCatalogSyncQueueId')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('save')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('getId')->willReturnSelf();
        

        $result = $this
        ->catalogMigrationHelper
        ->validateSheetData(self::ELEVEN_COLUMN_DATA_MULTIPLE_CATS, $compId, $sharedCatId, $extUrl);

        $this->assertNotNull($result['message']);
    }

    /**
     * Test method for Multiple categories Fix toggle off
     */
    public function testValidateSheetDataToggleOff()
    {
        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->dataHelerMock->expects($this->any())->method('getBrowseCatalogCategoryName')->willReturn('MG Browse Catalog');
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->authSessionMock->expects($this->any())->method('getUser')->willReturnSelf();
        $this->authSessionMock->expects($this->any())->method('getFirstname')->willReturn('test');
        $this->authSessionMock->expects($this->any())->method('getLastname')->willReturn('last');
        $this->authSessionMock->expects($this->any())->method('getEmail')->willReturn('test@123.com');
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCompanyId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setSharedCatalogId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCreatedBy')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setIsImport')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('save')->willReturnSelf();
        
        $this->catalogMigrationFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->catalogMigrationMock);
        $this->catalogMigrationMock->expects($this->any())->method('setCatalogSyncQueueId')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('setJsonData')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('save')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->any())->method('getId')->willReturnSelf();
        

        $result = $this
        ->catalogMigrationHelper
        ->validateSheetData(self::ELEVEN_COLUMN_DATA, $compId, $sharedCatId, $extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataWithException()
    {
        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->authSessionMock->expects($this->any())->method('getUser')->willReturnSelf();
        $this->authSessionMock->expects($this->any())->method('getFirstname')->willReturn('test');
        $this->authSessionMock->expects($this->any())->method('getLastname')->willReturn('last');
        $this->authSessionMock->expects($this->any())->method('getEmail')->willReturn('test@123.com');
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCompanyId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setSharedCatalogId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCreatedBy')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setIsImport')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('save')->willReturnSelf();
        $this->catalogMigrationFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willThrowException(new \Exception());

        $result = $this
        ->catalogMigrationHelper
        ->validateSheetData(self::ELEVEN_COLUMN_DATA, $compId, $sharedCatId, $extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataWithRow4Empty()
    {
        $datas = [
            ['sku1', 'Product 1', 'Desc 1', '100', 'http://example.com', ''],
            ['sku2', 'Product 2', 'Desc 2', '200', 'http://example.com', '']
        ];

        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCompanyId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getFirstname')->willReturn('test');
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getLastname')->willReturn('last');
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getEmail')->willReturn('test@123.com');
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setSharedCatalogId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setCreatedBy')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setEmailId')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('setIsImport')->willReturnSelf();
        $this->catalogSyncQueueFactoryMock->expects($this->any())->method('save')->willReturnSelf();

        $result = $this->catalogMigrationHelper->validateSheetData($datas, $compId, $sharedCatId, $extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataSuccess()
    {
        $datas = [
            ['sku1', 'Product 1',  'Desc 1', '100', 'http://company1.com'],
            ['sku2', 'Product 2', 'Desc 2', '200', 'http://company2.com'],
        ];

        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';

        $result = $this->catalogMigrationHelper->validateSheetData($datas, $compId, $sharedCatId, $extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataError()
    {
        $datas = [
            ['sku1', 'Product 1', 'Desc 1', '100', 'http://invalid-url.com'],
            ['sku2', 'Product 2', 'Desc 2', ''],
        ];

        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';

        $result = $this->catalogMigrationHelper->validateSheetData($datas, $compId, $sharedCatId, $extUrl);

        $this->assertFalse($result['status']);
    }

    public function testValidateSheetDataIfNotSku()
    {
        $datas = [
            ['sku2', 'Product 2', 'Desc 1', ''],
            ['', 'Product 1', 'Desc 2', '100', 'http://invalid-url.com'],
        ];

        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';

        $result = $this->catalogMigrationHelper->validateSheetData($datas, $compId, $sharedCatId, $extUrl);

        $this->assertFalse($result['status']);
    }
    
    public function testValidateSheetDataTrue()
    {
        $datas = [
            ['sku1', 'Product 1', 'Desc 1', '100', 'http://invalid-url.com'],
            ['sku2', 'Product 2', 'Desc 2', '200','http://another-valid-url.com'],
        ];

        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';

        $result = $this->catalogMigrationHelper->validateSheetData($datas, $compId, $sharedCatId, $extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataElse()
    {
        $datas = [
            [
                'status' => false,
                'message' => 'Missing data in some rows columns <b>Row</b> 2: Price, company url.'
            ]
        ];
        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';
        $result = $this->catalogMigrationHelper->validateSheetData($datas, $compId, $sharedCatId, $extUrl);
        $this->assertNotNull($result);
    }

    public function testCheckColumnDataSuccess()
    {
        $catalogData = ['sku1', 'Product 1', 'Desc 1', '100', 'http://company1.com'];
        $extUrl = 'http://example.com';

        $result = $this->catalogMigrationHelper->checkColumnData($catalogData, $extUrl);

        $this->assertTrue($result['columnErrorFlag']);
    }

    public function testCheckColumnDataError()
    {
        $catalogData = ['sku1', '', 'Desc 1', '100', 'http://invalid-url.com'];
        $extUrl = 'http://example.com';

        $result = $this->catalogMigrationHelper->checkColumnData($catalogData, $extUrl);

        $this->assertTrue($result['columnErrorFlag']);
    }

    public function testUpdateCatalogMigrationQueueStatus()
    {
        $lastMigrationProcessId = 1;
        
        $this->catalogMigrationFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->catalogMigrationMock);
        $this->catalogMigrationMock->expects($this->once())->method('setId')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->once())->method('setStatus')->willReturnSelf();
        $this->catalogMigrationMock->expects($this->once())->method('save')->willReturn($this->catalogMigrationMock);

        $this->catalogMigrationHelper->updateCatalogMigrationQueueStatus(
            $lastMigrationProcessId,
            static::STATUS_COMPLETED
        );
    }

    public function testUpdateCatalogMigrationQueueStatusException()
    {
        $lastMigrationProcessId = 1;
        $exception = new NoSuchEntityException();
        $this->catalogMigrationFactoryMock->expects($this->any())
            ->method('create')->willThrowException($exception);
        $this->catalogMigrationHelper->updateCatalogMigrationQueueStatus(
            $lastMigrationProcessId,
            static::STATUS_COMPLETED
        );
    }

    /**
     * Test cases for removeProductImage
     */
    public function testRemoveProductImage()
    {
        $productMock = $this->createMock(Product::class);

        $this->mediaGalleryProcessorMock->expects($this->any())
            ->method('removeImage')
            ->willReturnSelf();

        $productSku = 'testProduct';
        $entryMock = $this->getMockForAbstractClass(ProductAttributeMediaGalleryEntryInterface::class);
        $entryId = 42;
        $entrySecondId = 43;
        $this->productRepositoryMock->expects($this->once())->method('get')->with($productSku)
            ->willReturn($productMock);
        $existingEntryMock = $this->createMock(
            ProductAttributeMediaGalleryEntryInterface::class
        );
        $existingSecondEntryMock = $this->createMock(
            ProductAttributeMediaGalleryEntryInterface::class
        );
       
        $existingSecondEntryMock->expects($this->any())->method('getTypes')->willReturn([]);
        $existingSecondEntryMock->expects($this->once())->method('getFile')->willReturn('/s/a/sample_3.jpg');
        $productMock->expects($this->once())->method('getMediaGalleryEntries')
            ->willReturn([$existingEntryMock, $existingSecondEntryMock]);

        $this->assertNull($this->catalogMigrationHelper->removeProductImage($productSku));
    }

    /**
     * Test cases for removeProductImageException
     */
    public function testRemoveProductImagExceptione()
    {
        $productMock = $this->createMock(Product::class);

        $this->mediaGalleryProcessorMock->expects($this->any())
            ->method('removeImage')
            ->willReturnSelf();

        $productSku = 'testProduct';
        $entryMock = $this->getMockForAbstractClass(ProductAttributeMediaGalleryEntryInterface::class);
        $entryId = 42;
        $entrySecondId = 43;
        $this->productRepositoryMock->expects($this->once())->method('get')->with($productSku)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Product not found')));

        $this->assertNull($this->catalogMigrationHelper->removeProductImage($productSku));
    }
    

    /**
     * Test cases for getDltJson
     * return string
     */
    public function testGetDltJson()
    {
        $dlt = '{"DLT": [{"start":1,"end":100,"production_hours":2},  {"start":101,"end":1000,"production_hours":6}]}';
        $this->assertNotNull($this->catalogMigrationHelper->getDltJson(
            $dlt
        ));
    }
    
    /**
     * Test method for prepareProductData
     */
    public function testPrepareProductData()
    {
        $this->assertNotNull($this->catalogMigrationHelper->prepareProductData(
            self::ROW_COLUMN_DATA,
            self::COMPANY_ID,
            self::SHARED_CATALOG_ID,
            self::STORE_ID,
            self::CATALOG_SYNC_QUEUE_ID
        ));
    }

    /**
     * Test method for formatCustomizedFields
     */
    public function testFormatCustomizedFields()
    {
        $customizableFields = self::ROW_COLUMN_DATA[9];

        $this->assertNotNull($this->catalogMigrationHelper->formatCustomizedFields($customizableFields));
    }

    /**
     * Test method for createProductCreateUpdateQueue with Multiple categories Fix toggle on
     */
    public function testcreateProductCreateUpdateQueue()
    {
        $categoryIds= [
           'id1'
        ];
        $this->dataHelerMock->expects($this->any())->method('isMigrationFixToggle')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())->method('getCategoryIds')->willReturn(['id1']);
        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->assertNull(
            $this->catalogMigrationHelper->createProductCreateUpdateQueue(self::MIGRATE_CATALOG_DATA, $categoryIds));
    }

    /**
     * Test method for createProductCreateUpdateQueue with Multiple categories Fix toggle off
     */
    public function testcreateProductCreateUpdateQueueToggleOff()
    {
        $categoryIds= [
            [
                'id1'
            ]
        ];
        
        $this->productRepositoryMock->expects($this->any())->method('get')->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())->method('getCategoryIds')->willReturn(['id1']);
        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->assertNull(
            $this->catalogMigrationHelper->createProductCreateUpdateQueue(self::MIGRATE_CATALOG_DATA, $categoryIds));
    }

    /**
     * Test method for createProductCreateUpdateQueue
     */
    public function testcreateProductCreateUpdateQueueWithExceptions()
    {
        $categoryIds= ['id1'];
        $this->dataHelerMock->expects($this->any())->method('isMigrationFixToggle')->willReturn(true);
        $this->productRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Product not found')));
        $this->productRepositoryMock->expects($this->any())->method('getCategoryIds')->willReturn(['id1']);
        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogSyncQueueProcessFactoryMock
            ->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception());
        $this->assertNull(
            $this->catalogMigrationHelper->createProductCreateUpdateQueue(self::MIGRATE_CATALOG_DATA,$categoryIds));
    }

    /**
     * Test method for createProductCreateUpdateQueue
     */
    public function testcreateProductCreateUpdateQueueWithExceptionsToggleOff()
    {
        $categoryIds= [
            [
                'id1'
            ]
        ];
        $this->productRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Product not found')));
        $this->productRepositoryMock->expects($this->any())->method('getCategoryIds')->willReturn(['id1']);
        $this->catalogSyncQueueProcessFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogSyncQueueProcessFactoryMock
            ->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception());
        $this->assertNull(
            $this->catalogMigrationHelper->createProductCreateUpdateQueue(self::MIGRATE_CATALOG_DATA,$categoryIds));
    }
}
