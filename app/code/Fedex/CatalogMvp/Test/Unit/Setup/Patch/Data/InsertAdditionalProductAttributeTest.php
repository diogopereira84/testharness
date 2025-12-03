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
use Fedex\CatalogMvp\Setup\Patch\Data\InsertAdditionalProductAttribute;

/**
 * Test class for InsertAdditionalProductAttributeTest
 */

class InsertAdditionalProductAttributeTest extends TestCase
{
    
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $insertAdditionalProductAttributeDataPatch;

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

        $this->insertAdditionalProductAttributeDataPatch = $this->getMockForAbstractClass(
            InsertAdditionalProductAttribute::class,
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
        
        
        $this->assertEquals(null, $this->insertAdditionalProductAttributeDataPatch->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->insertAdditionalProductAttributeDataPatch->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->insertAdditionalProductAttributeDataPatch->getDependencies());
    }
}

