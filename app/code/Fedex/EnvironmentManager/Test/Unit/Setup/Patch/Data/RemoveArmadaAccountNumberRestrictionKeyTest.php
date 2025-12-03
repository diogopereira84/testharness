<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Manish Chaubey <manish.chaubey.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Fedex\EnvironmentManager\Setup\Patch\Data\RemoveArmadaAccountNumberRestrictionKey;

class RemoveArmadaAccountNumberRestrictionKeyTest extends TestCase
{
    /**
     * @var RemoveArmadaAccountNumberRestrictionKey
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

    protected function setUp(): void
    {
        $this->setup = $this->createMock(SchemaSetupInterface::class);
        $this->connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->setup->method('getConnection')->willReturn($this->connection);

        $this->patch = new RemoveArmadaAccountNumberRestrictionKey($this->setup);
    }

    public function testInstanceOfDataPatchInterface()
    {
        $this->assertInstanceOf(DataPatchInterface::class, $this->patch);
    }

    public function testGetDependencies()
    {
        $this->assertEquals([], $this->patch->getDependencies());
    }

    public function testGetAliases()
    {
        $this->assertEquals([], $this->patch->getAliases());
    }

    public function testApplyDeletesRows()
    {
        $this->connection->expects($this->exactly(count(RemoveArmadaAccountNumberRestrictionKey::CORE_CONFIG_DATA_KEY)))
            ->method('delete')
            ->with(
                $this->equalTo(null),
                true
            );

        $this->patch->apply();
    }
}
