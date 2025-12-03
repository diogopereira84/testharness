<?php

namespace Fedex\CatalogMvp\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Category;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\Registry;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
/* B-1573026 */
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Framework\DB\Adapter\Pdo\Mysql\Interceptor;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Product\Action;
use Magento\Customer\Model\Customer;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\CatalogMvp\Model\ProductActivity;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer;
use Fedex\SelfReg\Model\CustomerGroupPermissionManager;

class CatalogMvpTest extends TestCase
{
    protected $moduleDataSetup;
    protected $nodeFactory;
    protected $mysqlInterceptor;
    protected $resourceConnectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfigMock;
    protected $productRepositoryMock;
    protected $categoryMock;
    protected $productMock;
    protected $subcategoriesMock;
    protected $requestMock;
    protected $scopeConfigMock;
    protected $storeMangerMock;
    protected $storeInterfaceMock;
    protected $categoryManagementInterfaceMock;
    protected $categoryTreeInterfaceMock;
    /**
     * @var (\Magento\Framework\Exception\NoSuchEntityException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $noSuchEntityInterfaceMock;
    protected $loggerInterfaceMock;
    protected $attributeSetRepositoryMock;
    protected $attributeSetInterfaceMock;
    protected $attributeInterfaceMock;
    /**
     * @var (\Magento\Company\Model\CompanyFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyFactoryMock;
    protected $companyMock;
    /**
     * @var (\Magento\Company\Model\ResourceModel\Company\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyCollection;
    protected $sessionFactoryMock;
    protected $customerSessionMock;
    protected $customerMock;
    protected $commercialHelperMock;
    protected $selfRegHelperMock;
    protected $categoryColelctionFactoryMock;
    protected $productCollection;
    protected $categoryHelperMock;
    protected $productFactoryMock;
    protected $productMockForPodEditable;
    protected $productCollectionMock;
    protected $attributeSetCollectionFactory;
    protected $storeRepositoryInterface;
    protected $action;
    protected $scopeOverriddenValue;
    protected $companyRepository;
    protected $companyInterface;
    protected $productActivity;
    protected $layerMock;
    protected $catalogMvp;
    protected $delivaryHelperMock;

    protected $sdeHelperMock;

    /**
     * @var Registry
     */
    protected Registry $registryMock;

    /**
     * @var CategoryRepository
     */
    protected CategoryRepository $categoryRepositoryMock;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected $categoryLinkManagementInterfaceMock;

    protected $category;
    protected $customerGroupPermissionManagerMock;
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection', 'getTable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->nodeFactory = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $this->mysqlInterceptor = $this->getMockBuilder(Interceptor::class)
            ->setMethods(['insertArray', 'lastInsertId', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where','orWhere'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->delivaryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer', 'getAssignedCompany'])
            ->getMock();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getId', 'getChildrenData', 'getParentId','delete'])
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getById', 'getAttributeSetId'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getChildrenCategories', 'getChildrenData', 'getPath', 'delete', 'load', 'move', 'getChildrenCount', 'getName', 'getParentId', 'getCategories', 'getUrlKey', 'formatUrlKey'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getName', 'getCustomAttribute', 'getAttributeSetId','getIsPendingReview'])
            ->getMock();

        /* B-1573026 */
        $this->subcategoriesMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['addAttributeToSort', 'addAttributeToSelect', 'addFieldToFilter', 'addAttributeToFilter'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam','getPostValue'])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->storeMangerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStores'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getRootCategoryId'])
            ->getMockForAbstractClass();

        $this->categoryManagementInterfaceMock = $this->getMockBuilder(CategoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTree'])
            ->getMockForAbstractClass();

        $this->categoryTreeInterfaceMock = $this->getMockBuilder(CategoryTreeInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTree', 'getChildrenData'])
            ->getMockForAbstractClass();

        $this->noSuchEntityInterfaceMock = $this->getMockBuilder(NoSuchEntityException::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->attributeSetRepositoryMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->attributeSetInterfaceMock = $this->getMockBuilder(AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->onlyMethods(['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCollection', 'addFieldToFilter', 'getFirstItem', 'getIsCatalogMvpEnabled', 'getSharedCatalogId'])
            ->getMock();

        $this->companyCollection = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCollection', 'addFieldToFilter', 'getFirstItem', 'getIsCatalogMvpEnabled'])
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getCustomerCompany'])
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'getCustomerCompany',
                'getCustomer',
                'getGroupId',
                'isLoggedIn',
                'getId',
                'getName',
                'getUserPermissionData'
                ]
            )
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManageCatalogPermission'])
            ->getMock();

        $this->commercialHelperMock = $this->getMockBuilder(CommercialHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isRolePermissionToggleEnable'])
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer', 'isSelfRegCustomerAdmin'])
            ->getMock();

        $this->categoryColelctionFactoryMock = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->productCollection = $this->getMockBuilder(ProductCollection::class)
            ->onlyMethods(['addAttributeToFilter', 'getColumnValues', 'addAttributeToSelect'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryLinkManagementInterfaceMock = $this->getMockBuilder(CategoryLinkManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['assignProductToCategories'])
            ->getMockForAbstractClass();

        $this->categoryHelperMock = $this->getMockBuilder(CategoryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryUrl'])
            ->getMockForAbstractClass();

        $this->productFactoryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\ProductFactory::class)
            ->setMethods(['create', 'load', 'getMediaGalleryImages', 'getMediaGalleryEntries'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMockForPodEditable = $this
            ->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'getCollection',
                'addFieldToFilter',
                'getFirstItem',
                'getData'
                ]
            )->getMock();

        $this->productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem', 'getData'])
            ->getMock();

        $this->attributeSetCollectionFactory = $this
            ->getMockBuilder(AttributeSetCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'create',
                'addFieldToSelect',
                'addFieldToFilter',
                'getFirstItem',
                'getAttributeSetId'
                ]
            )->getMock();

        $this->storeRepositoryInterface = $this
            ->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'get',
                'getId',
                'getList'
                ]
            )->getMockForAbstractClass();

        $this->action = $this
            ->getMockBuilder(Action::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'updateAttributes'
                ]
            )->getMock();

        $this->scopeOverriddenValue = $this
            ->getMockBuilder(ScopeOverriddenValue::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'containsValue'
                ]
            )->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId','getStorefrontLoginMethodOption','getIsCatalogMvpEnabled'])
            ->getMockForAbstractClass();

        $this->productActivity = $this->getMockBuilder(ProductActivity::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','save'])
            ->getMock();

        $this->layerMock = $this->createMock(Layer::class);
        /**
 * @var MockObject|Resolver $layerResolver
*/
        $layerResolver = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'create', 'getCurrentCategory'])
            ->getMock();
        $layerResolver->expects($this->any())
            ->method($this->anything())
            ->willReturn($this->layerMock);

        $this->customerGroupPermissionManagerMock = $this->getMockBuilder(CustomerGroupPermissionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupsList', 'doesDenyAllPermissionExist', 'getAllowedGroups'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            CatalogMvp::class,
            [
                'context' => $this->contextMock,
                'toggleConfig' => $this->toggleConfigMock,
                'deliveryHelper' => $this->delivaryHelperMock,
                'SdeHelper' => $this->sdeHelperMock,
                'registry' => $this->registryMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'productRepository' => $this->productRepositoryMock,
                'request' => $this->requestMock,
                'scopeConfig' => $this->scopeConfigMock,
                'storeManger' => $this->storeMangerMock,
                'attributeSetRepository' => $this->attributeSetRepositoryMock,
                'categoryManagement' => $this->categoryManagementInterfaceMock,
                'customerSession' => $this->sessionFactoryMock,
                'companyFactory' => $this->companyFactoryMock,
                'selfRegHelper' => $this->selfRegHelperMock,
                'categoryColelctionFactory' => $this->categoryColelctionFactoryMock,
                'categoryLinkManagementInterface' => $this->categoryLinkManagementInterfaceMock,
                'category' => $this->categoryMock,
                'productFactory' => $this->productFactoryMock,
                'moduleDataSetup' => $this->moduleDataSetup,
                'resourceConnection' => $this->resourceConnectionMock,
                'attributeSetCollection' => $this->attributeSetCollectionFactory,
                'storeRepository' => $this->storeRepositoryInterface,
                'productAction' => $this->action,
                'scopeOverriddenValue' => $this->scopeOverriddenValue,
                'commercialHelper' => $this->commercialHelperMock,
                'companyRepository' => $this->companyRepository,
                'productActivity' => $this->productActivity,
                'layerResolver' => $layerResolver,
                'customerGroupPermissionManager' => $this->customerGroupPermissionManagerMock
            ]
        );
    }

    /**
     * @test testgetAttrSetIdByName
     */
    public function testGetAttrSetIdByName()
    {
        $attributeSetName = "PrintOnDemand";

        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('getFirstItem')
            ->willReturnSelf();
        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn('2');

        $this->assertEquals('2', $this->catalogMvp->getAttrSetIdByName($attributeSetName));
    }

    /**
     * @test testIsMvpCtcAdminEnable
     */
    public function testIsMvpCtcAdminEnable()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isMvpCtcAdminEnable());
    }

    /**
     * @test testIsProductAdminRefreshToggle
     */
    public function testIsProductAdminRefreshToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isProductAdminRefreshToggle());
    }


    /**
     * @test testisMvpSharedCatalogEnable
     */
    public function testisMvpSharedCatalogEnable()
    {

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->delivaryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->testisMvpCatalogEnabledForCompany();
        $this->assertNotNull($this->catalogMvp->isMvpSharedCatalogEnable());
    }

    /**
     * @test testisMvpSharedCatalogEnablefalse
     */
    public function testisMvpSharedCatalogEnablefalse()
    {


        $this->delivaryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(false);
        $this->assertEquals(false, $this->catalogMvp->isMvpSharedCatalogEnable());


    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     *
     * @test testGetSubCategories
     */
    public function testGetSubCategories()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->subcategoriesMock);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn('most_recent');
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSort')
            ->willReturnSelf();

        $this->catalogMvp->getSubCategories();
    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     */
    public function testGetSubCategorieswithnull()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->subcategoriesMock);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(null);
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSort')
            ->willReturnSelf();

        $this->catalogMvp->getSubCategories();
    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     */
    public function testGetSubCategorieswithasc()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->subcategoriesMock);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn('name_asc');
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSort')
            ->willReturnSelf();

        $this->catalogMvp->getSubCategories();
    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     */
    public function testGetSubCategorieswithdsc()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->subcategoriesMock);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn('name_desc');
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSort')
            ->willReturnSelf();

        $this->catalogMvp->getSubCategories();
    }

    public function testgetSubCategoryByParentID()
    {
        $this->categoryManagementInterfaceMock
            ->expects($this->any())
            ->method('getTree')
            ->willReturn($this->categoryTreeInterfaceMock);
        $this->categoryTreeInterfaceMock
            ->expects($this->any())
            ->method('getChildrenData')
            ->willReturn([$this->categoryMock]);

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $this->categoryMock->expects($this->any())
            ->method('getChildrenData')
            ->willReturn([$this->categoryMock]);

        $this->categoryTreeInterfaceMock->expects($this->any())
            ->method('getChildrenData')
            ->willReturn([$this->categoryMock]);

        $this->categoryMock->expects($this->any())
            ->method('getCategories')
            ->willReturn([$this->categoryMock]);

        $this->catalogMvp->getSubCategoryByParentID(123, 9);
    }

    public function testgetCategoryData()
    {
        $this->categoryManagementInterfaceMock
            ->expects($this->any())
            ->method('getTree')
            ->willReturn($this->categoryTreeInterfaceMock);

        $this->assertEquals($this->categoryTreeInterfaceMock, $this->catalogMvp->getCategoryData(9));

    }

    /**
     * Test Case for getCategoryData with Exception
     */
    public function testgetCategoryDataCatch()
    {
        $this->categoryManagementInterfaceMock
            ->expects($this->any())
            ->method('getTree')
            ->willThrowException(new NoSuchEntityException());
        $this->assertEquals(null, $this->catalogMvp->getCategoryData(9));

    }

    /**
     * Test Case for getScopeConfigValue
     */
    public function testgetScopeConfigValue()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('1');
        $this->assertNotNull($this->catalogMvp->getScopeConfigValue('ondemand_setting/category_setting/epro_print'));
    }
    /**
     * Test Case for getRootCategoryFromStore
     */
    public function testgetRootCategoryFromStore()
    {
        $this->storeMangerMock
            ->expects($this->any())
            ->method('getStores')
            ->willReturn([$this->storeInterfaceMock]);

        $this->storeInterfaceMock
            ->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');

        $this->storeInterfaceMock
            ->expects($this->any())
            ->method('getRootCategoryId')
            ->willReturn(487);

        $this->catalogMvp->getRootCategoryFromStore('ondemand');
    }

    /**
     * Test Case for setProductVisibilityValue
     */
    public function testSetProductVisibilityValue()
    {
        $attributeSet['attribute_set_name'] = "PrintOnDemand";
        $postvalues=['product'=>['visibility'=>4, 'current_store_id'=>8,'current_product_id'=>1]];
        $this->attributeSetRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($attributeSet);
        $this->testGetOndemandStoreId();
        $this->testGetAllStoreExceptOndemand();

        $this->scopeOverriddenValue
            ->expects($this->any())
            ->method('containsValue')
            ->willReturn(true);

        $this->action
            ->expects($this->any())
            ->method('updateAttributes')
            ->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($postvalues);
        $this->assertTrue($this->catalogMvp->setProductVisibilityValue($this->productMock, 8));
    }

    /**
     * Test Case for getAttributeSetName
     */
    public function testgetAttributeSetName()
    {
        $attributeSet['attribute_set_name'] = "Test";
        $this->attributeSetRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->willReturn($attributeSet);
        $this->catalogMvp->getAttributeSetName(1);
    }

    /**
     * Test Case for isAttributeSetPrintOnDemand
     */
    public function testIsAttributeSetPrintOnDemand()
    {
        $attributeSet['attribute_set_name'] = "PrintOnDemand";
        $this->attributeSetRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($attributeSet);
        $this->assertTrue($this->catalogMvp->isAttributeSetPrintOnDemand(1));
    }

    /**
     * Test Case for isAttributeSetPrintOnDemand
     */
    public function testIsAttributeSetPrintOnDemandWithoutPrintOnDemand()
    {
        $attributeSet['attribute_set_name'] = "Default";
        $this->attributeSetRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($attributeSet);
        $this->assertFalse($this->catalogMvp->isAttributeSetPrintOnDemand(1));
    }

    /**
     * Test Case for getOndemandStoreId
     */
    public function testGetOndemandStoreId()
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(8);
        $this->storeRepositoryInterface
            ->expects($this->any())
            ->method('get')
            ->willReturnSelf();
        $this->storeRepositoryInterface
            ->expects($this->any())
            ->method('getId')
            ->willReturn(8);

        $this->assertEquals(8, $this->catalogMvp->getOndemandStoreId());
    }

    /**
     * Test Case for getAllStoreExceptOndemand
     */
    public function testGetAllStoreExceptOndemand()
    {
        $varienObject = new \Magento\Framework\DataObject();
        $values = [
            'id' => 8
        ];
        $varienObject->setData($values);
        $this->storeRepositoryInterface
            ->expects($this->any())
            ->method('getList')
            ->willReturn([$varienObject]);

        $this->assertNotNull($this->catalogMvp->getAllStoreExceptOndemand(8));
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
        $this->catalogMvp->getAttributeSetName(1);
    }
    /**
     * Test Case for testgetIsLegacyItemBySku
     */
    public function testgetIsLegacyItemBySku()
    {

        $this->productRepositoryMock->expects($this->any())->method('get')->willReturn($this->productMock);
        $this->productRepositoryMock->expects($this->any())->method('getAttributeSetId')->willReturn(10);
        $this->attributeSetRepositoryMock->expects($this->any())->method('get')->willReturn($this->attributeSetInterfaceMock);
        $this->attributeSetInterfaceMock->expects($this->any())->method('getAttributeSetName')->willReturn('PrintOnDemand');
        $this->productMock->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeInterfaceMock);
        $this->attributeInterfaceMock->expects($this->any())->method('getValue')->willReturn(1);
        $this->assertNotNull($this->catalogMvp->getIsLegacyItemBySku('demo1'));
    }
    /**
     * Test Case for testgetIsLegacyItemBySkuwithexception Exception
     */
    public function testgetIsLegacyItemBySkuwithexception()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->productRepositoryMock->expects($this->any())->method('get')->willThrowException($exception);
        $this->productRepositoryMock->expects($this->any())->method('getAttributeSetId')->willReturn(10);
        $this->productRepositoryMock->expects($this->any())->method('get')->willReturn($this->attributeSetInterfaceMock);

        $this->attributeSetInterfaceMock->expects($this->any())->method('getAttributeSetName')->willReturn('PrintOnDemand');
        $this->productMock->expects($this->any())->method('getCustomAttribute')->willReturnSelf();
        $this->productMock->expects($this->any())->method('getValue')->willThrowException($exception);
        $this->assertEquals(true, $this->catalogMvp->getIsLegacyItemBySku('demo1'));
    }

    /**
     * Test Case for testisMvpCatalogEnabledForCompany
     */
    public function testisMvpCatalogEnabledForCompany()
    {
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->customerSessionMock->expects($this->any())->method('getId')->willReturn(2);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->companyInterface->expects($this->any())->method('getIsCatalogMvpEnabled')->willReturn(true);

        $this->selfRegHelperMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);

        $this->assertEquals(true, $this->catalogMvp->isMvpCatalogEnabledForCompany());
    }

    /**
     * Test Case for testisMvpCatalogEnabledForCompanyfalse
     */
    public function testisMvpCatalogEnabledForCompanyfalse()
    {
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->customerSessionMock->expects($this->any())->method('getId')->willReturn(2);

        $this->companyRepository->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');

        $this->companyInterface->expects($this->any())->method('getIsCatalogMvpEnabled')->willReturn(true);

        $this->selfRegHelperMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(false);

        $this->assertNotNull($this->catalogMvp->isMvpCatalogEnabledForCompany());
    }

    /**
     * Test Case to check current user is self reg customer admin
     */
    public function testIsSelfRegCustomerAdmin()
    {
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isSelfRegCustomerAdmin());
    }

    /**
     * Test Case to check current user has catalog permission or not
     */
    public function testIsSharedCatalogPermissionEnabled()
    {
        $this->selfRegHelperMock->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerSessionMock->expects($this->any())->method('getUserPermissionData')->willReturn(['Manage::manage_catalog'=>'2']);
        $this->commercialHelperMock->expects($this->any())->method('isRolePermissionToggleEnable')->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isSharedCatalogPermissionEnabled());
    }

    /**
     * Test Case to check current user has catalog permission or not
     */
    public function testIsSharedCatalogPermissionEnabledToggleOff()
    {
        $this->selfRegHelperMock->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isSharedCatalogPermissionEnabled());
    }

    /**
     * Test Case to check category is print product or not if case
     */
    public function testCheckPrintCategoryIfCaseOne()
    {
        $this->scopeConfigMock->expects($this->any())->method('getValue')->willReturn('1');
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getId')->willReturn(1);
        $this->assertEquals(true, $this->catalogMvp->checkPrintCategory());
    }

    /**
     * Test Case to check category is print product or not else case
     */
    public function testCheckPrintCategoryIfCaseTwo()
    {
        $this->scopeConfigMock->expects($this->any())->method('getValue')->willReturn('1');
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getId')->willReturn(0);
        $this->categoryMock->expects($this->any())->method('getPath')->willReturn('1/1/2');
        $this->assertEquals(true, $this->catalogMvp->checkPrintCategory());
    }

    /**
     * Test Case for sub category to selfreg customer admin
     */
    public function testGetSubCategoriesForCustomerAdmin()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryColelctionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->subcategoriesMock);
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->subcategoriesMock);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn('most_recent');
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSort')
            ->willReturnSelf();
        $this->assertEquals($this->subcategoriesMock, $this->catalogMvp->getSubCategories());
    }

    /**
     * Test case for getFilteredCategoryItem
     */
    public function testGetFilteredCategoryItem()
    {
        $this->delivaryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->testisMvpCatalogEnabledForCompany();
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(false);
        $this->productCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('getColumnValues')->willReturn(['2', '34']);
        $this->assertEquals(['2', '34'], $this->catalogMvp->getFilteredCategoryItem($this->productCollection));
    }
    /**
     * Test case for getFilteredCategoryItem With Customer Admin User
     */
    public function testGetFilteredCategoryItemWithAdmin()
    {
        $this->delivaryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->testisMvpCatalogEnabledForCompany();
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(true);
        $this->assertEquals([], $this->catalogMvp->getFilteredCategoryItem($this->productCollection));
    }

    /**
     * Test case for assignProductToCategory
     */
    public function testAssignProductToCategory()
    {
        $this->categoryLinkManagementInterfaceMock->expects($this->any())->method('assignProductToCategories')
            ->willReturn(true);

        $this->assertTrue($this->catalogMvp->assignProductToCategory(12, 'test'));
    }

    /**
     * Test case for assignProductToCategory
     */
    public function testAssignProductToCategoryWithCatch()
    {
        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);
        $this->categoryLinkManagementInterfaceMock->expects($this->any())->method('assignProductToCategories')
            ->willThrowException($e);

        $this->assertFalse($this->catalogMvp->assignProductToCategory(12, 'test'));
    }

    /**
     * Test case for assignCategoryToCategoryException
     */
    public function testAssignCategoryToCategoryException()
    {
        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);

        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willThrowException($e);

        $this->assertEquals(
            false,
            $this->catalogMvp->assignCategoryToCategory(12, 13)
        );
    }

    /**
     * Test case for assignCategoryToCategory
     */
    public function testAssignCategoryToCategory()
    {
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('move')
            ->with(12, 13)
            ->willReturn($this->categoryMock);

        $this->assertNull(
            $this->catalogMvp->assignCategoryToCategory(12, 13)
        );
    }

    /**
     * Test case to delete category try
     */
    public function testDeleteCategory()
    {
        $this->categoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->categoryMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->assertEquals(1, $this->catalogMvp->deleteCategory(123));
    }

    /**
     * Test case to delete category try
     */
    public function testDeleteCategoryCatch()
    {

        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);

        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willThrowException($e);
        $this->catalogMvp->deleteCategory(12);
        $this->assertEquals(0, $this->catalogMvp->deleteCategory(123));
    }

    /**
     * Test case to GetB2BcategoryCollection
     */
    public function testGetB2BcategoryCollection()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getPath')->willReturn('1/1/2');
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn($this->subcategoriesMock);
        $this->catalogMvp->getB2BcategoryCollection();
    }

    /**
     * Test case to GetB2BcategoryCollection
     */
    public function testGetCategoryRepository()
    {
        $this->categoryRepositoryMock;
        $this->catalogMvp->getCategoryRepository();
    }

    public function testGetCategoryUrl()
    {
        $this->categoryHelperMock->expects($this->any())->method('getCategoryUrl')->willReturn('asdasdasd');
        $this->assertEquals(false, $this->catalogMvp->getCategoryUrl($this->categoryMock));
    }

    /**
     * Test case for convertTimeIntoPST
     */
    public function testConvertTimeIntoPST()
    {
        $date = "2023-08-08 12:34:30";
        $this->assertEquals('2023-08-08 12:34:30', $this->catalogMvp->convertTimeIntoPST($date));
    }

    /**
     * Test case for getCurrentPSTDateAndTime
     */
    public function testgetCurrentPSTDateAndTime()
    {
        $this->assertNotNull('2023-08-10 12:34:30', $this->catalogMvp->getCurrentPSTDateAndTime());
    }

    /**
     * Test case for getconvertTimeIntoPSTWithCustomerTimezone
     */
    public function testconvertTimeIntoPSTWithCustomerTimezone()
    {
        $date = "2023-08-08 12:34:30";
        $timezone = "Asia/Kolkata";
        $this->assertNotNull('2023-08-10 12:34:30', $this->catalogMvp->convertTimeIntoPSTWithCustomerTimezone($date, $timezone));
    }

    /**
     * Test case for getCurrentPSTDate
     */
    public function testgetCurrentPSTDate()
    {
        $this->assertNotNull('2023-08-08', $this->catalogMvp->getCurrentPSTDate());
    }

    /**
     * Test case for GetCurrentCategoryId
     */
    public function testGetCurrentCategoryId()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getPath')->willReturn('1/1/2');
        $this->assertEquals('2', $this->catalogMvp->getCurrentCategoryId());
    }

    /**
     * Test case for GetCurrentCategoryId
     */
    public function testGetChildCategoryCount()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCount')->willReturn('5');
        $this->assertEquals('5', $this->catalogMvp->getChildCategoryCount());
    }

    /**
     * Test case for GetCurrentSubCategoryId
     */
    public function testGetCurrentSubCategoryId()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getId')->willReturn('12');
        $this->assertEquals('12', $this->catalogMvp->getCurrentSubCategoryId());
    }

    /**
     * Test case for GetCurrentSubCategoryIdforChild
     */
    public function testGetCurrentSubCategoryIdforChild()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getId')->willReturn('2');
        $this->assertEquals('2', $this->catalogMvp->getCurrentSubCategoryId());
    }

    /**
     * Test case for GetCurrentSubCategoryName
     */
    public function testGetCurrentSubCategoryName()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->testGetCurrentCategoryId();
        $this->testGetCurrentSubCategoryIdforChild();
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('Shared Catalog');
        $this->assertEquals('Shared Catalog', $this->catalogMvp->getCurrentSubCategoryName());
    }

    /**
     * Test case for GetCurrentSubCategoryNameForChild
     */
    public function testGetCurrentSubCategoryNameForChild()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->testGetCurrentCategoryId();
        $this->testGetCurrentSubCategoryId();
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('test');
        $this->assertEquals('test', $this->catalogMvp->getCurrentSubCategoryName());
    }

    /**
     * Test case for GetCurrentCategoryPath
     */
    public function testGetCurrentCategoryPath()
    {
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getPath')->willReturn('1/1/2');
        $this->assertEquals(['1', '1', '2'], $this->catalogMvp->getCurrentCategoryPath());
    }

    /**
     * Test case for testisProductPodEditAbleById
     */
    public function testisProductPodEditAbleById()
    {

        $this->productFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productMockForPodEditable);
        $this->productMockForPodEditable->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productCollectionMock);
        $this->productCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->productCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf($this->productMockForPodEditable);
        $this->productCollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn(['pod2_0_editable' => 1]);
        $this->catalogMvp->isProductPodEditAbleById(10);
    }
    /**
     * Test case for testisProductPodEditAbleByIdWithException
     */
    public function testisProductPodEditAbleByIdWithException()
    {
        $this->productFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productMockForPodEditable);
        $this->productMockForPodEditable->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->productCollectionMock);
        $this->productCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->productCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willThrowException(new NoSuchEntityException());
        $this->catalogMvp->isProductPodEditAbleById(10);
    }

    /**
     * @test testgetIdFromNode
     */
    public function testGetIdFromNode()
    {
        $this->nodeFactory->expects($this->any())->method('getId')->willReturn('catagory-node-2324');
        $this->assertEquals('catagory-node-2324', $this->catalogMvp->getIdFromNode($this->nodeFactory));
    }
    /**
     * Test updateFxoMenuId method
     *
     * @return void
     */
    public function testUpdateFxoMenuId()
    {
        $productId = '456';
        $fxoMenuId = '1582146604697-4';
        $this->moduleDataSetup->expects($this->any())->method('getConnection')->willReturn($this->mysqlInterceptor);
        $this->moduleDataSetup->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->mysqlInterceptor->expects($this->any())->method('update')->willReturn(3);
        $this->moduleDataSetup->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertEquals(null, $this->catalogMvp->updateFxoMenuId($productId, $fxoMenuId));
    }
    /**
     * Test updateFxoMenuId method
     *
     * @return void
     */
    public function testUpdateFxoMenuIdWithException()
    {
        $productId = '456';
        $fxoMenuId = '1582146604697-4';
        $this->moduleDataSetup->expects($this->any())->method('getConnection')->willThrowException(new \Exception());
        $this->assertEquals(null, $this->catalogMvp->updateFxoMenuId($productId, $fxoMenuId));
    }

    /**
     * Test getFxoMenuId
     *
     * @return void
     */
    public function testGetFxoMenuId()
    {
        $productId = '456';
        $fxoMenuId = '1582146604697-4';
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('catalog_product_entity');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['fxo_menu_id' => $fxoMenuId]]);
        $this->assertEquals($fxoMenuId, $this->catalogMvp->getFxoMenuId($productId));
    }

    /**
     * Test getCatalogPendingReviewStatus
     *
     * @return void
     */
    public function testGetCatalogPendingReviewStatus()
    {
        $productId = '456';
        $pendingReviewStatus = 2;
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getIsPendingReview')->willReturn($pendingReviewStatus);
        $this->assertEquals($pendingReviewStatus, $this->catalogMvp->getCatalogPendingReviewStatus($productId));
    }
    /**
     * @test testcustomDocumentToggle
     */
    public function testIscustomDocumentToggle()
    {

        $this->assertEquals(true, $this->catalogMvp->customDocumentToggle());
    }


    /**
     * testGetCompanySharedCatName
     */
    public function testGetCompanySharedCatName()
    {
        $this->delivaryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSharedCatalogId')
            ->willReturn(9);
        $this->categoryMock->expects($this->any())->method('load')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getName')
            ->willReturn('Eprosite');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->assertEquals('Eprosite', $this->catalogMvp->getCompanySharedCatName());
    }

    /**
     * testGetCompanySharedCatNameWithoutId
     */
    public function testGetCompanySharedCatNameWithoutId()
    {
        $this->delivaryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSharedCatalogId')
            ->willReturn(0);
        $this->assertEquals('', $this->catalogMvp->getCompanySharedCatName());
    }

    /**
     * testGetCompanySharedCatNameWithoutIdToogleDisabled
     */
    public function testGetCompanySharedCatNameWithoutIdToogleDisabled()
    {
        $this->assertEquals('', $this->catalogMvp->getCompanySharedCatName());
    }

    /**
     * testGetCompanySharedCatId
     */
    public function testGetCompanySharedCatId()
    {
        $this->delivaryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSharedCatalogId')
            ->willReturn(9);
        $this->assertEquals(9, $this->catalogMvp->getCompanySharedCatId());
    }

    /**
     * testGetCompanySharedCatIdWithoutId
     */
    public function testGetCompanySharedCatIdWithoutId()
    {
        $this->delivaryHelperMock->expects($this->any())->method('getAssignedCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSharedCatalogId')
            ->willReturn(0);
        $this->assertEquals(0, $this->catalogMvp->getCompanySharedCatId());
    }

    /**
     * testGetCompanySharedCatIdWithoutIdToogleDisabled
     */
    public function testGetCompanySharedCatIdWithoutIdToogleDisabled()
    {
        $this->assertEquals(0, $this->catalogMvp->getCompanySharedCatId());
    }


    /**
     * @test testGetParentGroupId
     */
    public function testGetParentGroupId()
    {
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('parent_customer_group');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn(1);
        $this->assertEquals(1, $this->catalogMvp->getParentGroupId(1));
    }
    /**
     * @test testGetParentGroupIdwithException
     */
    public function testGetParentGroupIdwithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('parent_customer_group');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willThrowException($exception);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn(1);
        $this->assertEquals(0, $this->catalogMvp->getParentGroupId(1));
    }
    /**
     * @test testGetChildGroupIds
     */
    public function testGetChildGroupIds()
    {
        $fetchData = [['customer_group_id'=>1]];
        $data = [1];
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('parent_customer_group');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);
        $this->assertEquals($data, $this->catalogMvp->getChildGroupIds(1));
    }
    /**
     * @test testGetChildGroupIds
     */
    public function testGetChildGroupIdswithException()
    {
        $fetchData = [['customer_group_id'=>1]];
        $data = [];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('parent_customer_group');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willThrowException($exception);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn($fetchData);
        $this->assertEquals($data, $this->catalogMvp->getChildGroupIds(1));
    }
    /**
     * @test testIsFolderPermissionAllowed
     */
    public function testIsFolderPermissionAllowed()
    {
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturn(90);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('magento_catalogpermissions');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['customer_group_id' => 90, 'grant_catalog_category_view' => '-1']]);
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
            ->willReturn(123);
        $this->assertEquals(true, $this->catalogMvp->isFolderPermissionAllowed(123));
    }

    /**
     * @test TestGetCurrentCategory
     */
    public function testGetCurrentCategory()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $currentCategory->expects($this->any())
            ->method('getId')
            ->willReturn('1');
        $this->layerMock->expects($this->any())
            ->method('getCurrentCategory')
            ->willReturn($currentCategory);

        $this->assertEquals($currentCategory, $this->catalogMvp->getCurrentCategory());
    }

    /**
     * @test testIsFolderPermissionAllowedwithException
     */
    public function testIsFolderPermissionAllowedwithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturn(90);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('magento_catalogpermissions');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willThrowException($exception);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['customer_group_id' => 90, 'grant_catalog_category_view' => '-1']]);
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
            ->willReturn(123);
        $this->assertEquals(false, $this->catalogMvp->isFolderPermissionAllowed(123));
    }
    /**
     * @test testIsFolderPermissionAllowedwithElse
     */
    public function testIsFolderPermissionAllowedwithElse()
    {
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())->method('getGroupId')->willReturn(90);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('magento_catalogpermissions');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([]);
        $this->categoryRepositoryMock->expects($this->any())->method('get')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getParentId')
            ->willReturn(123);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('magento_catalogpermissions');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['customer_group_id' => 90, 'grant_catalog_category_view' => '-1']]);
        $this->assertEquals(false, $this->catalogMvp->isFolderPermissionAllowed(123));
    }

    /**
     * @test testGetSharedCatalogId
     */
    public function testGetSharedCatalogIdByPath()
    {
        $result = $this->invokeMethod($this->catalogMvp, 'getSharedCatalogIdByPath', ['category/123/456']);
        $this->assertEquals('456', $result);
        $result = $this->invokeMethod($this->catalogMvp, 'getSharedCatalogIdByPath', ['category/123']);
        $this->assertNull($result);
        $result = $this->invokeMethod($this->catalogMvp, 'getSharedCatalogIdByPath', ['category']);
        $this->assertNull($result);
        $result = $this->invokeMethod($this->catalogMvp, 'getSharedCatalogIdByPath', ['']);
        $this->assertNull($result);
    }

    /**
     * Helper method to invoke private or protected method.
     *
     * @param object &$object    Instance of the class containing the method.
     * @param string $methodName Name of the private or protected method to invoke.
     * @param array  $parameters Parameters to pass to the method.
     *
     * @return mixed Method return value.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }


    /**
     * @test testIsNonStandaredCatalogToggleEnable
     */
    public function testIsNonStandaredCatalogToggleEnable()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isNonStandaredCatalogToggleEnable());
    }

    /**
     * Test case for insertProductActivity
     */
    public function testInsertProductActivity()
    {
        $this->testIsNonStandaredCatalogToggleEnable();
        $this->sessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getName')
            ->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->any())
            ->method('getName')
            ->willReturn("Test");
        $this->productActivity->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->productActivity->expects($this->any())
            ->method('save')
            ->willReturn($this->productMock);

        $this->assertNull($this->catalogMvp->insertProductActivity(8));
    }

    /**
     * Test case for insertProductActivity with exception
     */
    public function testInsertProductActivityWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->testIsNonStandaredCatalogToggleEnable();
        $this->sessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getName')
            ->willThrowException($exception);
        $this->loggerInterfaceMock->expects($this->any())
            ->method('error')
            ->willReturnSelf();
        $this->assertNull($this->catalogMvp->insertProductActivity(8));
    }

    /**
     * @test test sortingToggle for D-174502
     */
    public function testsortingToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->sortingToggle());
    }

    public function testGetDenyCategoryIds()
    {
        $implodedIds = "2,45";
        $groupId = 6;

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('magento_catalogpermissions');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['category_id' => 23]]);

        $this->assertIsArray($this->catalogMvp->getDenyCategoryIds($implodedIds, $groupId));
    }
    public function testGetDenyCategoryIdsWithORM()
    {
        $implodedIds = "2,45";
        $groupId = 6;
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('sgc_d_212530')
            ->willReturn(false);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('magento_catalogpermissions');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('orWhere')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['category_id' => 23]]);

        $this->assertIsArray($this->catalogMvp->getDenyCategoryIds($implodedIds, $groupId));
    }

    public function testGenerateCategoryNmae()
    {
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getChildrenCategories')
            ->willReturn([$this->categoryMock]);
        $this->categoryMock->expects($this->any())->method('getName')
            ->willReturn("new folder");
        $this->categoryMock->expects($this->any())->method('getUrlKey')
            ->willReturn("new_folder");
        $this->assertEquals("new folder(1)", $this->catalogMvp->generateCategoryName("new folder", 0));
    }

    /**
     * testgetEproMigratedCustomDocToggle
     * @return void
     */
    public function testGetEproMigratedCustomDocToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->getEproMigratedCustomDocToggle());
    }

    /**
     * Test Case for testSetProductVisibilityInNewProduct
     */
    public function testSetProductVisibilityInNewProduct()
    {
        $postvalues=['product'=>['visibility'=>4, 'current_store_id'=>8]];
        $attributeSet['attribute_set_name'] = "PrintOnDemand";
        $this->attributeSetRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($attributeSet);
        $this->testGetOndemandStoreId();
        $this->testGetAllStoreExceptOndemand();
        $this->scopeOverriddenValue
            ->expects($this->any())
            ->method('containsValue')
            ->willReturn(true);
        $this->action
            ->expects($this->any())
            ->method('updateAttributes')
            ->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($postvalues);
        $this->assertTrue($this->catalogMvp->setProductVisibilityValue($this->productMock, 8));
    }

    /**
     * Test for Toggle for B-2193925 Product updated at toggle
     * @return void
     */
    public function testGetToggleStatusForNewProductUpdatedAtToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->getToggleStatusForNewProductUpdatedAtToggle());
    }

    /**
     * testGetMergedSharedCatalogFilesToggle
     * @return void
     */
    public function testGetMergedSharedCatalogFilesToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->getMergedSharedCatalogFilesToggle());
    }

    /**
     * testisEnableStopRedirectMvpAddToCart
     * @return void
     */
    public function testIsEnableStopRedirectMvpAddToCart()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isEnableStopRedirectMvpAddToCart());
    }

    /**
     * testgetCatalogBreakpointToggle
     * @return void
     */
    public function testGetCatalogBreakpointToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->getCatalogBreakpointToggle());
    }

    /**
     * testGetUserGroupsforCompany
     *
     * @return void
     */
    public function testGetUserGroupsforCompany()
    {
        $this->customerGroupPermissionManagerMock->expects($this->any())
            ->method('getCustomerGroupsList')
            ->willReturn(['1', '2']);
        $this->assertNotNull($this->catalogMvp->getUserGroupsforCompany());
    }

    /**
     * testGetCategoryPermission
     *
     * @return void
     */
    public function testGetCategoryPermission()
    {
        $this->customerGroupPermissionManagerMock->expects($this->any())
            ->method('doesDenyAllPermissionExist')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->getCategoryPermission('1', ['2']));
    }

    /**
     * testIsFolderRestrictedToUser
     *
     * @return void
     */
    public function testIsFolderRestrictedToUser()
    {
        $this->sessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getGroupId')
            ->willReturn(1);
        $this->customerGroupPermissionManagerMock->expects($this->any())
            ->method('getAllowedGroups')
            ->willReturn(['1', '2', '3']);
        $this->assertEquals(false, $this->catalogMvp->isFolderRestrictedToUser('123', ['2', '4']));
    }

    public function testGetDenyCategoryIdsFromListLogic()
    {
        $sharedCatalogCategoryId = 1;
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->categoryColelctionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->subcategoriesMock);
        $this->subcategoriesMock->expects($this->any())->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->subcategoriesMock->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();

        $result = $this->catalogMvp->getDenyCategoryIdsFromListLogic($sharedCatalogCategoryId);

        $this->assertIsArray($result);
    }

    public function testisToggleD213910Enabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_d213910')
            ->willReturn(true);
        $this->assertEquals(true, $this->catalogMvp->isToggleD213910Enabled());
    }

    public function testisToggleD213910EnabledFalse()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_d213910')
            ->willReturn(false);
        $this->assertEquals(false, $this->catalogMvp->isToggleD213910Enabled());
    }
}
