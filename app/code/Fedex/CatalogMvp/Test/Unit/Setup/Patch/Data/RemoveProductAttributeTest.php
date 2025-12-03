<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Setup\Patch\Data\RemoveProductAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Test class for RemoveProductAttribute
 */

class RemoveProductAttributeTest extends TestCase
{
    
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $loggerMock;
    protected $removeProductAttributePatch;

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
                    'removeAttribute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->removeProductAttributePatch = $this->getMockForAbstractClass(
            RemoveProductAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test apply function
     */
    public function testapply()
    {
        $this->eavSetupFactoryMock->expects($this->once())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->once())
        ->method('removeAttribute')
        ->willReturnSelf();
        
        $this->assertEquals(null, $this->removeProductAttributePatch->apply());
    }

    /**
     * Test apply with exception function
     */
    public function testapplyWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->eavSetupFactoryMock->expects($this->once())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->once())
        ->method('removeAttribute')
        ->willThrowException($exception);
        $this->loggerMock->expects($this->any())
        ->method('critical')
        ->willReturnSelf();
        
        $this->assertEquals(null, $this->removeProductAttributePatch->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->removeProductAttributePatch->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->removeProductAttributePatch->getDependencies());
    }
}

