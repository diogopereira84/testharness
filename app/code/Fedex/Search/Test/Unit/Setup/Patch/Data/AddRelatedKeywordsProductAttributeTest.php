<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Search\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\Search\Setup\Patch\Data\AddRelatedKeywordsProductAttribute;
use PHPUnit\Framework\TestCase;

/**
 * Test class AddRelatedKeywordsProductAttribute
 */
class AddRelatedKeywordsProductAttributeTest extends TestCase
{
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var AddRelatedKeywordsProductAttribute
     */
    private AddRelatedKeywordsProductAttribute $addRelatedKeywordsProductAttribute;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->onlyMethods(['create'])
            ->addMethods(['addAttribute', 'removeAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();

        $adapter = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $moduleDataSetup->method('getConnection')->willReturn($adapter);

        $this->addRelatedKeywordsProductAttribute = new AddRelatedKeywordsProductAttribute($moduleDataSetup, $this->eavSetupFactory);
    }

    /**
     * Test apply function
     *
     * @return void
     */
    public function testApply()
    {
        $this->assertEquals(null, $this->addRelatedKeywordsProductAttribute->apply());
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
        $this->assertEquals(null, $this->addRelatedKeywordsProductAttribute->revert());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->addRelatedKeywordsProductAttribute->getAliases());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->addRelatedKeywordsProductAttribute->getDependencies());
    }
}
