<?php

use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Fedex\EnvironmentManager\Setup\Patch\Data\RemoveXmenExpiredItemToggle;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\DB\Adapter\AdapterInterface;

class RemoveXmenExpiredItemToggleTest extends TestCase
{
    /**
     * @var RemoveXmenExpiredItemToggle
     */
    private $patch;

    /**
     * @var SchemaSetupInterface|MockObject
     */
    private $setup;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * Setup function
     */
    protected function setUp(): void
    {
        $this->setup = $this->createMock(SchemaSetupInterface::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->setup->method('getConnection')->willReturn($this->connection);
        $this->patch = new RemoveXmenExpiredItemToggle($this->setup);
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
        $this->connection->expects($this->exactly(count(RemoveXmenExpiredItemToggle::CORE_CONFIG_DATA_KEY)))
            ->method('delete')
            ->with(
                $this->equalTo(null),
                true
            );

        $this->patch->apply();
    }
}