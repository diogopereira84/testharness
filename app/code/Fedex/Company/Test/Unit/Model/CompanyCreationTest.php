<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Company\Test\Unit\Model;

use Fedex\Catalog\Api\AttributeHandlerInterface;
use Fedex\CatalogMvp\Helper\SharedCatalogProduct;
use Fedex\Company\Api\CreateCompanyEntitiesMessageInterface;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\CompanyCreation;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use Fedex\SaaSCommon\Api\ConfigInterface as FedexSaaSCommonConfig;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;
use Magento\CatalogPermissions\Model\PermissionFactory;
use Magento\Company\Model\Company;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Store\Api\Data\GroupInterface as StoreGroupInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\Repository;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory as StoreGroupFactory;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\Data\TaxClassSearchResultsInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\User\Model\User;
use Fedex\Company\Api\Data\ConfigInterface as CompanyConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CompanyCreationTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $companyConfigMock;
    protected $taxClassResultMock;
    /**
     * @var (\Fedex\Catalog\Api\AttributeHandlerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeHandlerInterfaceMock;
    protected $messageMock;
    protected $publisherMock;
    protected $serializerMock;
    /**
     * @var SharedCatalogFactory|MockObject
     */
    private MockObject|SharedCatalogFactory $sharedCatalogFactoryMock;

    /**
     * @var AdminSession|MockObject
     */
    private MockObject|AdminSession $adminSessionMock;

    /**
     * @var User|MockObject
     */
    private MockObject|User $adminUserMock;

    /**
     * @var SharedCatalog|MockObject
     */
    private MockObject|SharedCatalog $sharedCatalogMock;

    /**
     * @var Repository|MockObject
     */
    private MockObject|Repository $sharedCatalogRepositoryMock;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private MockObject|ProductManagementInterface $productManagementMock;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    private MockObject|CategoryManagementInterface $categoryManagementMock;

    /**
     * @var GroupInterfaceFactory|MockObject
     */
    private MockObject|GroupInterfaceFactory $groupFactoryMock;

    /**
     * @var GroupInterface|MockObject
     */
    private MockObject|GroupInterface $customerGroupMock;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private GroupRepositoryInterface|MockObject $groupRepositoryMock;

    /**
     * @var TaxClassRepositoryInterface|MockObject
     */
    private MockObject|TaxClassRepositoryInterface $taxClassRepositoryMock;

    /**
     * @var TaxClassInterface|MockObject
     */
    private MockObject|TaxClassInterface $taxClassMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private MockObject|SearchCriteriaBuilder $searchCriteriaBuilderMock;

    /**
     * @var SearchCriteria|MockObject
     */
    private MockObject|SearchCriteria $searchCriteriaMock;

    /**
     * @var CategoryRepository|MockObject
     */
    private MockObject|CategoryRepository $categoryRepositoryMock;

    /**
     * @var Collection|MockObject
     */
    private MockObject|Collection $collectionMock;

    /**
     * @var CategoryCollectionFactory|MockObject
     */
    private MockObject|CategoryCollectionFactory $categoryCollectionFactoryMock;

    /**
     * @var PermissionFactory|MockObject
     */
    private MockObject|PermissionFactory $permissionFactoryMock;

    /**
     * @var CategoryCollection|MockObject
     */
    private MockObject|CategoryCollection $categoryCollectionMock;

    /**
     * @var PermissionCollectionFactory|MockObject
     */
    private MockObject|PermissionCollectionFactory $permissionCollectionFactoryMock;

    /**
     * @var CategoryInterfaceFactory|MockObject
     */
    private CategoryInterfaceFactory|MockObject $categoryInterfaceFactoryMock;

    /**
     * @var CategoryInterface|MockObject
     */
    private CategoryInterface|MockObject $categoryInterfaceMock;

    /**
     * @var StoreGroupFactory|MockObject
     */
    private MockObject|StoreGroupFactory $storeGroupFactoryMock;

    /**
     * @var Group|MockObject
     */
    private MockObject|Group $storeGroupMock;

    /**
     * @var ConfigInterface|MockObject
     */
    private MockObject|ConfigInterface $ondemandConfigMock;

    /**
     * @var SharedCatalogProduct
     */
    private SharedCatalogProduct $sharedCatalogProductHelperMock;

    /**
     * @var CompanyConfigInterface|MockObject
     */
    private FedexSaaSCommonConfig $fedexSaaSCommonConfigMock;

    /**
     * @var CustomerGroupAttributeHandlerInterface|MockObject
     */
    private CustomerGroupAttributeHandlerInterface $customerGroupAttributeHandlerMock;

    /**
     * @var CompanyCreation
     */
    protected $companyCreationMock;

    /**
     * @var Registry|MockObject
     */
    private registry $registryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerInterfaceMock;


    /**
     * {@inheritdoc}
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->adminSessionMock = $this->getMockBuilder(AdminSession::class)
            ->setMethods(['getUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminUserMock = $this->getMockBuilder(User::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogFactoryMock = $this->getMockBuilder(SharedCatalogFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogMock = $this->getMockBuilder(SharedCatalog::class)
            ->setMethods(
                ['setName','setDescription','setCreatedBy','setType','setCustomerGroupId','setTaxClassId','getId']
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogRepositoryMock = $this->getMockBuilder(Repository::class)
            ->setMethods(['save','delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productManagementMock = $this->getMockBuilder(ProductManagementInterface::class)
            ->setMethods(['reassignProducts','getProducts'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyConfigMock = $this->getMockBuilder(CompanyConfigInterface::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->categoryManagementMock = $this->getMockBuilder(CategoryManagementInterface::class)
            ->setMethods(['getCategories','assignCategories'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->groupFactoryMock = $this->getMockBuilder(GroupInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupMock = $this->getMockBuilder(GroupInterface::class)
            ->setMethods(['setCode','setTaxClassId','getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->groupRepositoryMock = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->setMethods(['save','delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->taxClassRepositoryMock = $this->getMockBuilder(TaxClassRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->taxClassResultMock = $this->getMockBuilder(TaxClassSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->taxClassMock = $this->getMockBuilder(TaxClassInterface::class)
            ->setMethods(['getClassId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter','create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->setMethods(['get','save','delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryCollectionFactoryMock = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryCollectionMock = $this->getMockBuilder(CategoryCollection::class)
            ->setMethods(['addFieldToFilter','getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['addFieldToFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->permissionCollectionFactoryMock = $this->getMockBuilder(PermissionCollectionFactory::class)
            ->setMethods(['create', 'addFieldToFilter'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionFactoryMock = $this->getMockBuilder(PermissionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryInterfaceFactoryMock = $this->getMockBuilder(CategoryInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryInterfaceMock = $this->getMockBuilder(CategoryInterface::class)
            ->setMethods(['setName','setIsActive', 'setCustomAttributes', 'setIncludeInMenu','setData','setParentId','setStoreId','getId', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeGroupFactoryMock = $this->getMockBuilder(StoreGroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeGroupMock = $this->getMockBuilder(Group::class)
            ->setMethods(['load','getRootCategoryId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ondemandConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods(['getDefaultSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogProductHelperMock = $this->getMockBuilder(SharedCatalogProduct::class)
            ->setMethods(['applyAssignedLogic', 'applyUnassignedLogic'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeHandlerInterfaceMock = $this->getMockBuilder(AttributeHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageMock = $this->getMockBuilder(CreateCompanyEntitiesMessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fedexSaaSCommonConfigMock = $this->getMockBuilder(FedexSaaSCommonConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupAttributeHandlerMock = $this->getMockBuilder(CustomerGroupAttributeHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyCreationMock = $this->objectManager->getObject(
            CompanyCreation::class,
            [
                'adminSession' => $this->adminSessionMock,
                'sharedCatalogFactory' => $this->sharedCatalogFactoryMock,
                'sharedCatalogRepository' => $this->sharedCatalogRepositoryMock,
                'productManagement' => $this->productManagementMock,
                'categoryManagement' => $this->categoryManagementMock,
                'groupFactory' => $this->groupFactoryMock,
                'groupRepository' => $this->groupRepositoryMock,
                'taxClassRepository' => $this->taxClassRepositoryMock,
                'collection' => $this->collectionMock,
                'permissionFactory' => $this->permissionFactoryMock,
                'permissionCollectionFactory' => $this->permissionCollectionFactoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'categoryCollectionFactory' => $this->categoryCollectionFactoryMock,
                'categoryInterfaceFactory' => $this->categoryInterfaceFactoryMock,
                'storeGroupFactory' => $this->storeGroupFactoryMock,
                'ondemandConfig' => $this->ondemandConfigMock,
                'sharedCatalogProductHelper' => $this->sharedCatalogProductHelperMock,
                'companyConfigInterface' => $this->companyConfigMock,
                'attributeHandlerInterface' => $this->attributeHandlerInterfaceMock,
                'message' => $this->messageMock,
                'publisher' => $this->publisherMock,
                'serializer' => $this->serializerMock,
                'logger' => $this->loggerInterfaceMock,
                'registry' => $this->registryMock,
                'fedexSaaSCommonConfig' => $this->fedexSaaSCommonConfigMock,
                'customerGroupAttributeHandler' => $this->customerGroupAttributeHandlerMock,
            ]
        );
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testInitializeCompanyExtraEntitiesCreation()
    {
        $taxClassId = 3;
        $urlExtensionName = 'company-test';

        $this->getRetailCustomerTaxClassId($taxClassId);
        $this->createCustomerGroup($urlExtensionName, $taxClassId);

        $permissionCollectionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->setMethods(['addFieldToFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionCollectionMock->method('addFieldToFilter')->willReturn([]);
        $this->permissionCollectionFactoryMock->method('create')->willReturn($permissionCollectionMock);
        
        $this->createRootCategory($urlExtensionName);

        $permissionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\Permission::class)
            ->setMethods(['setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionMock->method('setData')->willReturnSelf();
        $permissionMock->method('save')->willReturnSelf();
        $this->permissionFactoryMock->method('create')->willReturn($permissionMock);
        
        $this->createSharedCatalog($urlExtensionName, $taxClassId);

        $this->assertInstanceOf(
            CompanyCreation::class,
            $this->companyCreationMock->initializeCompanyExtraEntitiesCreation($urlExtensionName)
        );
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testInitializeCompanyExtraEntitiesCreationExceptionRootCategoryCouldNotSaveException()
    {
        $taxClassId = 3;
        $urlExtensionName = 'company-test';

        $this->getRetailCustomerTaxClassId($taxClassId);
        $this->createCustomerGroup($urlExtensionName, $taxClassId);

        $this->createRootCategoryWithCouldNotSaveException($urlExtensionName);

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save new Root Category.');
        $this->companyCreationMock->initializeCompanyExtraEntitiesCreation($urlExtensionName);
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testInitializeCompanyExtraEntitiesCreationExceptionRootCategoryNoSuchEntityException()
    {
        $taxClassId = 3;
        $urlExtensionName = 'company-test';

        $this->getRetailCustomerTaxClassId($taxClassId);
        $this->createCustomerGroup($urlExtensionName, $taxClassId);

        $this->createRootCategoryWithNoSuchEntityException();

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Could not find store Root Category.');
        $this->companyCreationMock->initializeCompanyExtraEntitiesCreation($urlExtensionName);
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testInitializeCompanyExtraEntitiesCreationExceptionSharedCatalog()
    {
        $taxClassId = 3;
        $urlExtensionName = 'company-test';

        $this->getRetailCustomerTaxClassId($taxClassId);
        $this->createCustomerGroup($urlExtensionName, $taxClassId);

        $permissionCollectionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class)
            ->setMethods(['addFieldToFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionCollectionMock->method('addFieldToFilter')->willReturn([]);
        $this->permissionCollectionFactoryMock->method('create')->willReturn($permissionCollectionMock);
        
        $this->createRootCategory($urlExtensionName);

        $this->createSharedCatalogWithException($urlExtensionName, $taxClassId);

        $permissionMock = $this->getMockBuilder(\Magento\CatalogPermissions\Model\Permission::class)
            ->setMethods(['setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $permissionMock->method('setData')->willReturnSelf();
        $permissionMock->method('save')->willReturnSelf();
        $this->permissionFactoryMock->method('create')->willReturn($permissionMock);
        
        $this->getCurrentAdminUserId();

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save new Shared Catalog.');
        $this->companyCreationMock->initializeCompanyExtraEntitiesCreation($urlExtensionName);
    }

    protected function createCustomerGroup($urlExtensionName, $taxClassId)
    {
        $this->groupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->customerGroupMock);

        $this->customerGroupMock->expects($this->once())
            ->method('setCode')
            ->with($urlExtensionName . CompanyCreation::CUSTOMER_GROUP_SUFFIX)
            ->willReturnSelf();

        $this->customerGroupMock->expects($this->once())
            ->method('setTaxClassId')
            ->with($taxClassId)
            ->willReturnSelf();

        $this->customerGroupMock->expects($this->any())
            ->method('getId')
            ->willReturn(190);

        $this->groupRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->customerGroupMock)
            ->willReturn($this->customerGroupMock);

        $this->fedexSaaSCommonConfigMock->expects($this->once())
            ->method('isTigerD200529Enabled')
            ->willReturn(true);

        $this->customerGroupAttributeHandlerMock->expects($this->once())
            ->method('addAttributeOption')
            ->with([190])
            ->willReturnSelf();
    }

    protected function getRetailCustomerTaxClassId($taxClassId) {
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->willReturnSelf();

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->taxClassRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->taxClassResultMock);

        $this->taxClassResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->taxClassMock]);

        $this->taxClassMock->expects($this->atMost(2))
            ->method('getClassId')
            ->willReturn($taxClassId);
    }

    protected function createRootCategory($urlExtensionName)
    {
        $rootCategoryId = 580;
        $createdRootCategoryId = 1000;
        $customerGroupId = 190;
        $categoryPermissions = [
            [
                'website_id' => CompanyCreation::MAIN_WEBSITE_ID,
                'customer_group_id' => $customerGroupId,
                'grant_catalog_category_view' => CompanyCreation::CATEGORY_PERMISSION_ALLOW,
                'grant_catalog_product_price' => CompanyCreation::CATEGORY_PERMISSION_ALLOW,
                'grant_checkout_items' => CompanyCreation::CATEGORY_PERMISSION_ALLOW
            ],
            [
                'website_id' => CompanyCreation::MAIN_WEBSITE_ID,
                'customer_group_id' => CompanyCreation::ALL_CUSTOMER_GROUPS_ID,
                'grant_catalog_category_view' => CompanyCreation::CATEGORY_PERMISSION_DENY,
                'grant_catalog_product_price' => CompanyCreation::CATEGORY_PERMISSION_DENY,
                'grant_checkout_items' => CompanyCreation::CATEGORY_PERMISSION_DENY
            ]
        ];

        $this->getOndemandRootCategory();

        $this->categoryInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->categoryInterfaceMock);

        $this->categoryInterfaceMock->expects($this->any())
           ->method('setCustomAttributes')
           ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setName')
            ->with($urlExtensionName . CompanyCreation::ROOT_CATEGORY_SUFFIX)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setIsActive')
            ->with(true)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setIncludeInMenu')
            ->with(true)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['show_promo_banner', true],
                ['pod2_0_editable', true],
                ['custom_use_parent_settings', true],
            )
            ->willReturnSelf();

        $this->customerGroupMock->expects($this->any())
            ->method('getId')
            ->willReturn($customerGroupId);

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setParentId')
            ->with($rootCategoryId)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->atMost(2))
            ->method('setStoreId')
            ->with(CompanyCreation::ONDEMAND_STORE_CODE)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturnCallback(function() use (&$rootCategoryId, &$createdRootCategoryId) {
                static $call = 0;
                $call++;
                if ($call == 1) {
                    return $rootCategoryId;
                }
                return $createdRootCategoryId;
            });

        $this->categoryRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->categoryInterfaceMock)
            ->willReturnSelf();
    }

    protected function createRootCategoryWithCouldNotSaveException($urlExtensionName)
    {
        $rootCategoryId = 580;
        $createdRootCategoryId = 1000;
        $customerGroupId = 190;
        $categoryPermissions = [
            [
                'website_id' => CompanyCreation::MAIN_WEBSITE_ID,
                'customer_group_id' => $customerGroupId,
                'grant_catalog_category_view' => CompanyCreation::CATEGORY_PERMISSION_ALLOW,
                'grant_catalog_product_price' => CompanyCreation::CATEGORY_PERMISSION_ALLOW,
                'grant_checkout_items' => CompanyCreation::CATEGORY_PERMISSION_ALLOW
            ],
            [
                'website_id' => CompanyCreation::MAIN_WEBSITE_ID,
                'customer_group_id' => CompanyCreation::ALL_CUSTOMER_GROUPS_ID,
                'grant_catalog_category_view' => CompanyCreation::CATEGORY_PERMISSION_DENY,
                'grant_catalog_product_price' => CompanyCreation::CATEGORY_PERMISSION_DENY,
                'grant_checkout_items' => CompanyCreation::CATEGORY_PERMISSION_DENY
            ]
        ];

        $this->getOndemandRootCategory();

        $this->categoryInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->categoryInterfaceMock);

        $this->categoryInterfaceMock->expects($this->any())
            ->method('setCustomAttributes')
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setName')
            ->with($urlExtensionName . CompanyCreation::ROOT_CATEGORY_SUFFIX)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setIsActive')
            ->with(true)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setIncludeInMenu')
            ->with(true)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['show_promo_banner', true],
                ['pod2_0_editable', true],
                ['custom_use_parent_settings', true],
            )
            ->willReturnSelf();

        $this->customerGroupMock->expects($this->any())
            ->method('getId')
            ->willReturn($customerGroupId);

        $this->categoryInterfaceMock->expects($this->once())
            ->method('setParentId')
            ->with($rootCategoryId)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->atMost(2))
            ->method('setStoreId')
            ->with(CompanyCreation::ONDEMAND_STORE_CODE)
            ->willReturnSelf();

        $this->categoryInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturnOnConsecutiveCalls($rootCategoryId, $createdRootCategoryId);

        $this->categoryRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->categoryInterfaceMock)
            ->willThrowException(new CouldNotSaveException(__('Could not save new Root Category.')));
    }

    protected function createRootCategoryWithNoSuchEntityException()
    {
        $this->getOndemandRootCategoryWithException();
    }

    protected function getOndemandRootCategory() {
        $rootCategoryId = 580;
        $this->storeGroupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->storeGroupMock);

        $this->storeGroupMock->expects($this->once())
            ->method('load')
            ->with(CompanyCreation::ONDEMAND_STORE_CODE, 'code')
            ->willReturnSelf();

        $this->storeGroupMock->expects($this->once())
            ->method('getRootCategoryId')
            ->willReturn($rootCategoryId);

        $this->categoryRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($rootCategoryId)
            ->willReturn($this->categoryInterfaceMock);
    }

    protected function getOndemandRootCategoryWithException() {
        $rootCategoryId = 580;
        $this->storeGroupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->storeGroupMock);

        $this->storeGroupMock->expects($this->once())
            ->method('load')
            ->with(CompanyCreation::ONDEMAND_STORE_CODE, 'code')
            ->willReturnSelf();

        $this->storeGroupMock->expects($this->once())
            ->method('getRootCategoryId')
            ->willReturn($rootCategoryId);

        $this->categoryRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($rootCategoryId)
            ->willThrowException(new NoSuchEntityException(__('Could not find store Root Category.')));
    }

    protected function createSharedCatalog($urlExtensionName, $taxClassId)
    {
        $createdRootCategoryId = 1000;
        $customerGroupId = 190;
        $sharedCatalogId = 1;
        $adminUserId = 5;

        $this->sharedCatalogFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->sharedCatalogMock);

        $this->sharedCatalogMock->expects($this->once())
            ->method('setName')
            ->with($urlExtensionName . CompanyCreation::SHARED_CATALOG_SUFFIX)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setDescription')
            ->with($urlExtensionName . CompanyCreation::SHARED_CATALOG_SUFFIX)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setCreatedBy')
            ->with($adminUserId)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setType')
            ->with(SharedCatalogInterface::TYPE_CUSTOM)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setCustomerGroupId')
            ->with($customerGroupId)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setTaxClassId')
            ->with($taxClassId)
            ->willReturnSelf();

        $this->sharedCatalogRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->sharedCatalogMock)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->any())
            ->method('getId')
            ->willReturn($sharedCatalogId);

        $this->companyConfigMock->expects($this->any())
            ->method('getSharedCatalogsMapIssueFixToggle')
            ->willReturn(true);

        $this->getProductsFromConfiguredSharedCatalog();
        $this->getCategoryFromConfiguredSharedCatalog();
        $this->getCurrentAdminUserId();

        $this->categoryManagementMock->expects($this->once())
            ->method('assignCategories')
            ->with($sharedCatalogId, [$createdRootCategoryId => $this->categoryInterfaceMock]);
    }

    protected function createSharedCatalogWithException($urlExtensionName, $taxClassId)
    {
        $customerGroupId = 190;
        $adminUserId = 5;

        $this->sharedCatalogFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->sharedCatalogMock);

        $this->sharedCatalogMock->expects($this->once())
            ->method('setName')
            ->with($urlExtensionName . CompanyCreation::SHARED_CATALOG_SUFFIX)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setDescription')
            ->with($urlExtensionName . CompanyCreation::SHARED_CATALOG_SUFFIX)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setCreatedBy')
            ->with($adminUserId)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setType')
            ->with(SharedCatalogInterface::TYPE_CUSTOM)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setCustomerGroupId')
            ->with($customerGroupId)
            ->willReturnSelf();

        $this->sharedCatalogMock->expects($this->once())
            ->method('setTaxClassId')
            ->with($taxClassId)
            ->willReturnSelf();

        $this->sharedCatalogRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->sharedCatalogMock)
            ->willThrowException(new CouldNotSaveException(__('Could not save new Shared Catalog.')));
    }

    protected function getProductsFromConfiguredSharedCatalog() {
        $defaultSharedCatalog = 50;
        $productSkus = ['12345-sku'];

        $this->ondemandConfigMock->expects($this->atMost(2))
            ->method('getDefaultSharedCatalog')
            ->willReturn($defaultSharedCatalog);

        $this->productManagementMock->expects($this->once())
            ->method('getProducts')
            ->with($defaultSharedCatalog)
            ->willReturn($productSkus);
    }

    protected function getCategoryFromConfiguredSharedCatalog() {
        $sharedCatalogId = 50;
        $categoryArray = [10];

        $this->categoryManagementMock->expects($this->once())
            ->method('getCategories')
            ->with($sharedCatalogId)
            ->willReturn($categoryArray);

        $this->categoryCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', $categoryArray)
            ->willReturn([$this->categoryInterfaceMock]);

        $this->categoryCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->categoryCollectionMock);
    }

    protected function getCurrentAdminUserId() {
        $adminUserId = 5;

        $this->adminSessionMock->expects($this->any())
            ->method('getUser')
            ->willReturn($this->adminUserMock);

        $this->adminUserMock->expects($this->any())
            ->method('getId')
            ->willReturn($adminUserId);
    }

    public function testPublishCompanyEntities()
    {
        $messageContent = [
            'creation_type' => 'all',
            'url_extension_name' => 'test_extension',
        ];
        $serializedMessage = json_encode($messageContent);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($messageContent)
            ->willReturn($serializedMessage);

        $this->messageMock->expects($this->once())
            ->method('setMessage')
            ->with($serializedMessage);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('createCompanyEntities', $this->messageMock);

        $this->companyCreationMock->publishCompanyEntities($messageContent);
    }
}
