<?php

namespace Fedex\CustomizedMegamenu\Test\Unit\Block\Html;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Fedex\CatalogDocumentUserSettings\Helper\Data;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Fedex\CustomizedMegamenu\Block\Html\CategoryList;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection as SharedCatalogCollection;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as SharedCatalogCollectionFactory;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration;
use Magento\Framework\Registry;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\SharedCatalog\Model\CategoryManagement;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Fedex\Base\Helper\Auth;

/**
 * CategoryList Block Test
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CategoryListTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $categoryModel;
    protected $companyModel;
    protected $companyFactory;
    protected $customer;
    protected $sharedCatalogCollection;
    protected $ondemandHelper;
    protected $catalogMvp;
    protected $deliveryHelper;
    protected $categoryManagement;
    /**
     * @var (\Magento\Catalog\Model\CategoryFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryFactoryMock;
    protected $categoryMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $categoryListMock;
    public const BROWSE_CATALOG = 'My Eprosite Browse Catalog';
    public const PRINT_PRODUCTS = 'Print Products';
    public const STORE_ID = 1;
    public const ROOT_ID = 32;
    public const CUSTOMER_GROUP_ID = 12;
    public const BROWSE_CATALOG_CATEGORY_ID = 9;
    public const PRINT_PRODUCT_CATEGORY_ID = 15;
    public const HAS_CHILDREN = 1;
    public const LEGACY_ROOT_FOLDER_ID = 'f2a7fe67-76ed-479b-8b2c-5157b0f2697a';

    /**
     * @var CategoryList|MockObject
     */
    protected $categoryList;

    /**
     * @var CategoryFactory|MockObject
     */
    protected $categoryFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var CategoryHelper|MockObject
     */
    protected $categoryHelper;

    /**
     * @var Data|MockObject
     */
    protected $helperData;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var SharedCatalogCollectionFactory|MockObject
     */
    private $sharedCatalogCollectionFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var SharedCatalogSyncQueueConfigurationRepository|MockObject
     */
    private $sharedCatalogConfRepository;

    /**
     * @var SharedCatalogSyncQueueConfiguration|MockObject
     */
    private $sharedCatalogConf;

    /**
     * @var SdeHelper
     */
    private $sdeHelper;

    /**
     * @var Registry $registry
     */
    private $registry;

    /**
     * @var CompanyInterface $companyInterface
     */
    private $companyInterface;

    // @codingStandardsIgnoreEnd

    protected $categoryCollection;
    protected $productCollectionMock;

    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'getCollection',
                'getName',
                'getLevel',
                'getCategories',
                'getId',
                'hasChildren',
                'getChildren',
                'getChildrenCategories',
                'getPath'
                ])
            ->getMock();

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load','getName'])
            ->getMock();

        $this->companyModel = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getAllowOwnDocument', 'getAllowSharedCatalog'])
            ->getMock();

        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSharedCatalogId'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getRootCategoryId'])
            ->getMock();

        $this->categoryHelper = $this->getMockBuilder(CategoryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryUrl'])
            ->getMock();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyConfiguration'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId','getData'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getCustomer','getOndemandCompanyInfo'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->sharedCatalogCollectionFactory = $this->getMockBuilder(SharedCatalogCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->sharedCatalogCollection = $this->getMockBuilder(SharedCatalogCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getSize', 'getId', 'getFirstItem', 'getCategoryId'])
            ->getMock();

        $this->ondemandHelper = $this->getMockBuilder(Ondemand::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProductAvailable','isPublishCategory'])
            ->getMock();

        $this->categoryCollection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addFieldToFilter',
                'addAttributeToSelect',
                'addAttributeToFilter',
                'getSize',
                'getFirstItem',
                'getId',
                'setStoreId',
                'setOrder',
                'getIterator',
                'count'
            ])->getMock();
        $this->logger = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this
            ->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue','getToggleConfig'])
            ->getMock();
        $this->sharedCatalogConfRepository = $this
            ->getMockBuilder(SharedCatalogSyncQueueConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBySharedCatalogId'])
            ->getMock();
        $this->sharedCatalogConf = $this
            ->getMockBuilder(SharedCatalogSyncQueueConfiguration::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus', 'getCategoryId'])
            ->getMock();
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore'])
            ->getMock();
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry','getId'])
            ->getMock();

        $this->catalogMvp = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable','getCurrentCategory','getCompanySharedCatName','folderPermissionToggle','isFolderPermissionAllowed','getParentGroupId','isSelfRegCustomerAdmin','getDenyCategoryIds','isSharedCatalogPermissionEnabled', 'getUserGroupsforCompany', 'getCategoryPermission', 'isEditFolderAccessEnabled', 'isD212350FixEnabled', 'isRestictedFoldersSyncEnabled'])
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEproCustomer','isCommercialCustomer', 'toggleEnableIcons'])
            ->getMock();
        $this->categoryManagement = $this->getMockBuilder(CategoryManagement::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategories'])
            ->getMock();

        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getData','getChildrenCount','getLevel','getUrl','getId','getName','getProductCollection'])
            ->getMock();



        $this->productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['count'])
            ->getMock();


        $this->objectManager = new ObjectManager($this);
        $this->categoryListMock = $this->objectManager->getObject(
            CategoryList::class,
            [
                'sdeHelper'                      => $this->sdeHelper,
                'Context'                        => $this->context,
                'categoryFactory'                => $this->categoryFactory,
                'storeManager'                   => $this->storeManager,
                'categoryHelper'                 => $this->categoryHelper,
                'helperData'                     => $this->helperData,
                'customerSession'                => $this->customerSession,
                'sharedCatalogCollectionFactory' => $this->sharedCatalogCollectionFactory,
                'toggleConfig'                   => $this->toggleConfigMock,
                'sharedCatalogConfRepository'    => $this->sharedCatalogConfRepository,
                'logger'                         => $this->logger,
                'registry'                       => $this->registry,
                'ondemandHelper'                 => $this->ondemandHelper,
                'catalogMvp'                     => $this->catalogMvp,
                'deliveryHelper' => $this->deliveryHelper,
                'categoryManagement' => $this->categoryManagement,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test make menu conditions
     * @return void
     */
    public function testMakeMenuConditions()
    {
        $iconUrls = [
            'folder' => 'folder_icon_url',
            'childFolder' => 'child_icon_url',
            'closedIcon' => 'closed_icon_url',
        ];
        $childrenCount = 3;
        $isTopLevel = true;
        $html = '';

        $this->categoryMock->expects($this->once())
            ->method('getUrl')
            ->willReturn('category_url');

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('test category');

        $resultHtml = $this->categoryListMock
            ->makeMenuConditions($iconUrls, $childrenCount, $isTopLevel, $html, $this->categoryMock);
        $this->assertNotNull($resultHtml);
    }


    /**
     * Test Case for getLeftNavigationCategories With Optimize code
     */
    public function testGetLeftNavigationCategoriesWithOptimization()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('toggleEnableIcons')->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfig')->willReturn(35);

        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(true);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);
        $this->customer->expects($this->any())->method('getData')->willReturn(23);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn(['company_type' => "selfreg"]);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('count')->willReturn(2);
        $this->categoryCollection->expects($this->any())
             ->method('getIterator')
             ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())->method('getLevel')->willReturn(3);

        $this->categoryMock
        ->method('getChildrenCount')
        ->withConsecutive([],[],[])
        ->willReturnOnConsecutiveCalls(
            1,
            1,
            0
        );

        $this->categoryMock->expects($this->any())->method('getId')->willReturn('23');
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('Catalog Items');
        $this->categoryMock->expects($this->any())->method('getUrl')->willReturn('https://staging3.office.fedex.com/');
        $this->categoryMock->expects($this->any())->method('getProductCollection')->willReturn($this->productCollectionMock);
        $this->productCollectionMock->expects($this->any())->method('count')->willReturn(23);
        $this->catalogMvp->expects($this->any())->method('getDenyCategoryIds')->willReturn([]);
        $this->catalogMvp->expects($this->any())->method('getCategoryPermission')->willReturn(false);
        $this->catalogMvp->expects($this->any())->method('isD212350FixEnabled')->willReturn(false);
        $this->catalogMvp->expects($this->any())->method('isRestictedFoldersSyncEnabled')->willReturn(false);
        $this->categoryListMock->getLeftNavigationCategories();
    }
     /**
     * Return current store categories tree
     */
    public function testGetLeftNavigationCategories()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(self::ROOT_ID);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
       // $this->registry->expects($this->any())->method('registry')->with('current_category')->willReturnSelf();
        $this->catalogMvp->expects($this->any())->method('getCurrentCategory')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(self::CUSTOMER_GROUP_ID);

        $this->catalogMvp->expects($this->any())->method('folderPermissionToggle')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('isFolderPermissionAllowed')->willReturn(true);

        $this->catalogMvp->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('getUserGroupsforCompany')->willReturn(['1', '2', '3']);

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->sharedCatalogCollection);

        $item = new DataObject(
            [
                'id' => 1,
                'name' => 'myeprosite',
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'legacy_catalog_root_folder_id' => self::LEGACY_ROOT_FOLDER_ID
            ]
        );
        $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
            ->with('customer_group_id', ['eq' => self::CUSTOMER_GROUP_ID])
            ->willReturnSelf();
        $this->sharedCatalogCollection->addItem($item);
        $this->sharedCatalogCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->sharedCatalogConfRepository->expects($this->any())->method('getBySharedCatalogId')
            ->willReturn($this->sharedCatalogConf);
        $this->sharedCatalogConf->expects($this->any())->method('getStatus')
            ->willReturn(1);
        $this->sharedCatalogConf->expects($this->any())->method('getCategoryId')
            ->willReturn(self::BROWSE_CATALOG_CATEGORY_ID);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['name',['eq' => self::PRINT_PRODUCTS]],
                ['path',['like' => '1/' . self::ROOT_ID .'/%']],
                ['include_in_menu',['eq' => 1]],
                ['is_active', ['eq' => 1] ],
                ['path', [['like' => '1/' . self::ROOT_ID . '/' . self::BROWSE_CATALOG_CATEGORY_ID .'%'],
                ['like' => '1/' . self::ROOT_ID . '/' . self::PRINT_PRODUCT_CATEGORY_ID .'%']
                ]]
            )->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getId')->willReturn(self::PRINT_PRODUCT_CATEGORY_ID);
        $this->categoryCollection->expects($this->any())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn(self::PRINT_PRODUCTS);
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);

        $categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getLevel', 'hasChildren', 'getChildrenCategories'])
            ->getMock();

        $this->categoryModel->expects($this->any())->method('getChildrenCategories')->willReturn([$categoryModel]);
        $categoryModel->expects($this->any())->method('getName')->willReturn("@Test1");

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->with(5)->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(true);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);

        //$this->categoryListMock->getLeftNavigationCategories();
    }

    /**
     * testGetLeftNavigationCategoriesWithOwnDocumentTrue Test method
     */
    public function testGetLeftNavigationCategoriesWithOwnDocumentTrue()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(self::ROOT_ID);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(self::CUSTOMER_GROUP_ID);

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->sharedCatalogCollection);

        $item = new DataObject(
            [
                'id' => 1,
                'name' => 'myeprosite',
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'legacy_catalog_root_folder_id' => self::LEGACY_ROOT_FOLDER_ID
            ]
        );
        $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
            ->with('customer_group_id', ['eq' => self::CUSTOMER_GROUP_ID])
            ->willReturnSelf();
        $this->sharedCatalogCollection->addItem($item);
        $this->sharedCatalogCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();

        $this->sharedCatalogConfRepository->expects($this->any())->method('getBySharedCatalogId')
            ->willReturn($this->sharedCatalogConf);
        $this->sharedCatalogConf->expects($this->any())->method('getStatus')
            ->willReturn(1);
        $this->sharedCatalogConf->expects($this->any())->method('getCategoryId')
            ->willReturn(self::BROWSE_CATALOG_CATEGORY_ID);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['include_in_menu',['eq' => 1]],
                ['is_active', ['eq' => 1] ],
                ['path',['like' => '1/' . self::ROOT_ID .'/%']],
                ['path', [['like' => '1/' . self::ROOT_ID . '/' . self::BROWSE_CATALOG_CATEGORY_ID .'%'],
                ['like' => '1/' . self::ROOT_ID . '/' . self::PRINT_PRODUCT_CATEGORY_ID .'%']
                ]]
            )->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getId')->willReturn(self::PRINT_PRODUCT_CATEGORY_ID);
        $this->categoryCollection->expects($this->any())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn(self::PRINT_PRODUCTS);
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);

        $categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getLevel', 'hasChildren', 'getChildrenCategories'])
            ->getMock();

        $this->categoryModel->expects($this->any())->method('getChildrenCategories')->willReturn([$categoryModel]);
        $categoryModel->expects($this->any())->method('getName')->willReturn("@Test1");

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->with(5)->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(true);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);
        $this->registry->expects($this->any())->method('registry')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn("2/45/89");


        $this->categoryListMock->getLeftNavigationCategories();
    }

    /**
     * testGetLeftNavigationCategoriesWithEmptyPrintProductCategory test method
     */
    public function testGetLeftNavigationCategoriesWithEmptyPrintProductCategory()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(self::ROOT_ID);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(self::CUSTOMER_GROUP_ID);

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->sharedCatalogCollection);

        $item = new DataObject(
            [
                'id' => 1,
                'name' => 'myeprosite',
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'legacy_catalog_root_folder_id' => self::LEGACY_ROOT_FOLDER_ID
            ]
        );
        $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
            ->with('customer_group_id', ['eq' => self::CUSTOMER_GROUP_ID])
            ->willReturnSelf();
        $this->sharedCatalogCollection->addItem($item);
        $this->sharedCatalogCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();

        $this->sharedCatalogConfRepository->expects($this->any())->method('getBySharedCatalogId')
            ->willReturn($this->sharedCatalogConf);
        $this->sharedCatalogConf->expects($this->any())->method('getStatus')
            ->willReturn(1);
        $this->sharedCatalogConf->expects($this->any())->method('getCategoryId')
            ->willReturn(self::BROWSE_CATALOG_CATEGORY_ID);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['include_in_menu',['eq' => 1]],
                ['is_active', ['eq' => 1] ],
                ['path',['like' => '1/' . self::ROOT_ID .'/%']],
                ['path', ['like' => '1/' . self::ROOT_ID . '/' . self::BROWSE_CATALOG_CATEGORY_ID .'%']]
            )->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getId')->willReturn(null);
        $this->categoryCollection->expects($this->any())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn(self::PRINT_PRODUCTS);
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);

        $categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getLevel', 'hasChildren', 'getChildrenCategories'])
            ->getMock();

        $this->categoryModel->expects($this->any())->method('getChildrenCategories')->willReturn([$categoryModel]);
        $categoryModel->expects($this->any())->method('getName')->willReturn("@Test1");

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->with(5)->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(false);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);
        $this->registry->expects($this->any())->method('registry')->with('current_category')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn("2/45/89");
        $this->categoryListMock->getLeftNavigationCategories();
    }



    /**
     * Return current store categories tree with Exception
     */
    public function testGetLeftNavigationCategoriesWithException()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(self::ROOT_ID);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(self::CUSTOMER_GROUP_ID);

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->sharedCatalogCollection);

        $item = new DataObject(
            [
                'id' => 1,
                'name' => 'myeprosite',
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'legacy_catalog_root_folder_id' => self::LEGACY_ROOT_FOLDER_ID
            ]
        );
        // $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
        //     ->with('customer_group_id', ['eq' => self::CUSTOMER_GROUP_ID])
        //     ->willReturnSelf();
        $this->sharedCatalogCollection->addItem($item);
        $this->sharedCatalogCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();

        $this->sharedCatalogConfRepository->expects($this->any())->method('getBySharedCatalogId')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getId')->willReturn(self::PRINT_PRODUCT_CATEGORY_ID);
        $this->categoryCollection->expects($this->any())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn(self::PRINT_PRODUCTS);
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);

        $categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getLevel', 'hasChildren', 'getChildrenCategories'])
            ->getMock();

        $this->categoryModel->expects($this->any())->method('getChildrenCategories')->willReturn([$categoryModel]);
        $categoryModel->expects($this->any())->method('getName')->willReturn("@Test1");

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(false);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);

        //$this->categoryListMock->getLeftNavigationCategories();
    }

    /**
     * Return current store categories tree
     */
    public function testGetLeftNavigationCategoriesWithoutStoreId()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $storeId = 0;
        $hasChildren = 0;

        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->exactly(2))->method('addFieldToFilter')
            ->withConsecutive(['include_in_menu',['eq' => 1]], ['is_active', ['eq' => 1]])->willReturnSelf();

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn('My Eprosite Browse Catalog');
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn($hasChildren);

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(true);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);

        $this->categoryListMock->getLeftNavigationCategories();
    }

    /**
     * testGetLeftNavigationCategoriesWithNoCategoryMatch Test method
     */
    public function testGetLeftNavigationCategoriesWithNoCategoryMatch()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(self::ROOT_ID);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(self::CUSTOMER_GROUP_ID);

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->sharedCatalogCollection);

        $item = new DataObject(
            [
                'id' => 1,
                'name' => 'myeprosite',
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'legacy_catalog_root_folder_id' => self::LEGACY_ROOT_FOLDER_ID
            ]
        );
        $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
            ->with('customer_group_id', ['eq' => self::CUSTOMER_GROUP_ID])
            ->willReturnSelf();
        $this->sharedCatalogCollection->addItem($item);
        $this->sharedCatalogCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();

        $this->sharedCatalogConfRepository->expects($this->any())->method('getBySharedCatalogId')
            ->willReturn($this->sharedCatalogConf);
        $this->sharedCatalogConf->expects($this->any())->method('getStatus')
            ->willReturn(1);
        $this->sharedCatalogConf->expects($this->any())->method('getCategoryId')
            ->willReturn(self::BROWSE_CATALOG_CATEGORY_ID);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['include_in_menu',['eq' => 1]],
                ['is_active', ['eq' => 1] ],
                ['path',['like' => '1/' . self::ROOT_ID .'/%']],
                ['path', ['like' => '1/' . self::ROOT_ID . '/' . self::BROWSE_CATALOG_CATEGORY_ID .'%']]
            )->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getId')->willReturn(null);
        $this->categoryCollection->expects($this->any())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn(self::BROWSE_CATALOG);
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);

        $categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getLevel', 'hasChildren', 'getChildrenCategories'])
            ->getMock();

        $this->categoryModel->expects($this->any())->method('getChildrenCategories')->willReturn([$categoryModel]);
        $categoryModel->expects($this->any())->method('getName')->willReturn("@Test1");

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->with(5)->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(true);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(false);
        $this->registry->expects($this->any())->method('registry')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn("2/45/89");
        $this->categoryListMock->getLeftNavigationCategories();
    }

    /**
     * testGetLeftNavigationCategoriesWithNoDocCheck Test method
     */
    public function testGetLeftNavigationCategoriesWithNoDocCheck()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(self::ROOT_ID);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->customerSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(self::CUSTOMER_GROUP_ID);

        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->categoryManagement->expects($this->any())->method('getCategories')->willReturn(['15','19']);

        $this->sharedCatalogCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->sharedCatalogCollection);

        $item = new DataObject(
            [
                'id' => 1,
                'name' => 'myeprosite',
                'customer_group_id' => self::CUSTOMER_GROUP_ID,
                'legacy_catalog_root_folder_id' => self::LEGACY_ROOT_FOLDER_ID
            ]
        );
        $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
            ->with('customer_group_id', ['eq' => self::CUSTOMER_GROUP_ID])
            ->willReturnSelf();
        $this->sharedCatalogCollection->addItem($item);
        $this->sharedCatalogCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->sharedCatalogCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();

        $this->sharedCatalogConfRepository->expects($this->any())->method('getBySharedCatalogId')
            ->willReturn($this->sharedCatalogConf);
        $this->sharedCatalogConf->expects($this->any())->method('getStatus')
            ->willReturn(1);
        $this->sharedCatalogConf->expects($this->any())->method('getCategoryId')
            ->willReturn(self::BROWSE_CATALOG_CATEGORY_ID);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getCollection')->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addFieldToFilter')
            ->withConsecutive(
                ['include_in_menu',['eq' => 1]],
                ['is_active', ['eq' => 1] ],
                ['path',['like' => '1/' . self::ROOT_ID .'/%']],
                ['path', ['like' => '1/' . self::ROOT_ID . '/' . self::BROWSE_CATALOG_CATEGORY_ID .'%']]
            )->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->categoryCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getId')->willReturn(null);
        $this->categoryCollection->expects($this->any())->method('setStoreId')->with(self::STORE_ID)->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryModel]));

        $this->categoryModel->expects($this->any())->method('getName')->willReturn(self::BROWSE_CATALOG);
        $this->categoryModel->expects($this->any())->method('getLevel')->willReturn(2);
        $this->categoryModel->expects($this->any())->method('hasChildren')->willReturn(self::HAS_CHILDREN);

        $categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getLevel', 'hasChildren', 'getChildrenCategories'])
            ->getMock();

        $this->categoryModel->expects($this->any())->method('getChildrenCategories')->willReturn([$categoryModel]);
        $categoryModel->expects($this->any())->method('getName')->willReturn("@Test1");

        $this->companyFactory->expects($this->any())->method('create')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('load')->with(5)->willReturn($this->companyModel);
        $this->helperData->expects($this->any())->method('getCompanyConfiguration')->willReturn($this->companyModel);
        $this->companyModel->expects($this->any())->method('getAllowOwnDocument')->willReturn(true);
        $this->companyModel->expects($this->any())->method('getAllowSharedCatalog')->willReturn(true);
        $this->catalogMvp->expects($this->any())->method('getCurrentCategory')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn("2/45/89");

        $this->categoryListMock->getLeftNavigationCategories();
    }

    /**
     * Test for child category exit
     */
    public function testAddCarretIcon()
    {
        $hasCategoryChildren = 1;
        $carretTag = "<i class='expend-class'> < </i>";
        $this->categoryListMock->addCarretIcon($hasCategoryChildren);
    }

    /**
     * Test for child category exit
     */
    public function testAddCarretIconWithoutChild()
    {
        $hasCategoryChildren = 0;
        $carretTag = "";
        $this->assertEquals($carretTag, $this->categoryListMock->addCarretIcon($hasCategoryChildren));
    }

    /**
     * Test for can open category by default
     */
    public function testCanOpenCategoryFilterByDefault()
    {
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        $this->assertEquals(true, $this->categoryListMock->canOpenCategoryFilterByDefault(25));
    }

    /**
     * Test for can open category by default with false return
     */
    public function testCanOpenCategoryFilterByDefaultFalseReturn()
    {
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(0);
        $this->catalogMvp->expects($this->any())->method('getCurrentCategory')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn('2/5/23');
        $this->assertEquals(true, $this->categoryListMock->canOpenCategoryFilterByDefault(5));
    }

    /**
     * Test for can open category by default with false return when no category id
     */
    public function testCanOpenCategoryFilterByDefaultFalseReturnWithoutCatId()
    {
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(0);
        $this->registry->expects($this->any())->method('registry')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn('2/5/23');
        $this->assertEquals(false, $this->categoryListMock->canOpenCategoryFilterByDefault(null));
    }
    /**
     * Test for checkIfBrowseCat
     */
    public function testcheckIfBrowseCatMvp()
    {
        $this->catalogMvp->expects($this->any())->method('getCompanySharedCatName')->willReturn("test");
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->assertNotNull($this->categoryListMock->checkIfBrowseCat('Shared Catalog'));
    }

    /**
     * Test for checkIfBrowseCat
     */
    public function testcheckIfBrowseCatMvpToogleDisabled()
    {
        $this->catalogMvp->expects($this->any())->method('getCompanySharedCatName')->willReturn("");
        $this->catalogMvp->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->assertNotNull($this->categoryListMock->checkIfBrowseCat('Browse Catalog'));
    }

    /**
     * Test for getOndemandHelper | B-1598909
     */
    public function testGetOndemandHelper()
    {
        $this->categoryListMock->getOndemandHelper();
    }

    /**
     * testCanOpenCategoryFilterByDefaultForChild
     */
    public function testCanOpenCategoryFilterByDefaultForChild() {
        $this->catalogMvp->expects($this->any())->method('getCurrentCategory')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn('2/5/23');
        $this->assertEquals('active', $this->categoryListMock->canOpenCategoryFilterByDefaultForChild(23));
    }

    /**
     * testCanOpenCategoryFilterByDefaultForChildforExpand
     */
    public function testCanOpenCategoryFilterByDefaultForChildforExpand() {

        $this->catalogMvp->expects($this->any())->method('getCurrentCategory')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn('2/5/23');
        $this->assertEquals('expand', $this->categoryListMock->canOpenCategoryFilterByDefaultForChild(5));
    }
}
