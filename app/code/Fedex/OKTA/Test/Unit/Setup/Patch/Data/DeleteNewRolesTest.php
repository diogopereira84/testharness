<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Setup\Module\DataSetup;
use Fedex\OKTA\Model\UserRole\RoleHandler;
use Fedex\OKTA\Setup\Patch\Data\DeleteNewRoles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteNewRolesTest extends TestCase
{
    /**
     * @var DeleteNewRoles
     */
    private DeleteNewRoles $deleteNewRoles;

    /**
     * @var RoleHandler|MockObject
     */
    private RoleHandler $roleHandlerMock;

    /**
     * @var DataSetup|MockObject
     */
    private DataSetup $moduleDataSetupMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface $adapterMock;

    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(DataSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleHandlerMock = $this->createMock(RoleHandler::class);
        $this->adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->deleteNewRoles = new DeleteNewRoles($this->moduleDataSetupMock, $this->roleHandlerMock);
    }

    public function testApplySuccess()
    {
        $this->moduleDataSetupMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterMock);
        $this->roleHandlerMock->expects($this->once())->method('processNewAfterDeleteRole');
        $this->assertEquals(null, $this->deleteNewRoles->apply());
    }

    public function testGetAliases()
    {
        $this->assertEquals([], $this->deleteNewRoles->getAliases());
    }

    public function testGetDependencies()
    {
        $this->assertEquals([], $this->deleteNewRoles->getDependencies());
    }
}
