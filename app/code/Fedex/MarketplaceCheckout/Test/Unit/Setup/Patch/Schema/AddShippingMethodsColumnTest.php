<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Setup\Patch\Schema;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Setup\Patch\Schema\AddShippingMethodsColumn;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class AddShippingMethodsColumnTest extends TestCase
{
    /**
     * Test that applying the patch successfully adds the shipping methods column.
     * @return void
     */
    public function testApplyAddsColumn()
    {
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $connectionMock = $this->createMock(AdapterInterface::class);

        $moduleDataSetupMock->expects($this->once())
            ->method('startSetup');
        $moduleDataSetupMock->expects($this->once())
            ->method('endSetup');

        $moduleDataSetupMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $moduleDataSetupMock->expects($this->once())
            ->method('getTable')
            ->with('mirakl_shop')
            ->willReturn('mirakl_shop_table');

        $connectionMock->expects($this->once())
            ->method('addColumn')
            ->with(
                'mirakl_shop_table',
                'shipping_methods',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Shipping methods',
                ]
            );

        $patch = new AddShippingMethodsColumn($moduleDataSetupMock);
        $patch->apply();
    }

    /**
     * Test that the patch does not throw an exception when applied.
     * @return void
     */
    public function testGetDependenciesReturnsEmptyArray()
    {
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $patch = new AddShippingMethodsColumn($moduleDataSetupMock);
        $this->assertEquals([], $patch::getDependencies());
    }

    /**
     * Test that the patch does not have any aliases.
     * @return void
     */
    public function testGetAliasesReturnsEmptyArray()
    {
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $patch = new AddShippingMethodsColumn($moduleDataSetupMock);
        $this->assertEquals([], $patch->getAliases());
    }
}
