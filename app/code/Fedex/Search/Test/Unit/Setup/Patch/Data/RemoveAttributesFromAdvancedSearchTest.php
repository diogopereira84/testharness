<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Search\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\Search\Setup\Patch\Data\RemoveAttributesFromAdvancedSearch;
use PHPUnit\Framework\TestCase;

/**
 * Test class RemoveAttributesFromAdvancedSearch
 */
class RemoveAttributesFromAdvancedSearchTest extends TestCase
{
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var RemoveAttributesFromAdvancedSearch
     */
    private RemoveAttributesFromAdvancedSearch $removeAttributesFromAdvancedSearch;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->onlyMethods(['create'])
            ->addMethods(['addAttribute', 'removeAttribute', 'updateAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('updateAttribute')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('removeAttribute')->willReturn(1);

        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $select->method("from")->willReturnSelf();
        $select->method("join")->willReturnSelf();
        $select->method("where")->willReturnSelf();
        
        $adapter = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $adapter->method("select")->willReturn($select);
        $adapter->method("fetchAll")->willReturn([["attribute_id" => 1]]);

        $moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $moduleDataSetup->method('getConnection')->willReturn($adapter);

        $this->removeAttributesFromAdvancedSearch = new RemoveAttributesFromAdvancedSearch($moduleDataSetup, $this->eavSetupFactory);
    }

    /**
     * Test apply function
     *
     * @return void
     */
    public function testApply()
    {
        $this->assertEquals($this->removeAttributesFromAdvancedSearch, $this->removeAttributesFromAdvancedSearch->apply());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->removeAttributesFromAdvancedSearch->getAliases());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->removeAttributesFromAdvancedSearch->getDependencies());
    }
}
