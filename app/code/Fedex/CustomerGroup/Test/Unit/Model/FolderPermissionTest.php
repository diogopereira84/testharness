<?php

namespace Fedex\CustomerGroup\Test\Unit\Model;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\SaaSCommon\Api\ConfigInterface as FedexSaaSCommonConfig;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Customer\Model\Group;
use PHPUnit\Framework\TestCase;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as CatalogPermissionCollectionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection as CatalogPermissionCollection;

class FolderPermissionTest extends TestCase
{

    protected $resourceConnectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    protected $categoryRepositoryMock;
    protected $categoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    protected $productCollectionFactory;
    protected $productCollection;
    /**
     * @var (\Magento\Catalog\Model\Product\Action & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productActionMock;
    protected $productMock;
    protected $folderPermission;
    protected $collectionFactoryMock;
    protected $catalogMvpHelperMock;
    protected $catalogPermissionCollectionFactoryMock;
    protected $toggleConfigMock;
    protected $fedexSaaSCommonConfigMock;
    protected $customerGroupAttributeHandlerMock;

    const SHAREDCATALOG_PERMISSION_TABLE = 'sharedcatalog_category_permissions';

    const MAGENTOCATALOG_PERMISSION_TABLE = 'magento_catalogpermissions';


    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName','delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['distinct', 'from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getParentId'])
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect', 'addFieldToFilter', 'addCategoriesFilter', 'load','getSize', 'getItems', 'getIterator'])
            ->getMock();

        $this->productActionMock = $this->getMockBuilder(ProductAction::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateAttributes'])
            ->getMockForAbstractClass();

        $this->productMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getData'
            ])->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CatalogPermissionCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->catalogMvpHelperMock = $this->createMock(CatalogMvp::class);
        $this->catalogPermissionCollectionFactoryMock = $this->createMock(CatalogPermissionCollectionFactory::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->fedexSaaSCommonConfigMock = $this->createMock(FedexSaaSCommonConfig::class);
        $this->customerGroupAttributeHandlerMock = $this->createMock(CustomerGroupAttributeHandlerInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->folderPermission = $objectManagerHelper->getObject(
            FolderPermission::class,
            [
                'resourceConnection' =>  $this->resourceConnectionMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'productCollectionFactory' => $this->productCollectionFactory,
                'productAction' => $this->productActionMock,
                'logger' => $this->loggerInterfaceMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'catalogPermissionCollectionFactory' => $this->collectionFactoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                'fedexSaaSCommonConfig' => $this->fedexSaaSCommonConfigMock,
                'customerGroupAttributeHandler' => $this->customerGroupAttributeHandlerMock,
            ]
        );
    }

    /**
     * Test MapCategoriesCustomerGroup
     *
     */
    public function testMapCategoriesCustomerGroup()
    {
        $categoryIds = [1,2];
        $newGroupId = 10;
        $parentData = 11;
        $fetchData = [0 => ['permission_id' => 1,'category_id' => 1, 'website_id' => null,'customer_group_id'=>12,'permission'=>-2]];

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::SHAREDCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn(1);

        $this->categoryRepositoryMock->expects($this->any())->method('get')
        ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
        ->willReturn(1);

        $this->assertEquals(null, $this->folderPermission->mapCategoriesCustomerGroup($categoryIds, $parentData, $newGroupId));
    }

    /**
     * Test MapCategoriesCustomerGroup witth Exception
     *
     */
    public function testMapCategoriesCustomerGroupwithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $categoryIds = [1,2];
        $newGroupId = 10;
        $parentData = 11;

        $fetchData = [0 => ['permission_id' => 1,'category_id' => 1, 'website_id' => null,'customer_group_id'=>12,'permission'=>-2]];

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('delete')->willThrowException($exception);

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::SHAREDCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willThrowException($exception);
        $this->adapterInterfaceMock->method('fetchOne')->willThrowException($exception);
        $this->adapterInterfaceMock->method('insert')->willThrowException($exception);

        $this->categoryRepositoryMock->expects($this->any())->method('get')
        ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
        ->willReturn(1);

        $this->assertEquals(null, $this->folderPermission->mapCategoriesCustomerGroup($categoryIds, $parentData, $newGroupId));
    }

    /**
     * Test MapCategoriesCustomerGroup witth Magento Catalog table
     *
     */
    public function testMapCategoriesCustomerGroupwithMagentoCatalog()
    {
        $categoryIds = [1,2];
        $newGroupId = 10;
        $parentData = 11;
        $fetchData = [0 => ['permission_id' => 1,'category_id' => 12, 'website_id' => null,'customer_group_id'=>12,'grant_catalog_category_view'=>-1,'grant_catalog_product_price'=>-1,'grant_checkout_items'=>-1]];

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);

        $this->categoryRepositoryMock->expects($this->any())->method('get')
        ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
        ->willReturn(1);

        $this->assertEquals(null, $this->folderPermission->mapCategoriesCustomerGroup($categoryIds, $parentData, $newGroupId));
    }

    public function testMapCategoriesCustomerGroup_CallsAttributeHandlerWhenTigerD200529Enabled()
    {
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $productCollectionFactory = $this->createMock(CollectionFactory::class);
        $productAction = $this->createMock(Action::class);
        $logger = $this->createMock(LoggerInterface::class);
        $catalogMvpHelper = $this->createMock(CatalogMvp::class);
        $catalogPermissionCollectionFactory = $this->createMock(CatalogPermissionCollectionFactory::class);
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $fedexSaaSCommonConfig = $this->createMock(FedexSaaSCommonConfig::class);
        $customerGroupAttributeHandler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);

        $fedexSaaSCommonConfig->method('isTigerD200529Enabled')->willReturn(true);

        $customerGroupAttributeHandler->expects($this->once())
            ->method('addAttributeOption')
            ->with([123]);

        $customerGroupAttributeHandler->expects($this->once())
            ->method('pushEntityToQueue')
            ->with($this->equalTo(123), $this->equalTo(Group::ENTITY));

        $folderPermission = $this->getMockBuilder(FolderPermission::class)
            ->setConstructorArgs([
                $resourceConnection,
                $categoryRepository,
                $productCollectionFactory,
                $productAction,
                $logger,
                $catalogMvpHelper,
                $catalogPermissionCollectionFactory,
                $toggleConfig,
                $fedexSaaSCommonConfig,
                $customerGroupAttributeHandler
            ])
            ->onlyMethods([
                'deletePermissions',
                'getParentGroupPermissions',
                'insertParentGroupPermissions',
                'insertNewCategoryPermissions',
                'insertDenyAllPermissions'
            ])
            ->getMock();

        // Mock other methods to avoid side effects
        $folderPermission->method('deletePermissions');
        $folderPermission->method('getParentGroupPermissions')->willReturn([]);
        $folderPermission->method('insertParentGroupPermissions');
        $folderPermission->method('insertNewCategoryPermissions');
        $folderPermission->method('insertDenyAllPermissions');

        $folderPermission->mapCategoriesCustomerGroup([1,2], 10, 123);
    }

    /**
     * Test MapCategoriesCustomerGroup does NOT call addAttributeOption when $isEdit = true
     */
    public function testMapCategoriesCustomerGroup_DoesNotCallAttributeHandlerWhenIsEditTrue()
    {
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $productCollectionFactory = $this->createMock(CollectionFactory::class);
        $productAction = $this->createMock(Action::class);
        $logger = $this->createMock(LoggerInterface::class);
        $catalogMvpHelper = $this->createMock(CatalogMvp::class);
        $catalogPermissionCollectionFactory = $this->createMock(CatalogPermissionCollectionFactory::class);
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $fedexSaaSCommonConfig = $this->createMock(FedexSaaSCommonConfig::class);
        $customerGroupAttributeHandler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);

        $fedexSaaSCommonConfig->method('isTigerD200529Enabled')->willReturn(true);

        // Assert addAttributeOption is NOT called
        $customerGroupAttributeHandler->expects($this->never())
            ->method('addAttributeOption');

        $folderPermission = $this->getMockBuilder(FolderPermission::class)
            ->setConstructorArgs([
                $resourceConnection,
                $categoryRepository,
                $productCollectionFactory,
                $productAction,
                $logger,
                $catalogMvpHelper,
                $catalogPermissionCollectionFactory,
                $toggleConfig,
                $fedexSaaSCommonConfig,
                $customerGroupAttributeHandler
            ])
            ->onlyMethods([
                'deletePermissions',
                'getParentGroupPermissions',
                'insertParentGroupPermissions',
                'insertNewCategoryPermissions',
                'insertDenyAllPermissions'
            ])
            ->getMock();

        // Mock other methods to avoid side effects
        $folderPermission->method('deletePermissions');
        $folderPermission->method('getParentGroupPermissions')->willReturn([]);
        $folderPermission->method('insertParentGroupPermissions');
        $folderPermission->method('insertNewCategoryPermissions');
        $folderPermission->method('insertDenyAllPermissions');

        // Call with $isEdit = true
        $folderPermission->mapCategoriesCustomerGroup([1,2], 10, 123, true);
    }

    public function testMapCategoriesCustomerGroup_DoesNotCallAttributeHandlerWhenTigerD200529Disabled()
    {
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $productCollectionFactory = $this->createMock(CollectionFactory::class);
        $productAction = $this->createMock(Action::class);
        $logger = $this->createMock(LoggerInterface::class);
        $catalogMvpHelper = $this->createMock(CatalogMvp::class);
        $catalogPermissionCollectionFactory = $this->createMock(CatalogPermissionCollectionFactory::class);
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $fedexSaaSCommonConfig = $this->createMock(FedexSaaSCommonConfig::class);
        $customerGroupAttributeHandler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);

        $fedexSaaSCommonConfig->method('isTigerD200529Enabled')->willReturn(false);

        $customerGroupAttributeHandler->expects($this->never())
            ->method('addAttributeOption');

        $customerGroupAttributeHandler->expects($this->never())
            ->method('pushEntityToQueue');

        $folderPermission = $this->getMockBuilder(FolderPermission::class)
            ->setConstructorArgs([
                $resourceConnection,
                $categoryRepository,
                $productCollectionFactory,
                $productAction,
                $logger,
                $catalogMvpHelper,
                $catalogPermissionCollectionFactory,
                $toggleConfig,
                $fedexSaaSCommonConfig,
                $customerGroupAttributeHandler
            ])
            ->onlyMethods([
                'deletePermissions',
                'getParentGroupPermissions',
                'insertParentGroupPermissions',
                'insertNewCategoryPermissions',
                'insertDenyAllPermissions'
            ])
            ->getMock();

        // Mock other methods to avoid side effects
        $folderPermission->method('deletePermissions');
        $folderPermission->method('getParentGroupPermissions')->willReturn([]);
        $folderPermission->method('insertParentGroupPermissions');
        $folderPermission->method('insertNewCategoryPermissions');
        $folderPermission->method('insertDenyAllPermissions');

        $folderPermission->mapCategoriesCustomerGroup([1,2], 10, 123);
    }

    /**
     * Test CheckCategoryPermission
     */
    public function testCheckCategoryPermission()
    {
        $categoryId = 1;
        $groupId = 10;

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchOne')->willReturn('1');

        $this->assertEquals(false, $this->folderPermission->checkCategoryPermission($categoryId, $groupId));
    }
    /**
     * Test CheckCategoryPermissionwithElse
     */
    public function testCheckCategoryPermissionwithElse()
    {
        $categoryId = 1;
        $groupId = 10;

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchOne')->willReturn('0');

        $this->categoryRepositoryMock->expects($this->any())->method('get')
        ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
        ->willReturn(1);

        $this->assertEquals(false, $this->folderPermission->checkCategoryPermission($categoryId, $groupId));
    }
    /**
     * Test AssignCustomerGroupId
     */
    public function testAssignCustomerGroupId()
    {
        $categoryIds = [1,2];

        $groupId = 2;

        $isProductIds = false;

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('load')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('getSize')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('getItems')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())->method('getIterator')
        ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productMock->expects($this->any())->method('getData')->willReturn('3,4');

        $this->assertEquals(null, $this->folderPermission->assignCustomerGroupId($categoryIds, $groupId, $isProductIds));
    }
    /**
     * Test AssignCustomerGroupId with true
     */
    public function testAssignCustomerGroupIdwithTrue()
    {
        $categoryIds = [1,2];

        $groupId = 2;

        $isProductIds = true;

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('load')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('getSize')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('getItems')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())->method('getIterator')
        ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productMock->expects($this->any())->method('getData')->willReturn('3,4');

        $this->assertEquals(null, $this->folderPermission->assignCustomerGroupId($categoryIds, $groupId, $isProductIds));
    }
    /**
     * Test UnAssign Customer GroupId
     */
    public function testUnAssignCustomerGroupId()
    {
        $categoryIds = [1,2];

        $groupId = 2;

        $isProductIds = false;

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('load')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('getSize')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('getItems')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())->method('getIterator')
        ->willReturn(new \ArrayIterator([$this->productMock]));

        $this->productMock->expects($this->any())->method('getData')->willReturn('1,2');

        $this->assertEquals(null, $this->folderPermission->unAssignCustomerGroupId($categoryIds, $groupId, $isProductIds));
    }
    /**
     * Test Get Customer Group Ids
     */
    public function testGetCustomerGroupIds()
    {
        $categoryIds = [1,2];
        $groupIds = ['1'];
        $fetchData = [['customer_group_id'=>'1']];
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn('1');

        $this->assertEquals($groupIds, $this->folderPermission->getCustomerGroupIds($categoryIds));
    }
    /**
     * Test Get Customer Group Ids
     */
    public function testGetCustomerGroupIdswithFalse()
    {
        $categoryIds = [1,2];
        $groupIds = [];
        $fetchData = [['customer_group_id'=>'1']];
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn(0);

        $this->assertEquals($groupIds, $this->folderPermission->getCustomerGroupIds($categoryIds));
    }
    /**
     * Test Get Customer Group Ids
     */
    public function testGetCustomerGroupIdswithException()
    {
        $categoryIds = [1,2];
        $groupIds = [];
        $fetchData = [['customer_group_id'=>'1']];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);
        $this->adapterInterfaceMock->method('fetchOne')->willThrowException($exception);

        $this->assertEquals($groupIds, $this->folderPermission->getCustomerGroupIds($categoryIds));
    }
    /**
     * Test Get Un Assigned Categories
     */
    public function testGetUnAssignedCategories()
    {
        $categoryIds = [1,2];
        $groupId = 1;
        $fetchData = [['category_id'=>3],['category_id'=>4]];
        $categories = [3,4];
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);

        $this->assertEquals($categories, $this->folderPermission->getUnAssignedCategories($groupId, $categoryIds));
    }

    /**
     * Test updatePermissions
     */
    public function testUpdatePermissions()
    {
        $fetchData = [];
        $categories = [3, 4];

        $permissionCollectionMock = $this->createMock(CatalogPermissionCollection::class);

        $this->collectionFactoryMock->method('create')->willReturn($permissionCollectionMock);
        $permissionCollectionMock->method('addFieldToSelect')->willReturnSelf();
        $permissionCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionCollectionMock->method('getSelect')->willReturnSelf();
        $permissionCollectionMock->method('getColumnValues')->willReturn([]);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')
            ->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')
            ->willReturn(self::SHAREDCATALOG_PERMISSION_TABLE);

        $this->folderPermission->updatePermissions($categories);
    }
}
