<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Helper;

use Fedex\SharedCatalogCustomization\Helper\Data;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcess;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\Collection as CatalogSyncQueueCollection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface;
use Magento\SharedCatalog\Model\SharedCatalog;

class DataTest extends TestCase
{
    protected $catalogSyncQueueCollection;
    protected $companyCollectionFactory;
    protected $categoryCollection;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogSyncQueueConfigurationInterface;
    protected $collectionItem;
    protected $helperData;
    public const API_RESPONSE_DATA = [
        'transactionId' => 'dec05fe5-9a0d-4bf4-8ace-2a8614a996d4',
        'output' => [
            'folder' => [
                'id' => 'e4de4027-9379-4186-a939-282de593776a',
                'name' => 'ALL FOLDERS',
                'description' => 'ROOT FOLDER',
                'accessGroups' => ['administrators','everyone'],
                'folderSummaries' => [
                    [
                        'id' => 'b10be3ab-6cc2-4e98-b525-990c4ea61ad1',
                        'name' => '__ImageLibrary',
                        'description' => 'Image Library Folder'
                    ],
                    [
                        'id' => '9c48a810-754b-4d60-8d56-847d65e69a8e',
                        'name' => 'Documents'
                    ],
                    [
                        'id' => '2d3e11f6-61e3-4757-aa36-0ada4f0f8954',
                        'name' => 'Party Events',
                        'description' => 'Party Events'
                    ],
                    [
                        'id' => 'ceac1d27-a075-4fc6-96ca-e3162761deda',
                        'name' => 'Charity Checks'
                    ],
                    [
                        'id' => '5c1c730d-aa00-423c-b0ef-84bb98bdbdbc',
                        'name' => 'Empty Folder',
                        'description' => 'Empty Folder'
                    ]
                ],
                'itemSummaries' => [
                    [
                        'id' => 'a7f5cb9d-431c-4ca7-bf65-b28ba6ee0042',
                        'version' => 'DOC_20210305_12565010315_1',
                        'name' => 'Root Distribution Brochures',
                        'description' => 'These are the brochures to be
                        distributed to AstraZen members only.',
                        'createdBy' => 'Admin Admin',
                        'creationTime' => '2021-03-05T16:17:55Z',
                        'modifiedBy' => 'Admin Admin',
                        'modifiedTime' => '2021-03-05T16:17:55Z',
                        'links' => [
                            [
                                'href' => '.../v2/catalogProducts/a7f5cb9d-431c-4ca7-bf65-b28ba6ee0042',
                                'rel' => 'detail'
                            ],
                            [
                                'href' => self::URL,
                                'rel' => 'thumbnail'
                            ]
                        ],
                        'type' => 'PRODUCT',
                        'catalogProductSummary' => [
                            'productRateTotal' => [
                                'currency' => 'USD',
                                'price' => '11.52'
                            ],
                            'customizable' => '',
                            'availability' => [
                                'available' => 1,
                                'dateRange' => [
                                    'startDateTime' => '2021-03-05T16:16:57Z'
                                ]
                            ],
                            'editable' => 1
                        ]
                    ]
                ]
            ]
        ]
    ];
    public const API_RESPONSE = '{"transactionId":"52de42f3-2c8a-47f8-a781-3280c815ea41","output":{"folder":{"id":"f2a7fe67-76ed-479b-8b2c-5157b0f2697a","name":"ALL FOLDERS","description":"ROOT FOLDER","accessGroups":["administrators","everyone"],"folderSummaries":[{"id":"5108a373-877b-4066-ac4d-ec925cc84059","name":"__ImageLibrary","description":"Image Library Folder","createdDate":"2020-10-08T04:29:08.000+0000","modifiedDate":"2020-10-08T04:29:08.000+0000"},{"id":"cc643324-2358-410d-8010-fb0c38fd5c4b","name":"Sample Files","description":"Sample Files","createdDate":"2021-04-23T04:59:47.000+0000","modifiedDate":"2021-04-23T04:59:47.000+0000"},{"id":"d9a8a9af-e34d-4f47-87ba-cce66e137054","name":"Infogain","description":"Infogain","createdDate":"2022-01-03T09:23:09.000+0000","modifiedDate":"2022-01-03T09:23:09.000+0000"},{"id":"6d013599-8eeb-4b51-aec0-2eae55fb4397","name":"!@#$%*Test","description":"!@#$%*Test","createdDate":"2021-09-09T20:31:55.000+0000","modifiedDate":"2021-09-09T20:31:55.000+0000"}],"itemSummaries":[{"id":"d29b5ec6-904d-4816-92a6-4dea274150b4","version":"DOC_20201007_12505756517_2","name":"test doc","createdBy":" ","creationTime":"2020-10-08T04:31:32Z","modifiedBy":" ","modifiedTime":"2020-10-08T04:32:36Z","links":[{"href":".../v2/catalogProducts/d29b5ec6-904d-4816-92a6-4dea274150b4","rel":"detail"},{"href":"https://printonline6.dmz.fedex.com/v3.8.0_s3/PreviewServlet?getImage=DOC_20201007_12505756517_2","rel":"thumbnail"}],"type":"PRODUCT","catalogProductSummary":{"productRateTotal":{"currency":"USD","price":"0.69"},"customizable":false,"availability":{"available":true,"dateRange":{}},"editable":false}}],"createdDate":"2020-10-08T04:29:08.000+0000","modifiedDate":"2020-10-08T04:29:08.000+0000"}}}';
    public const GATEWAY_TOKEN = 'l7xx1cb26690fadd4b2789e0888a96b80ee2';
    public const TAZ_TOKEN = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDI0MDczMDYsImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjVlNjcyZDAxLTEwYWMtNDA4MS04OGFiLTVlMzNlOTU1MjUzZiIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.byIF18MhwSoG4TW7CofrSDr9xVyDAjiT15HF78s_SRvWdFJXn1mxrv855WR38-AoBGM9VIKVQLw81HlbaQ0pfGaZg7hVuma7Qg2eDyDmmuPBQCuAxsPKJZmBQpUss4NajEp44tN8wKKo5PSJOmIZ-vrLiaugLvQs5T72Vx2qAGzGT-l0YpJs74YhJeTmU5rhNp5z76hY6A8jaf7s90NNr6yO9cEQxwqLK0S1xeojil2qcve0zzO0t7u2uKsBP-bb-D1XSdr1ax5KqPZSInGM675SMhyUvHgyy1KTYfbCUUOxoGLdYqDXzt50FjGrJYNjfO9ceTnKhQm60__4lXv6mtXbUliLIcoyCPAUtarK5k05c3ETO5B_4ZVCu1Pzc8kq_t9F-DhUtVgaYVT1ZcLNemvrOJIpTkOONHsGihGCazXxIKyevCLxK1EQRKU7J-Qa34Dl7BANGibiwOGMprcXrV-d_lBnVsOnxBsLE86csqzCc07K5NyRpaZZistjYycYzllWsX3mlgsXJsXzNC3aR5s81-Z1o5ev4tvSaOoUL-pi8fb_9Zgwuk8UfhXJNorB1cqgeKjkRdFB2HJM4tcMDrQYLrD94CLWmNEwTo-MAlmhXUGAMYpVxaB2iTA9CxxXWEDFI8I3ZoqGeItNICErAlLX5c-zH7B0Hj9A-Q3Vmmw","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"5e672d01-10ac-4081-88ab-5e33e955253f"}';
    /**
     * Sample Url
     * @var string
     */
    public const URL = 'https://printonline6.zmd.fedex.com/v3.8.0_s3/PreviewServlet?getImage=DOC_20210305_12565010315_1';

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var CatalogSyncQueueFactory|MockObject
     */
    protected $catalogSyncQueueFactory;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManager;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $catalogSyncQueueCollectionFactory;

    /**
     * @var CompanyCollection|MockObject
     */
    protected $companyCollection;

    /**
     * @var CategoryFactory|MockObject
     */
    protected $categoryFactory;

    /**
     * @var Category|MockObject
     */
    protected $category;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    protected $categoryRepositoryInterface;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    protected $categoryManagement;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configInterface;

    /**
     * @var Data|MockObject
     */
    protected $punchoutHelperData;

    /**
     * @var ManageCatalogItems|MockObject
     */
    protected $manageCatalogItems;

    /**
     * @var Curl|MockObject
     */
    protected $curl;

    /**
     * @var SharedCatalogFactory|MockObject
     */
    protected $sharedCatalogFactory;

    /**
     * @var CatalogSyncQueueProcessFactory
     */
    protected $catalogSyncQueueProcessFactory;

    /**
     * @var StoreRepositoryInterface|MockObject
     */
    protected $storeRepository;

    /**
     * @var PublisherInterface|MockObject
     */
    protected $publisher;

    /**
     * @var MessageInterface|MockObject
     */
    protected $message;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceConnection;

    /**
     * @var Select|MockObject
     */
    protected $selectMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var AttributeRepositoryInterface|MockObject
     */
    protected $attributeRepositoryMock;

    /**
     * @var AttributeInterface|MockObject
     */
    protected $attributeInterfaceMock;

    /**
     * @var CatalogSyncQueueProcess
     */
    protected $catalogSyncQueueProcess;

    /**
     * @var Collection|MockObject
     */
    protected $catalogSyncQueue;

    /**
     * @var CompanyFactory|MockObject
     */
    protected $companyFactory;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfigMock;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var SharedCatalogSyncQueueConfigurationRepository
     */
    protected $sharedCatalogConfRepository;
    private DataObject|MockObject $sharedCatalogItem;
    private MockObject|SharedCatalog $sharedCatalog;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setCompanyId'])
            ->getMock();

        $this->catalogSyncQueueCollection = $this->getMockBuilder(CatalogSyncQueueCollection::class)
            ->disableOriginalConstructor()
            ->addMethods(['setCompanyId', 'setStoreId', 'setLegacyCatalogRootFolderId',
            'setSharedCatalogId', 'setStatus', 'setCreatedBy', 'setEmailId'])
            ->setMethods(['addFieldToFilter', 'getSize'])
            ->getMock();

        $this->catalogSyncQueueFactory = $this->getMockBuilder(CatalogSyncQueueFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueue = $this->getMockBuilder(CatalogSyncQueue::class)
            ->setMethods([
                'setCompanyId',
                'setStoreId',
                'setLegacyCatalogRootFolderId',
                'setSharedCatalogId',
                'setStatus',
                'setCreatedBy',
                'setEmailId',
                'load',
                'save'
            ])
            //->setMethods(['load', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyCollectionFactory = $this
            ->getMockBuilder(CompanyCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyCollection = $this
            ->getMockBuilder(CompanyCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFactory = $this
            ->getMockBuilder(CategoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'setName',
                'getName',
                'load',
                'delete',
                'setParentId',
                'setIsActive',
                'setCustomAttributes',
                'setData'
            ])->getMock();

        $this->categoryRepositoryInterface = $this
            ->getMockBuilder(\Magento\Catalog\Api\CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->logger = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->registryMock = $this
            ->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryManagement = $this
            ->getMockBuilder(\Magento\SharedCatalog\Api\CategoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->punchoutHelperData = $this->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manageCatalogItems = $this
            ->getMockBuilder(\Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->curl = $this->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->setMethods(['getBody', 'get', 'setOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogFactory = $this
            ->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalogFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSyncQueueProcessFactory = $this
            ->getMockBuilder(CatalogSyncQueueProcessFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeRepository = $this->getMockBuilder(\Magento\Store\Api\StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->publisher = $this
            ->getMockBuilder(\Magento\Framework\MessageQueue\PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->message = $this
            ->getMockBuilder(\Fedex\SharedCatalogCustomization\Api\MessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceConnection = $this
            ->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeRepositoryMock = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeId'])
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue', 'getToggleConfig'])
            ->getMock();
        $this->catalogSyncQueueProcess = $this
            ->getMockBuilder(CatalogSyncQueueProcess::class)
            ->setMethods(['setCatalogSyncQueueId', 'getId', 'setCategoryId',
            'setCompanyId', 'setStoreId', 'setLegacyCatalogRootFolderId',
            'setSharedCatalogId', 'setStatus', 'setCreatedBy', 'setJsonData', 'setCatalogType', 'setEmailId', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSyncQueueProcessFactory->expects($this->any())
        ->method('create')->willReturn($this->catalogSyncQueueProcess);
        $this->categoryCollectionFactory = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryCollection = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->setMethods(['addAttributeToSelect', 'load', 'setData',
            'getData', 'getId', 'getSize', 'getItems', 'addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryCollectionFactory->expects($this->any())
            ->method('create')->willReturn($this->categoryCollection);
        $this->sharedCatalogConfRepository = $this->getMockBuilder(
            SharedCatalogSyncQueueConfigurationRepository::class
        )->setMethods(['getBySharedCatalogId'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->sharedCatalogSyncQueueConfigurationInterface = $this->getMockBuilder(
            SharedCatalogSyncQueueConfigurationInterface::class
        )->setMethods(['getCategoryId'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->collectionItem = $this->getMockBuilder(DataObject::class)
        ->disableOriginalConstructor()
        ->setMethods(['getId','getCategoryId','getCompanyUrl'])
        ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->helperData = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->context,
                'catalogSynchQueueFactory' => $this->catalogSyncQueueFactory,
                'messageManager' => $this->messageManager,
                'catalogSyncCollectionFactory' => $this->catalogSyncQueueCollectionFactory,
                'companyCollectionFactory' => $this->companyCollectionFactory,
                '_categoryFactory' => $this->categoryFactory,
                'categoryCollectionFactory' => $this->categoryCollectionFactory,
                'categoryRepositoryInterface' => $this->categoryRepositoryInterface,
                'logger' => $this->logger,
                'categoryManagement' => $this->categoryManagement,
                'configInterface' => $this->configInterface,
                'punchoutHelperData' => $this->punchoutHelperData,
                'manageCatalogItems' => $this->manageCatalogItems,
                'curl' => $this->curl,
                'sharedCatalogFactory' => $this->sharedCatalogFactory,
                'catalogSyncQueueProcessFactory' => $this->catalogSyncQueueProcessFactory,
                'storeRepository' => $this->storeRepository,
                'publisher' => $this->publisher,
                'message' => $this->message,
                'registry' => $this->registryMock,
                'resourceConnection' => $this->resourceConnection,
                'toggleConfig' => $this->toggleConfigMock,
                'attributeRepository' => $this->attributeRepositoryMock,
                'sharedCatalogConfRepository' => $this->sharedCatalogConfRepository,
            ]
        );
    }

    /**
     * Test Method to company ID
     */
    public function testGetCompanyId()
    {
        $customGroupId = 8;
        $companyId = 1;
        $this->getCompanyId($customGroupId, $companyId);
        $this->assertEquals($companyId, $this->helperData->getCompanyId($customGroupId));
    }

    /**
     * Get Company Id by Customer Id.
     */
    protected function getCompanyId($sharedCatalogCustomerGroupId, $companyId)
    {
        $this->companyCollectionFactory->expects($this->any())->method('create')->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();

        $this->companyCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->collectionItem);
        $this->collectionItem->expects($this->any())->method('getId')->willReturn($companyId);
    }

    /**
     * Get Company Id with Null Test method
     */
    protected function getCompanyIdWithNull($sharedCatalogCustomerGroupId)
    {
        $this->companyCollectionFactory->expects($this->any())->method('create')->willReturn($this->companyCollection);

        $this->companyCollection->expects($this->any())->method('addFieldToFilter')
                ->willReturnSelf();

        $this->companyCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturn($this->collectionItem);
        $this->collectionItem->expects($this->any())->method('getId')->willReturn(null);
    }

    /**
     * @test createSyncCatalogQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogQueueWithAlreadyInQueue()
    {
        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $sharedCatalogId = 1;
        $sharedCatalogCategoryId = 32;
        $sharedCatalogName = 'Kaiser';
        $userName = 'System';
        $manualSchedule = true;
        $emailId = 'dummy@fedex.com';
        $companyId = 1;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);
        $this->sharedCatalogConfRepository->expects($this->any())
        ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
        ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['legacy_catalog_root_folder_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null,$this->helperData->createSyncCatalogQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $sharedCatalogCategoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test createSyncCatalogQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogQueue()
    {
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $sharedCatalogId = 1;
        $sharedCatalogCategoryId = 32;
        $sharedCatalogName = 'Kaiser';
        $userName = 'System';
        $manualSchedule = true;
        $emailId = 'dummy@fedex.com';
        $companyId = 1;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['legacy_catalog_root_folder_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(0);

        $this->assertEquals(null,$this->helperData->createSyncCatalogQueue(
            null,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $sharedCatalogCategoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test createSyncCatalogQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogQueueWithNullsharedCatalogCompanyID()
    {
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $sharedCatalogId = null;
        $sharedCatalogCategoryId = 0;
        $sharedCatalogName = 'Kaiser';
        $userName = 'System';
        $manualSchedule = true;
        $emailId = 'dummy@fedex.com';
        $companyId = 1;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['legacy_catalog_root_folder_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(0);

        $this->assertEquals(null,$this->helperData->createSyncCatalogQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $sharedCatalogCategoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test createSyncCatalogQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogQueueWithAlreadyInQueueWithNullsharedCatalogCategoryId()
    {
        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $sharedCatalogId = 1;
        $sharedCatalogCategoryId = null;
        $sharedCatalogName = 'Kaiser';
        $userName = 'System';
        $manualSchedule = true;
        $emailId = 'dummy@fedex.com';
        $companyId = 1;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);
        $this->sharedCatalogConfRepository->expects($this->any())
        ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
        ->method('getCategoryId')->willReturn(null);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['legacy_catalog_root_folder_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null,$this->helperData->createSyncCatalogQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $sharedCatalogCategoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test createSyncCatalogQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogQueueWithAlreadyInQueueWithNullsharedCatalogCompanyID()
    {
        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $sharedCatalogId = 1;
        $sharedCatalogCategoryId = 1;
        $sharedCatalogName = 'Kaiser';
        $userName = 'System';
        $manualSchedule = true;
        $emailId = 'dummy@fedex.com';
        $companyId = 1;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyIdWithNull($sharedCatalogCustomerGroupId, $companyId);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);
        $this->sharedCatalogConfRepository->expects($this->any())
        ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
        ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['legacy_catalog_root_folder_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null,$this->helperData->createSyncCatalogQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $sharedCatalogCategoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test createSyncCatalogQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogQueueWithsharedCatalogCategoryIdEmpty()
    {
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $sharedCatalogId = 1;
        $sharedCatalogCategoryId = "";
        $sharedCatalogName = 'Kaiser';
        $userName = 'System';
        $manualSchedule = true;
        $emailId = 'dummy@fedex.com';
        $companyId = 1;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['legacy_catalog_root_folder_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(0);

        $this->assertEquals(null,$this->helperData->createSyncCatalogQueue(
            null,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $sharedCatalogCategoryId,
            $sharedCatalogName,
            $userName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * Test Method for processCategories
     */
    public function testProcessCategories()
    {
        $catalogSyncQueueId = 1;
        $rootParentCateId = 32;
        $parentCategoryId = 22;
        $catalogSyncQueueProcessId = 2;
        $sharedCatalogId = 4;
        $catalogType = 'category';
        $storeId = 8;
        $responseDataId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $responseDataName = 'Documents';

        $categoryId = $this->testgetCategoryDetails();
        $category = 23;
        $this->updateCategegory(
            $responseDataName,
            $parentCategoryId,
            $category,
            $responseDataId,
            $catalogSyncQueueProcessId
        );

        $this->helperData->processCategories(
            self::API_RESPONSE_DATA,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $catalogType,
            $storeId,
            $catalogSyncQueueProcessId
        );
    }

    /**
     * Test Method for processCategories with create Category
     */
    public function testProcessCategoriesWithCreateCategory()
    {
        $catalogSyncQueueId = 1;
        $rootParentCateId = 32;
        $parentCategoryId = 22;
        $catalogSyncQueueProcessId = 2;
        $sharedCatalogId = 4;
        $catalogType = 'category';
        $storeId = 8;
        $responseDataId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $responseDataName = 'Documents';
        $categoryId = null;

        $categoryId = $this->testGetCategoryDetailsWithNull($responseDataId, $responseDataName, $rootParentCateId);

        $this->createCategory($responseDataName, $parentCategoryId, $responseDataId, $catalogSyncQueueProcessId);

        $this->helperData->processCategories(
            self::API_RESPONSE_DATA,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $catalogType,
            $storeId,
            $catalogSyncQueueProcessId
        );
    }

    /**
     * Test Method for processCategories with Exception
     */
    public function testProcessCategoriesWithException()
    {
        $catalogSyncQueueId = 1;
        $rootParentCateId = 32;
        $parentCategoryId = 22;
        $catalogSyncQueueProcessId = 2;
        $sharedCatalogId = 4;
        $catalogType = 'category';
        $storeId = 8;
        $responseDataId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $responseDataName = 'Documents';
        $categoryId = null;

        $categoryId = $this->testGetCategoryDetailsWithNull($responseDataId, $responseDataName, $rootParentCateId);
        $this->createCategory($responseDataName, $parentCategoryId, $responseDataId, $catalogSyncQueueProcessId);
        $this->catalogSyncQueueProcess->expects($this->any())
        ->method('save')->willThrowException(new \Exception());

        $this->helperData->processCategories(
            self::API_RESPONSE_DATA,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $catalogType,
            $storeId,
            $catalogSyncQueueProcessId
        );
    }
/**
     * Test Method for processCategories with Exception
     */
    public function testProcessCategoriesWithNullResponse()
    {
        $catalogSyncQueueId = 1;
        $rootParentCateId = 32;
        $parentCategoryId = 22;
        $catalogSyncQueueProcessId = 2;
        $sharedCatalogId = 4;
        $catalogType = 'category';
        $storeId = 8;
        $responseDataId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $responseDataName = 'Documents';
        $categoryId = null;

        $categoryId = $this->testGetCategoryDetailsWithNull($responseDataId, $responseDataName, $rootParentCateId);
        $this->createCategory($responseDataName, $parentCategoryId, $responseDataId, $catalogSyncQueueProcessId);
        $this->catalogSyncQueueProcess->expects($this->any())
        ->method('save')->willThrowException(new \Exception());

        $this->helperData->processCategories(
            null,
            $catalogSyncQueueId,
            $rootParentCateId,
            $sharedCatalogId,
            $catalogType,
            $storeId,
            $catalogSyncQueueProcessId
        );
    }

    /**
     * Test method to get Store Id
     */
    public function testGetStoreId()
    {
        $this->assertNotNull($this->getStoreId());
    }

    /**
     * Test method to get Store Id
     */
    protected function getStoreId()
    {
        $storeId = 8;
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->configInterface->expects($this->once())
        ->method('getValue')->with('ondemand_setting/category_setting/b2b_default_store')
        ->willReturn('ondemand');
        $this->storeRepository->expects($this->any())->method('get')->willReturn($store);
        $store->expects($this->any())->method('getId')->willReturn($storeId);

        return $this->helperData->getStoreId();
    }


    /**
     * Test method to handle exception while getting Store Id
     */
    public function testGetStoreIdWithSuchEntityException()
    {
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();

      $this->configInterface->expects($this->once())
        ->method('getValue')->with('ondemand_setting/category_setting/b2b_default_store')
        ->willReturn('ondemand');
        $this->storeRepository->expects($this->once())
        ->method('get')->willThrowException($exception);
        $this->helperData->getStoreId();
    }

    /**
     * Test Method for CatalogSyncApiRequest
     */
    public function testCatalogSyncApiRequest()
    {
        $this->punchoutHelperData->expects($this->any())->method('getAuthGatewayToken')->willReturn(self::GATEWAY_TOKEN);
        $this->punchoutHelperData->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);

        $legacyCatalogRootFolderId = 'f2a7fe67-76ed-479b-8b2c-5157b0f2697a';
        $productApiUrl = 'https://apitest.fedex.com/catalog/fedexoffice/v2/folders';
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Cookie:". self::TAZ_TOKEN,
            "client_id: " . self::GATEWAY_TOKEN
        ];

        $this->curl->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->curl->expects($this->any())->method('get')->willReturnSelf();
        $this->curl->expects($this->any())->method('getBody')->willreturn(self::API_RESPONSE);

        $this->assertIsArray($this->helperData->catalogSyncApiRequest($legacyCatalogRootFolderId));
    }

    /**
     * Test Method for CatalogSyncApiRequest with error
     */
    public function testCatalogSyncApiRequestWithError()
    {
        $folderApiResponse = '{"errors":"Test"}';
        $this->punchoutHelperData->expects($this->any())->method('getAuthGatewayToken')->willReturn(self::GATEWAY_TOKEN);
        $this->punchoutHelperData->expects($this->any())->method('getTazToken')->willReturn(self::TAZ_TOKEN);

        $legacyCatalogRootFolderId = 'f2a7fe67-76ed-479b-8b2c-5157b0f2697a';
        $productApiUrl = 'https://apitest.fedex.com/catalog/fedexoffice/v2/folders';
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Cookie:". self::TAZ_TOKEN,
            "client_id: " . self::GATEWAY_TOKEN
        ];

        $this->curl->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->curl->expects($this->any())->method('get')->willReturnSelf();
        $this->curl->expects($this->any())->method('getBody')->willreturn($folderApiResponse);

        $this->assertIsArray($this->helperData->catalogSyncApiRequest($legacyCatalogRootFolderId));
    }

    /**
     * Test Method for createCategory
     */
    public function testCreateCategory()
    {
        $categoryName = 'Document';
        $parentCategoryId = 32;
        $legacyCatalogFolderId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $catalogSyncQueueProcessId = 2;

        $this->createCategory($categoryName, $parentCategoryId, $legacyCatalogFolderId, $catalogSyncQueueProcessId);
    }

    protected function createCategory(
        $categoryName,
        $parentCategoryId,
        $legacyCatalogFolderId,
        $catalogSyncQueueProcessId
    ) {
        $isActive = true;
        $categoryId = 1;

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('setName')->with($categoryName)->willReturnSelf();
        $this->category->expects($this->any())->method('setParentId')->with($parentCategoryId)->willReturnSelf();
        $this->category->expects($this->any())->method('setIsActive')->with($isActive)->willReturnSelf();
        $this->category->expects($this->any())->method('setCustomAttributes')->willReturnSelf();
        $this->category->expects($this->any())->method('setData')->willReturnSelf();

        $this->categoryRepositoryInterface->expects($this->any())
        ->method('save')->with($this->category)->willReturn($this->category);

        $this->category->expects($this->any())->method('getId')->willReturn($categoryId);

        $this->assertNotNull($this->helperData->createCategory(
            $categoryName,
            $parentCategoryId,
            $legacyCatalogFolderId,
            $catalogSyncQueueProcessId
        ));
    }

     /**
      * Test Method for createCategory with Exception
      */
    public function testCreateCategoryWithException()
    {
        $categoryName = 'Document';
        $parentCategoryId = 32;
        $legacyCatalogFolderId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $catalogSyncQueueProcessId = 2;
        $isActive = true;
        $categoryId = 1;
        $exception = new \Exception();

        $this->categoryFactory->expects($this->once())->method('create')->willReturn($this->category);
        $this->category->expects($this->once())->method('setName')->with($categoryName)->willReturnSelf();
        $this->category->expects($this->once())->method('setParentId')->with($parentCategoryId)->willReturnSelf();
        $this->category->expects($this->once())->method('setIsActive')->with($isActive)->willReturnSelf();
        $this->category->expects($this->once())->method('setCustomAttributes')->willReturnSelf();
        $this->category->expects($this->once())->method('setData')->willReturnSelf();

        $this->categoryRepositoryInterface->expects($this->once())
        ->method('save')->with($this->category)->willThrowException($exception);

        $this->helperData->createCategory(
            $categoryName,
            $parentCategoryId,
            $legacyCatalogFolderId,
            $catalogSyncQueueProcessId
        );
    }

    /**
     * Test Method for updateCategory
     */
    public function testUpdateCategory()
    {
        $categoryName = 'Party Events';
        $parentCategoryId = 22;
        $legacyCatalogFolderId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $catalogSyncQueueProcessId = 2;
        $categoryId = 1;

        $this->assertEquals(null,$this->updateCategegory(
            $categoryName,
            $parentCategoryId,
            $categoryId,
            $legacyCatalogFolderId,
            $catalogSyncQueueProcessId
        ));
    }

    /**
     * Test method for updateCategegory
     */
    public function updateCategegory(
        $categoryName,
        $parentCategoryId,
        $categoryId,
        $legacyCatalogFolderId,
        $catalogSyncQueueProcessId
    ) {
        $rowData = ['row_id' => 12];
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);

        $this->resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('from')->with('catalog_category_entity')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')->withConsecutive(['entity_id=?', $categoryId])
            ->willReturnSelf();

        $this->connectionMock->expects($this->any())->method('fetchRow')->with($this->selectMock)
            ->willReturn(new \ArrayIterator($rowData));

        $this->attributeRepositoryMock->expects($this->any())->method('get')->willReturn($this->attributeInterfaceMock);
        $this->attributeInterfaceMock->expects($this->any())->method('getAttributeId')->willReturn(133);

        $this->category->expects($this->any())->method('setName')->willReturnSelf();
        $this->category->expects($this->any())->method('setParentId')->willReturnSelf();
        $this->category->expects($this->any())->method('setIsActive')->willReturnSelf();
        $this->category->expects($this->any())->method('setCustomAttributes')->willReturnSelf();

        $this->categoryRepositoryInterface->expects($this->any())->method('save')->with($this->category)
            ->willReturn($this->category);

        $this->category->expects($this->any())->method('getId')->willReturn($categoryId);

        $this->helperData->updateCategory(
            $categoryName,
            $parentCategoryId,
            $categoryId,
            $legacyCatalogFolderId,
            $catalogSyncQueueProcessId
        );
    }

    /**
     * Test Method for updateCategory with Exception
     */
    public function testUpdateCategoryWithException()
    {
        $categoryName = 'Party Events';
        $parentCategoryId = 22;
        $legacyCatalogFolderId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $catalogSyncQueueProcessId = 2;
        $isActive = true;
        $categoryId = 1;
        $rowData = ['row_id' => 12];
        $exception = new \Exception();

        $this->categoryFactory->expects($this->once())->method('create')->willReturn($this->category);
        $this->category->expects($this->once())->method('load')->willReturn($this->category);

        $this->resourceConnection->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->selectMock);

        $this->selectMock->expects($this->any())->method('from')->with('catalog_category_entity')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('where')->withConsecutive(['entity_id=?', $categoryId])
            ->willReturnSelf();

        $this->connectionMock->expects($this->any())->method('fetchRow')->with($this->selectMock)
            ->willReturn(new \ArrayIterator($rowData));

        $this->attributeRepositoryMock->expects($this->any())->method('get')->willReturn($this->attributeInterfaceMock);
        $this->attributeInterfaceMock->expects($this->any())->method('getAttributeId')->willReturn(133);

        $this->category->expects($this->any())->method('setName')->willReturnSelf();
        $this->category->expects($this->any())->method('setParentId')->willReturnSelf();
        $this->category->expects($this->any())->method('setIsActive')->willReturnSelf();
        $this->category->expects($this->any())->method('setCustomAttributes')->willReturnSelf();

        $this->categoryRepositoryInterface->expects($this->any())->method('save')->with($this->category)
            ->willThrowException($exception);

        $this->helperData->updateCategory(
            $categoryName,
            $parentCategoryId,
            $categoryId,
            $legacyCatalogFolderId,
            $catalogSyncQueueProcessId
        );
    }

    /**
     * Test method for setCatalogSyncRequest
     */
    public function testSetCatalogSyncRequest()
    {
        $catalogSyncQueueId = 1;
        $catalogSyncQueueProcessId = 5;
        $sharedCatalogId = 2;
        $legacyCatalogRootFolderId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $storeId = 8;
        $sharedCatalogCategoryId = 1;

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogItem = $this->getMockBuilder(
            DataObject::class
        )
                ->disableOriginalConstructor()
                ->setMethods(['getCategoryId'])
                ->getMock();

        $this->sharedCatalog->expects($this->any())->method('load')
        ->with($sharedCatalogId)->willReturn($this->sharedCatalogItem);
        $this->sharedCatalogConfRepository->expects($this->any())
        ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
        ->method('getCategoryId')->willReturn(12);
        $this->catalogSyncQueueProcess->expects($this->once())->method('setCatalogSyncQueueId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setSharedCatalogId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setCategoryId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setStoreId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setJsonData')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setCatalogType')->willReturnSelf();

        $this->catalogSyncQueueProcess->expects($this->once())
        ->method('save')->willReturn($this->catalogSyncQueueProcess);
        $this->catalogSyncQueueProcess->expects($this->once())->method('getId')
            ->willReturn($catalogSyncQueueProcessId);
        $this->helperData->setCatalogSyncRequest(
            $catalogSyncQueueId,
            $sharedCatalogId,
            $legacyCatalogRootFolderId,
            $storeId
        );
    }

     /**
      * Test method for setCatalogSyncRequest
      */
    public function testSetCatalogSyncRequestWithException()
    {
        $catalogSyncQueueId = null;
        $catalogSyncQueueProcessId = 5;
        $sharedCatalogId = 2;
        $legacyCatalogRootFolderId = '9c48a810-754b-4d60-8d56-847d65e69a8e';
        $storeId = 8;
        $sharedCatalogCategoryId = 1;

        $exception = new \Exception();

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogItem = $this->getMockBuilder(
            DataObject::class
        )
                ->disableOriginalConstructor()
                ->setMethods(['getCategoryId'])
                ->getMock();


        $this->sharedCatalogConfRepository->expects($this->any())
        ->method('getBySharedCatalogId')->willThrowException($exception);

        $this->sharedCatalogItem->expects($this->any())
        ->method('getCategoryId')->willReturn(12);
        $this->catalogSyncQueueProcess->expects($this->once())->method('setCatalogSyncQueueId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setSharedCatalogId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setCategoryId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setStoreId')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setJsonData')->willReturnSelf();
        $this->catalogSyncQueueProcess->expects($this->once())->method('setCatalogType')->willReturnSelf();

        $this->catalogSyncQueueProcess->expects($this->once())->method('save')->willThrowException($exception);

        $this->helperData->setCatalogSyncRequest(
            $catalogSyncQueueId,
            $sharedCatalogId,
            $legacyCatalogRootFolderId,
            $storeId
        );
    }

    /**
     * Get Category detail test method code.
     */
    public function testGetCategoryDetails()
    {
        $legacyCatalogRootFolderId = 'asd7tbjdsf7t32748382378ynxgb2131';
        $categoryName = 'Document';
        $rootParentCateId = 32;
        $currentCategoryId = 1;
        $path = "%/".$rootParentCateId."/%";

        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->with('*')
            ->willReturn($this->categoryCollection);

        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $collectionItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturn($collectionItem);
        $collectionItem->expects($this->any())->method('getId')->willReturn($currentCategoryId);

        $this->assertEquals($currentCategoryId, $this->helperData->getCategoryDetails(
            $legacyCatalogRootFolderId,
            $categoryName,
            $rootParentCateId
        ));
    }

    /**
     * Get Category detail test method with null
     */
    public function testGetCategoryDetailsWithNull()
    {
        $legacyCatalogRootFolderId = 'asd7tbjdsf7t32748382378ynxgb2131';
        $categoryName = 'Document2';
        $rootParentCateId = 32;
        $currentCategoryId = null;
        $path = "%/".$rootParentCateId."/%";

        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->with('*')
            ->willReturn($this->categoryCollection);

        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $collectionItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturn($collectionItem);
        $collectionItem->expects($this->any())->method('getId')->willReturn(null);

        $this->assertEquals($currentCategoryId, $this->helperData->getCategoryDetails(
            $legacyCatalogRootFolderId,
            $categoryName,
            $rootParentCateId
        ));
    }

    /**
     * Test method for getCategory Detail with Exception
     */
    public function testGetCategoryDetailsWithException()
    {
        $legacyCatalogRootFolderId = 'asd7tbjdsf7t32748382378ynxgb2131';
        $categoryName = 'Document';
        $rootParentCateId = 32;
        $currentCategoryId = 1;
        $path = "%/".$rootParentCateId."/%";

        $exception = new \Exception();

        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->with('*')
            ->willReturn($this->categoryCollection);

        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $collectionItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturn($collectionItem);
        $collectionItem->expects($this->once())->method('getId')->willThrowException($exception);

        $this->assertEquals(null, $this->helperData->getCategoryDetails(
            $legacyCatalogRootFolderId,
            $categoryName,
            $rootParentCateId
        ));
    }

    /**
     * Test method for Get API URL
     */
    public function testGetProductApiUrl()
    {
        $productApiUrlKey = 'fedex/general/product_api_url';
        $productApiUrl = 'https://apitest.fedex.com/catalog/fedexoffice/v2/folders';

        $this->configInterface->expects($this->once())
        ->method('getValue')->with($productApiUrlKey)->willReturn($productApiUrl);

        $this->assertEquals($productApiUrl, $this->helperData->getProductApiUrl());
    }

    /**
     * Test method for deleteCategory
     *
     * @return void
     */
    public function testDeleteCategory()
    {
        $subCategoriesFolderIds = ['cc643324-2358-410d-8010-fb0c38fd5c4b'];
        $subCategoriesNames = ['Sample Files'];
        $rootParentCateId  = 9;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 7;
        $categoryId = 12;

        $fieldFilter = [
            ['attribute'=> 'legacy_catalog_root_folder_id', ['nin' => $subCategoriesFolderIds]],
            ['attribute'=> 'legacy_catalog_root_folder_id', ['null' => true]]
        ];

        $item = ["entity_id" => $categoryId];
        $categoryData = new \Magento\Framework\DataObject();
        $categoryData->setData($item);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);
        $this->categoryCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->withConsecutive([$fieldFilter])
            ->willReturn([$fieldFilter]);

        $this->categoryCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->withConsecutive(['name', ['nin' => $subCategoriesNames]])
            ->willReturnSelf();

        $this->categoryCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->withConsecutive(['parent_id', ['eq' => $rootParentCateId]])
            ->willReturn([$categoryData]);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);

        $this->assertEquals(null, $this->helperData->deleteCategory(
            $subCategoriesFolderIds,
            $subCategoriesNames,
            $rootParentCateId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     * deleteCategoryException
     *
     * @return void
     */
    public function testDeleteCategoryException()
    {
        $subCategoriesFolderIds = ['cc643324-2358-410d-8010-fb0c38fd5c4b'];
        $subCategoriesNames = ['Sample Files'];
        $rootParentCateId  = 9;
        $catalogSyncQueueId = 1;
        $sharedCatalogId = 7;

        $fieldFilter = [
            ['attribute'=> 'legacy_catalog_root_folder_id', ['nin' => $subCategoriesFolderIds]],
            ['attribute'=> 'legacy_catalog_root_folder_id', ['null' => true]]
        ];

        $categoryId = 12;

        $item = ["id" => $categoryId];
        $categoryData = new \Magento\Framework\DataObject();
        $categoryData->setData($item);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);
        $this->categoryCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->withConsecutive([$fieldFilter])
            ->willReturnSelf();

        $this->categoryCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->withConsecutive(['names', ['nin' => $subCategoriesNames]])
            ->willReturnSelf();

        $this->categoryCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->withConsecutive(['parent_id', ['eq' => $rootParentCateId]])
            ->willReturn([$categoryData]);

        $this->category->expects($this->any())->method('load')->willReturn($this->category);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);

        $this->assertEquals(null, $this->helperData->deleteCategory(
            $subCategoriesFolderIds,
            $subCategoriesNames,
            $rootParentCateId,
            $catalogSyncQueueId,
            $sharedCatalogId
        ));
    }

    /**
     * Test method for getBrowseCatalogCategoryName
     *
     * @return void
     */
    public function testGetBrowseCatalogCategoryName()
    {
        $browseCatalogCategoryId = 440;
        $categoryName = 'L6 Site 51 Browse Catalog';

        $this->categoryRepositoryInterface->expects($this->any())->method('get')
            ->with($browseCatalogCategoryId)->willReturn($this->category);
        $this->category->expects($this->any())->method('getName')->willReturn($categoryName);

        $this->assertEquals($categoryName, $this->helperData->getBrowseCatalogCategoryName($browseCatalogCategoryId));
    }

    /**
     * Test method for getBrowseCatalogCategoryName with Exception
     *
     * @return void
     */
    public function testGetBrowseCatalogCategoryNameWithException()
    {
        $browseCatalogCategoryId = 440;
        $exception = new \Exception();
        $this->categoryRepositoryInterface->expects($this->any())->method('get')->willThrowException($exception);

        $this->assertNull($this->helperData->getBrowseCatalogCategoryName($browseCatalogCategoryId));
    }

    /**
     * Test method for isMigrationFixToggle toggle on
     */
    public function testIsMigrationFixToggleOn()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('explorers_d185410_fix')->willReturn(true);

        $this->assertTrue($this->helperData->isMigrationFixToggle());
    }
}
