<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Model;

use Fedex\SelfReg\Model\CategoryPermissionProcessor;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;
use Magento\SharedCatalog\Model\ResourceModel\Permission\Collection as PermissionCollection;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\Permissions\Synchronizer;
use Magento\SharedCatalog\Model\State;
use Magento\CatalogPermissions\Model\Permission as CatalogPermission;
use Magento\SharedCatalog\Model\Permission as SharedCatalogPermission;
use Magento\SharedCatalog\Model\ResourceModel\Permission as PermissionResource;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use PHPUnit\Framework\TestCase;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\ResourceConnection;

class CategoryPermissionProcessorTest extends TestCase
{
    private $permissionCollectionFactory;
    private $catalogPermissionManagement;
    private $permissionsSynchronizer;
    private $sharedCatalogState;
    private $sharedCatalogPermissionResource;
    private $sharedCatalogInvalidation;
    private $permissionCollection;
    private $catalogMvpHelper;
    private $categoryPermissionProcessor;
    private $folderPermission;
    private $categoryRepository;
    private $resourceConnection;
    private $parentUserGroupResourceModel;

    protected function setUp(): void
    {
        $this->permissionCollectionFactory = $this->createMock(PermissionCollectionFactory::class);
        $this->catalogPermissionManagement = $this->createMock(CatalogPermissionManagement::class);
        $this->permissionsSynchronizer = $this->createMock(Synchronizer::class);
        $this->sharedCatalogState = $this->createMock(State::class);
        $this->sharedCatalogPermissionResource = $this->createMock(PermissionResource::class);
        $this->sharedCatalogInvalidation = $this->createMock(SharedCatalogInvalidation::class);
        $this->permissionCollection = $this->createMock(PermissionCollection::class);
        $this->catalogMvpHelper = $this->createMock(CatalogMvp::class);
        $this->folderPermission = $this->createMock(FolderPermission::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->parentUserGroupResourceModel = $this->createMock(\Fedex\SelfReg\Model\ResourceModel\ParentUserGroup::class);

        $this->categoryPermissionProcessor = new CategoryPermissionProcessor(
            $this->permissionCollectionFactory,
            $this->catalogPermissionManagement,
            $this->permissionsSynchronizer,
            $this->sharedCatalogState,
            $this->folderPermission,
            $this->categoryRepository,
            $this->resourceConnection,
            $this->sharedCatalogPermissionResource,
            $this->sharedCatalogInvalidation,
            $this->catalogMvpHelper,
            $this->parentUserGroupResourceModel
        );
    }

    public function testProcessPermissionsReturnsFalseIfToggleOff()
    {
        $this->catalogMvpHelper->method('isEditFolderAccessEnabled')->willReturn(false);
        $result = $this->categoryPermissionProcessor->processPermissions(1, 10, false, [1 => CatalogPermission::PERMISSION_ALLOW]);
        $this->assertFalse($result);
    }

    public function testProcessPermissionsReturnsFalseIfEmpty()
    {
        $this->catalogMvpHelper->method('isEditFolderAccessEnabled')->willReturn(true);
        $result = $this->categoryPermissionProcessor->processPermissions(1, 10, false, []);
        $this->assertFalse($result);
    }

    public function testProcessPermissionsAllowAndDeny()
    {
        $this->catalogMvpHelper->method('isEditFolderAccessEnabled')->willReturn(true);
        $scopeId = 1;
        $categoryId = 20;
        $selectedGroupIds = [
            3 => CatalogPermission::PERMISSION_ALLOW,
            4 => CatalogPermission::PERMISSION_DENY
        ];

        $this->permissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->permissionCollection);

        $this->permissionCollection->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['category_id', $categoryId],
                ['website_id', ['seq' => $scopeId]],
                ['customer_group_id', ['in' => array_keys($selectedGroupIds)]]
            )->willReturnSelf();

        $this->permissionCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $permissionItemAllow = $this->getMockBuilder(SharedCatalogPermission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setPermission'])
            ->getMock();
        $permissionItemAllow->expects($this->once())
            ->method('setPermission')
            ->with(CatalogPermission::PERMISSION_ALLOW);

        $permissionItemDeny = $this->getMockBuilder(SharedCatalogPermission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setPermission'])
            ->getMock();
        $permissionItemDeny->expects($this->any())
            ->method('setPermission')
            ->with(CatalogPermission::PERMISSION_DENY);

        $this->catalogPermissionManagement->expects($this->exactly(1))
            ->method('getSharedCatalogPermission')
            ->withConsecutive(
                [$categoryId, null, 3],
                [$categoryId, null, 4]
            )
            ->willReturnOnConsecutiveCalls($permissionItemAllow, $permissionItemDeny);

        $this->sharedCatalogPermissionResource->expects($this->exactly(1))
            ->method('save')
            ->withConsecutive([$permissionItemAllow], [$permissionItemDeny]);

        $this->permissionsSynchronizer->expects($this->exactly(1))
            ->method('updateCategoryPermissions')
            ->withConsecutive(
                [$categoryId, [3]],
                [$categoryId, [4]]
            );

        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('reindexCatalogPermissions')
            ->with([$categoryId]);

        $connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->expects($this->any())
            ->method('delete')
            ->withConsecutive([
                'magento_catalogpermissions',
                ['category_id = ?' => $categoryId, 'customer_group_id IN (?)' => [4]]
            ], [
                'sharedcatalog_category_permissions',
                ['category_id = ?' => $categoryId, 'customer_group_id IN (?)' => [4]]
            ]
            );
        $this->resourceConnection->method('getConnection')->willReturn($connectionMock);

        $result = $this->categoryPermissionProcessor->processPermissions($scopeId, $categoryId, true, $selectedGroupIds);
        $this->assertTrue($result);
    }

    public function testProcessPermissionsNoChangesNeeded()
    {
        $this->catalogMvpHelper->method('isEditFolderAccessEnabled')->willReturn(true);
        $scopeId = 2;
        $categoryId = 30;
        $selectedGroupIds = [
            5 => CatalogPermission::PERMISSION_ALLOW,
        ];

        $permissionCollectionItem = $this->getMockBuilder(SharedCatalogPermission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerGroupId', 'getPermission'])
            ->getMock();
        $permissionCollectionItem->method('getCustomerGroupId')->willReturn(5);
        $permissionCollectionItem->method('getPermission')->willReturn(CatalogPermission::PERMISSION_ALLOW);

        $this->permissionCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->permissionCollection);

        $this->permissionCollection->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->permissionCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$permissionCollectionItem]);

        $this->catalogPermissionManagement->expects($this->never())->method('getSharedCatalogPermission');
        $this->sharedCatalogPermissionResource->expects($this->never())->method('save');
        $this->permissionsSynchronizer->expects($this->never())->method('updateCategoryPermissions');
        $this->sharedCatalogInvalidation->expects($this->never())->method('reindexCatalogPermissions');

        $result = $this->categoryPermissionProcessor->processPermissions($scopeId, $categoryId, true, $selectedGroupIds);
        $this->assertTrue($result);
    }
}