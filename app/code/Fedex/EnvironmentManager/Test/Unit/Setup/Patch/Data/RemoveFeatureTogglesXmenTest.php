<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Fedex\EnvironmentManager\Setup\Patch\Data\RemoveFeatureTogglesXmen;

class RemoveFeatureTogglesXmenTest extends TestCase
{
    /**
     * @var ToggleRemoveKey
     */
    private $patch;

    /**
     * @var SchemaSetupInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $setup;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    /**
     * Setup function
     */
    protected function setUp(): void
    {
        $this->setup = $this->createMock(SchemaSetupInterface::class);
        $this->connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->setup->method('getConnection')->willReturn($this->connection);
        $this->patch = new RemoveFeatureTogglesXmen($this->setup);
    }
    
    /**
     * Test function InstanceOfDataPatchInterface
     *
     * @return void
     */
    public function testInstanceOfDataPatchInterface()
    {
        $this->assertInstanceOf(DataPatchInterface::class, $this->patch);
    }

    /**
     * Test function testGetAliases
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->patch->getDependencies());
    }

    /**
     * Test function testGetAliases
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->patch->getAliases());
    }

    /**
     * Test function testApplyDeletesRows
     *
     * @return void
     */
    public function testApplyDeletesRows()
    {
        $this->connection->expects($this->exactly(count(RemoveFeatureTogglesXmen::CORE_CONFIG_DATA_KEY)))
            ->method('delete')
            ->with(
                $this->equalTo(null),
                true
            );

        $this->patch->apply();
    }
}
