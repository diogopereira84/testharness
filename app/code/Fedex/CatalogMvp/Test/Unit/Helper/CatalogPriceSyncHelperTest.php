<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Helper;

use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue as CatalogSyncQueueModel;
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
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Company\Model\Company;
use Magento\SharedCatalog\Model\SharedCatalog;

class CatalogPriceSyncHelperTest extends TestCase
{
    /**
     * @var (\Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\Collection
     * & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogSyncQueueCollection;

    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyCollectionFactory;

    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryCollection;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface
     * & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogSyncQueueConfigurationInterface;

    /**
     * @var (\Magento\Framework\DataObject & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionItem;

    /**
     * @var (\Magento\Eav\Api\AttributeSetRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeSetRepositoryMock;

    /**
     * @var (\Fedex\FXOPricing\Helper\FXORate & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fXORateMock;

    /**
     * @var (\Magento\Catalog\Model\Product & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productFactoryMock;

    /**
     * @var (\Fedex\CatalogMvp\Helper\CatalogMvp & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogMvpMock;

    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelperMock;

    /**
     * @var (\Magento\Company\Api\Data\CompanyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyInterfaceMock;

    /**
     * @var CatalogPriceSyncHelper
     */
    protected $helperData;
    public const GATEWAY_TOKEN = 'l7xx1cb26690fadd4b2789e0888a96b80ee2';
    public const TAZ_TOKEN = '{"access_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2NDI0MDczMDYsImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjVlNjcyZDAxLTEwYWMtNDA4MS04OGFiLTVlMzNlOTU1MjUzZiIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.byIF18MhwSoG4TW7CofrSDr9xVyDAjiT15HF78s_SRvWdFJXn1mxrv855WR38-AoBGM9VIKVQLw81HlbaQ0pfGaZg7hVuma7Qg2eDyDmmuPBQCuAxsPKJZmBQpUss4NajEp44tN8wKKo5PSJOmIZ-vrLiaugLvQs5T72Vx2qAGzGT-l0YpJs74YhJeTmU5rhNp5z76hY6A8jaf7s90NNr6yO9cEQxwqLK0S1xeojil2qcve0zzO0t7u2uKsBP-bb-D1XSdr1ax5KqPZSInGM675SMhyUvHgyy1KTYfbCUUOxoGLdYqDXzt50FjGrJYNjfO9ceTnKhQm60__4lXv6mtXbUliLIcoyCPAUtarK5k05c3ETO5B_4ZVCu1Pzc8kq_t9F-DhUtVgaYVT1ZcLNemvrOJIpTkOONHsGihGCazXxIKyevCLxK1EQRKU7J-Qa34Dl7BANGibiwOGMprcXrV-d_lBnVsOnxBsLE86csqzCc07K5NyRpaZZistjYycYzllWsX3mlgsXJsXzNC3aR5s81-Z1o5ev4tvSaOoUL-pi8fb_9Zgwuk8UfhXJNorB1cqgeKjkRdFB2HJM4tcMDrQYLrD94CLWmNEwTo-MAlmhXUGAMYpVxaB2iTA9CxxXWEDFI8I3ZoqGeItNICErAlLX5c-zH7B0Hj9A-Q3Vmmw","token_type":"bearer","expires_in":43199,"scope":"taz.clients:write","iss":"taz","jti":"5e672d01-10ac-4081-88ab-5e33e955253f"}';

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
     * @var CatalogSyncQueueModel|MockObject
     */
    protected $catalogSyncQueueModel;

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
     * @var DeliveryHelper|MockObject
     */
    protected $deliveryHelper;

    /**
     * @var SharedCatalogSyncQueueConfigurationRepository
     */
    protected $sharedCatalogConfRepository;
    private DataObject|MockObject $sharedCatalogItem;
    private SharedCatalog|MockObject $sharedCatalog;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setCompanyId'])
            ->getMock();

        $this->catalogSyncQueueCollection = $this->getMockBuilder(CatalogSyncQueueCollection::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'setCompanyId', 'setStoreId', 'setLegacyCatalogRootFolderId',
                'setSharedCatalogId', 'setStatus', 'setCreatedBy', 'setEmailId'
            ])
            ->setMethods(['addFieldToFilter', 'getSize', 'getData'])
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
                'getResource',
                'load',
                'save',
                'setId'
            ])
            //->setMethods(['load', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueModel = $this->getMockBuilder(CatalogSyncQueueModel::class)
            ->setMethods(['save'])
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
            ->setMethods([
                'setCatalogSyncQueueId', 'getId', 'setCategoryId',
                'setCompanyId', 'setStoreId', 'setLegacyCatalogRootFolderId',
                'setSharedCatalogId', 'setStatus', 'setCreatedBy', 'setJsonData', 'setCatalogType', 'setEmailId', 'save'
            ])
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
            ->setMethods([
                'addAttributeToSelect', 'load', 'setData',
                'getData', 'getId', 'getSize', 'getItems', 'addFieldToFilter', 'getFirstItem'
            ])
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
            ->setMethods(['getId', 'getCategoryId', 'getCompanyUrl'])
            ->getMock();

        $this->attributeSetRepositoryMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->fXORateMock = $this->getMockBuilder(
            FXORate::class
        )->setMethods(['callRateApi'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactoryMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getExternalProd', 'getAttributeSetId'])
            ->getMock();

        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProductPodEditAbleById'])
            ->getMock();

        $this->companyHelperMock = $this
            ->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFedexAccountNumber', 'getCustomerCompany'])
            ->getMockForAbstractClass();

        $this->companyInterfaceMock = $this
            ->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFedexAccountNumber', 'getDiscountAccountNumber', 'getFxoAccountNumberEditable'])
            ->getMock();

        $this->deliveryHelper = $this
            ->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCustomerAdminUser'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->helperData = $this->objectManager->getObject(
            CatalogPriceSyncHelper::class,
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
                'attributeRepository' => $this->attributeRepositoryMock,
                'sharedCatalogConfRepository' => $this->sharedCatalogConfRepository,
                'companyHelper' => $this->companyHelperMock,
                'attributeSetRepository' => $this->attributeSetRepositoryMock,
                'catalogMvpHelper' => $this->catalogMvpMock,
                'toggleConfig' => $this->toggleConfigMock,
                'fxoRateHelper' => $this->fXORateMock,
                'deliveryHelper' => $this->deliveryHelper,
            ]
        );
    }
    /**
     * Product rates data
     *
     * @return array
     */
    public function productRatesData()
    {
        return [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId'  => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type'   => 'ACCOUNT',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
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
     * @test createSyncCatalogPriceQueue
     *
     * @return void
     */
    public function testcreateSyncCatalogPriceQueue()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = 12;
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = 32;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(12);

        $this->assertNull($this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }
    /**
     * @test createSyncCatalogPriceQueue
     *
     * @return void
     */
    public function testCreateSyncCatalogPriceQueueWithoutSharedCatalogCategoryId()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = null;
        $companyId = 1;
        $legacyCatalogRootFolderId = 12;
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $status = 'pending';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->withConsecutive(
            ['techtitans_B2096706_shared_catalog_price_sync'],
        )->willReturn(true);
        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(12);

        $this->assertNull($this->helperData->createSyncCatalogPriceQueueForSelfReg(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }
    /**
     * @test testSetSyncCatalogQueueWithException
     *
     * @return void
     */
    public function testcreateSyncCatalogPriceQueueWithException()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = 32;
        $status = 'pending';
        $storeId = 8;
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollectionFactory->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->once())->method('getSize')->willThrowException($exception);

        $this->assertNull($this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test setSyncCatalogQueue
     *
     * @return void
     */
    public function testcreateSyncCatalogPriceQueuewithNullsharedcatalogId()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = 12;
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = 32;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(null);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null, $this->assertEquals(null, $this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        )));
    }

    /**
     * @test setSyncCatalogQueue
     *
     * @return void
     */
    public function testSetSyncCatalogQueuewithNulllegacyCatalogRootFolderId()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = null;
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = 32;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(null);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null, $this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test setSyncCatalogQueue
     *
     * @return void
     */
    public function testSetSyncCatalogQueuewithNullsharedCatalogCategoryId()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = null;
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = null;
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyIdWithNull($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(null);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null, $this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }
    /**
     * @test setSyncCatalogQueue
     *
     * @return void
     */
    public function testSetSyncCatalogQueuewithNullsharedCompanyId()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = 12;
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = "test";
        $status = 'pending';
        $storeId = 8;

        $this->getCompanyIdWithNull($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->any())->method('getSize')->willReturn(1);

        $this->assertEquals(null, $this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * @test testSetSyncCatalogQueueWithException
     *
     * @return void
     */
    public function testSetSyncCatalogQueueWithException()
    {
        $adminUserName = 'Test';
        $manualSchedule = true;
        $sharedCatalogId = 1;
        $companyId = 1;
        $legacyCatalogRootFolderId = 'Legacyfolder1312';
        $sharedCatalogCustomerGroupId = 2;
        $name = 'Kaiser';
        $emailId = 'dummy@fedex.com';
        $sharedCatalogCategoryId = 32;
        $status = 'pending';
        $storeId = 8;
        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->getCompanyId($sharedCatalogCustomerGroupId, $companyId);

        $this->sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->addMethods(['getCategoryId'])
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId'])
            ->getMock();

        $this->sharedCatalogConfRepository->expects($this->any())
            ->method('getBySharedCatalogId')->willReturn($this->sharedCatalogItem);

        $this->sharedCatalogItem->expects($this->any())
            ->method('getCategoryId')->willReturn(12);

        $this->catalogSyncQueueCollectionFactory->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->once())->method('getSize')->willThrowException($exception);

        $this->assertNull($this->helperData->createSyncCatalogPriceQueue(
            $legacyCatalogRootFolderId,
            $sharedCatalogCustomerGroupId,
            $sharedCatalogId,
            $name,
            $adminUserName,
            $manualSchedule,
            $emailId
        ));
    }

    /**
     * Test Case for getAttributeSetName
     */
    public function testgetAttributeSetName()
    {
        $attributeSet['attribute_set_name'] = "PrintOnDemand";
        $this->attributeSetRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($attributeSet);
        $this->helperData->getAttributeSetName(1);
    }
    /**
     * Test Case for getAttributeSetNameWith Exception
     */
    public function testgetAttributeSetNameWithException()
    {
        $this->attributeSetRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willThrowException(new \Exception());
        $this->helperData->getAttributeSetName(1);
    }

    public function testgetRateApiUrl()
    {
        $this->configInterface->expects($this->any())->method('getValue')->willReturn('https://api.test.office.fedex.com/rate/fedexoffice/v2/rates');
        $this->helperData->getRateApiUrl();
    }

     /**
      * Test Case for testQuequcleanUp
      */
    public function testQuequcleanUp()
    {
        $legacyCatalogRootFolderId = 12;
        $outputArray = [
            '0' => [
                'id' => 32
            ]
        ];
        $status = "pending";
        $this->catalogSyncQueueCollectionFactory->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncQueueCollection);

        $this->catalogSyncQueueCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['status', ['eq' => $status]],
                ['shared_catalog_id', ['eq' => $legacyCatalogRootFolderId]]
            )->willReturnSelf();

        $this->catalogSyncQueueCollection->expects($this->once())->method('getSize')->willReturn(1);
        $this->catalogSyncQueueCollection->expects($this->once())->method('getData')->willReturn($outputArray);

        $this->catalogSyncQueueFactory->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncQueue);
        $this->catalogSyncQueue->expects($this->once())->method('setId')->willReturnSelf();
        $this->catalogSyncQueue->expects($this->once())->method('setStatus')->willReturnSelf();
        $this->catalogSyncQueue->expects($this->once())->method('getResource')->willReturn($this->catalogSyncQueueModel);
        $this->catalogSyncQueueModel->expects($this->once())->method('save')->willReturnSelf();

        $this->helperData->QuequcleanUp(12);
    }

    /**
     * Test Case for testrateApiCall
     */
    public function testrateApiCall()
    {
        $postData = [
            'product' =>
            ['external_prod' => '{"fxoProductInstance": {"productConfig": {
                            "product":{"contentAssociations":[{"parentContentReference":"13284872036123432471801882290520602401471","contentReference":"13284872037059946631120801710221854566831","contentType":"IMAGE","fileName":"noerror.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]}
                        } }}']
        ];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->withConsecutive(
            ['tech_titans_E_475721'],
            ['tech_titans_E_475721'],  
            ['tech_titans_d_177875_correct_price_product'],
            ['magegeeks_d236791']
        )->willReturn(true, true, false, true);
        $this->fXORateMock->expects($this->any())->method('callRateApi')->willReturn($this->productRatesData());
        $this->testGetCompanyId();
        $this->testifgetFedexNdcAccountNumber();
        $this->helperData->rateApiCall($postData, 'myeprosite', 8);
    }

    /**
     * Test Case for testrateApiCall
     */
    public function testrateApiCallWithToggleOff()
    {
        $postData = [
            'product' =>
            ['external_prod' => '{"fxoProductInstance": {"productConfig": {
                            "product":{"contentAssociations":[{"parentContentReference":"13284872036123432471801882290520602401471","contentReference":"13284872037059946631120801710221854566831","contentType":"IMAGE","fileName":"noerror.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]}
                        } }}']
        ];
        $this->fXORateMock->expects($this->any())->method('callRateApi')->willReturn($this->productRatesData());
        $this->testGetCompanyId();
        $this->testifgetFedexNdcAccountNumber();
        $this->helperData->rateApiCall($postData, 'myeprosite', 8);
    }

    /**
     * Test Case for testgetProductPrice
     */
    public function testgetProductPrice()
    {
        $this->productFactoryMock->expects($this->any())->method('getAttributeSetId')->willReturn(1);
        $this->testgetAttributeSetName();
        $this->testGetCompanyId();
        $this->catalogMvpMock->expects($this->any())->method('isProductPodEditAbleById')->willReturn(1);
        $this->testifgetFedexNdcAccountNumber();
        $this->helperData->getProductPrice($this->productFactoryMock, 'myeprosite', 8);
    }

    /**
     * Test Case for getFedexNdcAccountNumber
     */
    public function testifgetFedexNdcAccountNumber()
    {
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getDiscountAccountNumber')->willReturn(1111);
        $this->helperData->getFedexNdcAccountNumber(8);
    }
    /**
     * Test Case for getFedexNdcAccountNumber
     */
    public function testelsegetFedexNdcAccountNumber()
    {
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getDiscountAccountNumber')->willReturn(null);
        $this->companyInterfaceMock->expects($this->any())->method('getFedexAccountNumber')->willReturn(null);
        $this->helperData->getFedexNdcAccountNumber(8);
    }

    /**
     * Test Case for getFedexNdcAccountNumber with Toggle On and Fedex accounts empty
     */
    public function testGetFedexNdcAccountNumberToggleOnFedexEmptyFallbackDiscount()
    {
        $companyId = 8;
        $this->companyHelperMock->expects($this->once())->method('getCustomerCompany')->with($companyId)->willReturn($this->companyInterfaceMock);
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->with('tech_titans_E_475721')->willReturn(true);
        $this->companyInterfaceMock->expects($this->once())->method('getFedexAccountNumber')->willReturn(null);
        $this->companyInterfaceMock->expects($this->once())->method('getDiscountAccountNumber')->willReturn('111');
        $this->assertEquals('111', $this->helperData->getFedexNdcAccountNumber($companyId));
    }

    /**
     * Test Case for getFedexNdcAccountNumber with Toggle On and Fedex account present
     */
    public function testGetFedexNdcAccountNumberToggleOnFedexPresent()
    {
        $companyId = 8;
        $this->companyHelperMock->expects($this->once())->method('getCustomerCompany')->with($companyId)->willReturn($this->companyInterfaceMock);
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->with('tech_titans_E_475721')->willReturn(true);
        $this->companyInterfaceMock->expects($this->once())->method('getFedexAccountNumber')->willReturn(999);
        $this->companyInterfaceMock->expects($this->never())->method('getDiscountAccountNumber');
        $this->assertEquals('999', $this->helperData->getFedexNdcAccountNumber($companyId));
    }

    /**
     * D-177875  Test getCorrectPriceToggle()
     */
    public function testGetCorrectPriceToggle()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(true, $this->helperData->getCorrectPriceToggle());
    }

    /**
     * D-177875  Test getCorrectPriceToggle()
     */
    public function testCustomerAdminCheck()
    {
        $this->deliveryHelper->expects($this->any())->method('isCustomerAdminUser')->willReturn(true);
        $this->assertNotNull($this->helperData->customerAdminCheck());
    }

    /**
     * Test Case for isMagegeeksD236791ToggleEnabled method
     *
     * @return void
     */
    public function testIsMagegeeksD236791ToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('magegeeks_d236791')
            ->willReturn(true);

        $result = $this->helperData->isMagegeeksD236791ToggleEnabled();
        $this->assertEquals(true, $result);
    }

    /**
     * Test Case for isTechTitansE475721ToggleEnabled method
     *
     * @return void
     */
    public function testIsTechTitansE475721ToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tech_titans_E_475721')
            ->willReturn(true);

        $this->assertTrue($this->helperData->isTechTitansE475721ToggleEnabled());
    }

    /**
     * Test rate API call functionality when Magegeeks toggle is enabled and returns valid response.
     *
     * This test verifies that the rateApiCall method works correctly when:
     * - The Magegeeks toggle is enabled.
     *
     * @return void
     */
    public function testRateApiCallWithMagegeeksToggleEnabledValidResponse()
    {
        $postData = [
            'product' =>
            ['external_prod' => '{"fxoProductInstance": {"productConfig": {
                            "product":{"contentAssociations":[{"parentContentReference":"13284872036123432471801882290520602401471","contentReference":"13284872037059946631120801710221854566831","contentType":"IMAGE","fileName":"noerror.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]}
                        } }}']
        ];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->withConsecutive(
                ['tech_titans_E_475721'],
                ['tech_titans_E_475721'],
                ['tech_titans_d_177875_correct_price_product'],
                ['magegeeks_d236791']
            )->willReturn(true, true, false, true);

        $this->fXORateMock->expects($this->any())->method('callRateApi')->willReturn($this->productRatesData());
        $this->testGetCompanyId();
        $this->testifgetFedexNdcAccountNumber();

        $result = $this->helperData->rateApiCall($postData, 'myeprosite', 8);
        $this->assertEquals($this->productRatesData(), $result);
    }

    /**
     * Test rate API call with Magegeeks toggle enabled and response containing errors.
     *
     * The test expects that when errors are present in the API response,
     * the rateApiCall method should return false, indicating failure.
     *
     * @return void
     */
    public function testRateApiCallWithMagegeeksToggleEnabledResponseWithErrors()
    {
        $postData = [
            'product' =>
            ['external_prod' => '{"fxoProductInstance": {"productConfig": {
                            "product":{"contentAssociations":[{
                            "parentContentReference":"13284872036123432471801882290520602401471",
                            "contentReference":"13284872037059946631120801710221854566831",
                            "contentType":"IMAGE",
                            "fileName":"noerror.png",
                            "contentReqId":"1455709847200",
                            "name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]}
                            "desc":null,
                            "purpose":"SINGLE_SHEET_FRONT",
                            "specialInstructions":"",
                            "printReady":true,
                            "pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}]}
                        } }}']
        ];

        $responseWithErrors = [
            'errors' => ['Some error message'],
            'output' => $this->productRatesData()['output']
        ];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->withConsecutive(
                ['tech_titans_E_475721'],
                ['tech_titans_E_475721'],
                ['tech_titans_d_177875_correct_price_product'],
                ['magegeeks_d236791']
            )->willReturn(true, true, false, true);

        $this->fXORateMock->expects($this->any())->method('callRateApi')->willReturn($responseWithErrors);
        $this->testGetCompanyId();
        $this->testifgetFedexNdcAccountNumber();

        $result = $this->helperData->rateApiCall($postData, 'myeprosite', 8);
        $this->assertEquals(false, $result);
    }
}
