<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Group;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory as PermissionsCollectionFactory;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup\CollectionFactory as ParentUserGroupCollectionFactory;
use Fedex\SelfReg\Model\CustomerGroupPermissionManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Company\Model\Company;
use Magento\Customer\Model\Customer;
use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup\Collection;
use Magento\Framework\DB\Select;
use Magento\SharedCatalog\Model\ResourceModel\Permission\Collection as PermissionCollection;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\Permission;

class CustomerGroupPermissionManagerTest extends TestCase
{
    /**
     * @var (\Magento\Customer\Model\Group & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $groupModelMock;
    protected $permissionsCollectionFactoryMock;
    protected $deliveryHelperMock;
    protected $collectionFactoryMock;
    protected $customerMock;
    protected $companyMock;
    protected $collectionMock;
    protected $selectMock;
    protected $catalogPermissionManagementMock;
    protected $sharedCategoryPermissionMock;
    /**
     * @var (\Magento\SharedCatalog\Model\ResourceModel\Permission\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $permissionCollectionMock;
    protected $customerGroupPermissionManager;
    protected function setUp(): void
    {
        $this->groupModelMock = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->permissionsCollectionFactoryMock = $this->getMockBuilder(PermissionsCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();
        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', 'getAssignedCompany'])
            ->getMockForAbstractClass();
        $this->collectionFactoryMock = $this->getMockBuilder(ParentUserGroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();
        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'addFieldToFilter', 'getData'])
            ->addMethods(['create'])
            ->getMockForAbstractClass();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join'])
            ->getMockForAbstractClass();
        $this->permissionCollectionMock = $this->getMockBuilder(PermissionCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getData', 'getItems'])
            ->getMockForAbstractClass();
        $this->catalogPermissionManagementMock = $this->getMockBuilder(CatalogPermissionManagement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setAllowPermissions'])
            ->getMock();
        $this->sharedCategoryPermissionMock = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerGroupId', 'getPermission'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->customerGroupPermissionManager = $objectManager->getObject(
            CustomerGroupPermissionManager::class,
            [
                'groupModel' => $this->groupModelMock,
                'sharedCatalogPermissionCollectionFactory' => $this->permissionsCollectionFactoryMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'catalogPermissionManagement' => $this->catalogPermissionManagementMock
            ]
        );
    }

    public function testGetCustomerGroupsList()
    {
        // Mock the customer and company group ID
        $this->deliveryHelperMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->with($this->customerMock)
            ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(1); // Parent group ID

        // Mock the collection to return subgroups
        $this->collectionFactoryMock->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->method('getSelect')
            ->willReturn($this->selectMock);

        $this->selectMock->method('join')
            ->willReturnSelf();

        $this->collectionMock->method('addFieldToFilter')
            ->willReturnSelf();

        $this->collectionMock->method('getData')
            ->willReturn([
                ['customer_group_id' => 2, 'customer_group_code' => 'Group 2'],
                ['customer_group_id' => 3, 'customer_group_code' => 'Group 3'],
            ]);

        // Expected result no longer includes the default group
        $expectedResult = [
            2 => ['customer_group_code' => 'Group 2'],
            3 => ['customer_group_code' => 'Group 3'],
        ];

        // Assert the actual result matches the updated expected result
        $this->assertEquals($expectedResult, $this->customerGroupPermissionManager->getCustomerGroupsList());
    }


    public function testGetUserGroupsByCategory()
    {
        $this->customerGroupPermissionManager = $this->getMockBuilder(CustomerGroupPermissionManager::class)
            ->setConstructorArgs([
                $this->groupModelMock,
                $this->permissionsCollectionFactoryMock,
                $this->deliveryHelperMock,
                $this->collectionFactoryMock,
                $this->catalogPermissionManagementMock
            ])
            ->onlyMethods(['getCustomerGroupsList'])
            ->getMock();

        $sharedCategoryPermissions = [
            $this->createConfiguredMock(\Magento\SharedCatalog\Model\Permission::class, [
                'getCustomerGroupId' => 2,
                'getPermission' => -1
            ])
        ];

        $this->customerGroupPermissionManager->method('getCustomerGroupsList')->willReturn([
            1 => ['customer_group_code' => 'Group 1'],
            2 => ['customer_group_code' => 'Group 2']
        ]);

        $permissionCollectionMock = $this->createMock(PermissionCollection::class);
        $this->permissionsCollectionFactoryMock->method('create')->willReturn($permissionCollectionMock);
        $permissionCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionCollectionMock->method('getItems')->willReturn($sharedCategoryPermissions);
        $this->sharedCategoryPermissionMock->method('getCustomerGroupId')->willReturn(2);
        $this->sharedCategoryPermissionMock->method('getPermission')->willReturn(-1);

        $expectedResult = [
            1 => ['customer_group_code' => 'Group 1', 'permission' => -1],
            2 => ['customer_group_code' => 'Group 2', 'permission' => -1]
        ];

        $this->assertEquals($expectedResult, $this->customerGroupPermissionManager->getUserGroupsByCategory(1));
    }

    public function testDoesDenyAllPermissionExist()
    {
        $categoryId = 1;

        $permissionCollectionMock = $this->createMock(PermissionCollection::class);
        $this->permissionsCollectionFactoryMock->method('create')->willReturn($permissionCollectionMock);
        $permissionCollectionMock->method('addFieldToFilter')->willReturnSelf();

        $permissionCollectionMock->method('count')->willReturn(0);

        $this->assertFalse($this->customerGroupPermissionManager->doesDenyAllPermissionExist($categoryId));
    }

    public function testGetAllowedGroups()
    {
        $expectedResult = ['1'];
        $permissionCollectionMock = $this->createMock(PermissionCollection::class);
        $this->permissionsCollectionFactoryMock->method('create')->willReturn($permissionCollectionMock);
        $permissionCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $permissionCollectionMock->method('getData')->willReturn(
            [
                1 => ['customer_group_id' => '1', 'permission' => '-1'],
                2 => ['customer_group_id' => '2', 'permission' => '-2']
            ]
        );

        $this->assertEquals($expectedResult, $this->customerGroupPermissionManager->getAllowedGroups('1', ['1', '2']));
    }
}
