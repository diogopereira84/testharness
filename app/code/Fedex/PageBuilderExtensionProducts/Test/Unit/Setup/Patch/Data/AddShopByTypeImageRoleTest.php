<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\PageBuilderExtensionProducts\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\PageBuilderExtensionProducts\Setup\Patch\Data\AddShopByTypeImageRole;
use PHPUnit\Framework\TestCase;

/**
 * Test class AddShopByTypeImageRole
 */
class AddShopByTypeImageRoleTest extends TestCase
{
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var AddShopByTypeImageRole
     */
    private AddShopByTypeImageRole $addShopByTypeImageRole;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->onlyMethods(['create'])
            ->addMethods(['addAttribute', 'getAttributeId', 'getAllAttributeSetIds', 'addAttributeToGroup', 'removeAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);

        $this->addShopByTypeImageRole = new AddShopByTypeImageRole($moduleDataSetup, $this->eavSetupFactory);
    }

    /**
     * Test apply function
     *
     * @return void
     */
    public function testApply()
    {
        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('getAttributeId')->willReturn(1);
        $this->eavSetupFactory->expects($this->any())->method('getAllAttributeSetIds')->willReturn([1,2,3]);
        $this->eavSetupFactory->expects($this->any())->method('addAttributeToGroup')->willReturnSelf();
        $this->assertEquals(null, $this->addShopByTypeImageRole->apply());
    }

    /**
     * Test revert function
     *
     * @return void
     */
    public function testRevert()
    {
        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('removeAttribute')->willReturnSelf();
        $this->assertEquals(null, $this->addShopByTypeImageRole->revert());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->addShopByTypeImageRole->getAliases());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->addShopByTypeImageRole->getDependencies());
    }
}
