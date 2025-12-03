<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Setup\Patch\Data;

use Fedex\OKTA\Model\UserRole\RoleHandler;
use Fedex\OKTA\Setup\Patch\Data\UpdateProductRolePermissions;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Setup\Module\DataSetup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateProductRolePermissionsTest extends TestCase
{
    /**
     * @var UpdateProductRolePermissions
     */
    private UpdateProductRolePermissions $updateProductRolePermissions;

    /**
     * @var DataSetup|MockObject
     */
    private DataSetup $moduleDataSetupMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface $adapterMock;

    /**
     * @var RoleHandler|MockObject
     */
    private RoleHandler $roleHandlerMock;

    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(DataSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleHandlerMock = $this->createMock(RoleHandler::class);
        $this->adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateProductRolePermissions = new UpdateProductRolePermissions($this->moduleDataSetupMock, $this->roleHandlerMock);
    }

    public function testApply(): void
    {
        $this->moduleDataSetupMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterMock);
        $this->roleHandlerMock->expects($this->once())->method('updateProductRolePermission');
        $this->assertEquals(null, $this->updateProductRolePermissions->apply());
    }

    public function testGetAliases()
    {
        $this->assertEquals([], $this->updateProductRolePermissions->getAliases());
    }

    public function testGetDependencies()
    {
        $this->assertEquals([], $this->updateProductRolePermissions->getDependencies());
    }
}
