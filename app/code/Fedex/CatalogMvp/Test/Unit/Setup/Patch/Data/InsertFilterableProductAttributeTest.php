<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Fedex\CatalogMvp\Setup\Patch\Data\InsertFilterableProductAttribute;

/**
 * Test class for InsertPublishedProductAttribute
 */

class InsertFilterableProductAttributeTest extends TestCase
{
    
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $insertPublishedProductAttributeDataPatch;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->moduleDataSetupInterfaceMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
        ->setMethods(
            [
                'create',
                'addAttribute',
                'addAttributeToGroup'
            ]
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->insertPublishedProductAttributeDataPatch = $this->getMockForAbstractClass(
            InsertFilterableProductAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
            ]
        );
    }

    /**
     * Test apply function
     */
    public function testapply()
    {
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttribute')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttributeToGroup')
        ->willReturnSelf();
        
        
        $this->assertEquals(null, $this->insertPublishedProductAttributeDataPatch->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->insertPublishedProductAttributeDataPatch->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->insertPublishedProductAttributeDataPatch->getDependencies());
    }
}

