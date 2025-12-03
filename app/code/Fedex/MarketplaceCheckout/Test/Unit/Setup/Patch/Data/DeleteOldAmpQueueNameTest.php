<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Setup\Patch\Data\DeleteOldAmpQueueName;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

class DeleteOldAmpQueueNameTest extends TestCase
{

    /**
     * Tests that applying the patch deletes the old AMP queue name correctly.
     * @return void
     */
    public function testApplyDeletesOldQueueName()
    {
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $connectionMock = $this->createMock(AdapterInterface::class);

        $moduleDataSetupMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $moduleDataSetupMock->expects($this->once())
            ->method('getTable')
            ->with('queue')
            ->willReturn('queue_table');

        $connectionMock->expects($this->once())
            ->method('startSetup');

        $connectionMock->expects($this->once())
            ->method('delete')
            ->with(
                'queue_table',
                ['name = ?' => 'SendOrderQueueToMirakl']
            );

        $connectionMock->expects($this->once())
            ->method('endSetup');

        $patch = new DeleteOldAmpQueueName($moduleDataSetupMock);
        $patch->apply();
    }

    /**
     * Tests that the patch is not applied if the queue name does not exist.
     * @return void
     */
    public function testGetDependenciesReturnsEmptyArray()
    {
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $patch = new DeleteOldAmpQueueName($moduleDataSetupMock);
        $this->assertEquals([], $patch::getDependencies());
    }

    /**
     * Tests that the patch does not have any aliases.
     * @return void
     */
    public function testGetAliasesReturnsEmptyArray()
    {
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $patch = new DeleteOldAmpQueueName($moduleDataSetupMock);
        $this->assertEquals([], $patch->getAliases());
    }
}
