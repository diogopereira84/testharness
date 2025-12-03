<?php

declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\ViewModel;

use Fedex\CustomerGroup\ViewModel\AssignPermissions;
use Fedex\SelfReg\Block\User\Search;
use Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\CollectionFactory as RolePermissionCollectionFactory;
use Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\Collection;
use PHPUnit\Framework\TestCase;

class AssignPermissionsTest extends TestCase
{
    /**
     * @var Search|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchMock;
    /**
     * @var RolePermissionCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $rolePermissionCollectionFactoryMock;
    /**
     * @var Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionMock;

    protected function setUp(): void
    {
        $this->searchMock = $this->createMock(Search::class);
        $this->rolePermissionCollectionFactoryMock = $this->createMock(RolePermissionCollectionFactory::class);
        $this->collectionMock = $this->createMock(Collection::class);
    }

    public function testGetAllRolePermissions()
    {
        $this->rolePermissionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('setOrder')
            ->with('sort_order', 'ASC')
            ->willReturnSelf();

        $viewModel = new AssignPermissions(
            $this->searchMock,
            $this->rolePermissionCollectionFactoryMock
        );

        $result = $viewModel->getAllRolePermission();
        $this->assertSame($this->collectionMock, $result);
    }
}
