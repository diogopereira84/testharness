<?php
/**
 * @category     Fedex
 * @package      Fedex_FujitsuCore
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use Exception;
use Fedex\MarketplaceProduct\Setup\Patch\Data\CreateMapSkuProductAttribute;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateMapSkuProductAttributeTest extends TestCase
{
    /**
     * @var CreateMapSkuProductAttribute
     */
    private CreateMapSkuProductAttribute $instance;

    /**
     * @var ModuleDataSetupInterface|MockObject
     */
    private ModuleDataSetupInterface|MockObject $moduleDataSetupMock;

    /**
     * @var EavSetupFactory|MockObject
     */
    private EavSetupFactory|MockObject $eavSetupFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var EavSetup|MockObject
     */
    private EavSetup|MockObject $eavSetupMock;

    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactoryMock = $this->createMock(EavSetupFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->instance = new CreateMapSkuProductAttribute(
            $this->moduleDataSetupMock,
            $this->eavSetupFactoryMock,
            $this->loggerMock
        );

        $this->eavSetupMock = $this->createMock(EavSetup::class);
    }

    public function testGetAliases(): void
    {
        $result = $this->instance->getAliases();
        static::assertIsArray($result);
        static::assertEmpty($result);
    }

    public function testGetDependencies(): void
    {
        $result = $this->instance->getDependencies();
        static::assertIsArray($result);
        static::assertEmpty($result);
    }

    public function testApplyWithException(): void
    {
        $this->eavSetupFactoryMock->expects(static::once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetupMock])
            ->willReturn($this->eavSetupMock);
        
        $exception = new Exception();
        $this->eavSetupMock->expects(static::once())
            ->method('addAttribute')
            ->with(
                Product::ENTITY,
                CreateMapSkuProductAttribute::MAP_SKU_ATTRIBUTE_CODE,
                [
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'input' => 'text',
                    'label' => __('Map SKU'),
                    'required' => false,
                    'type' => 'varchar',
                    'visible' => true,
                    'sort_order' => 50,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'searchable' => true,
                    'user_defined' => false,
                    'filterable' => true,
                    'filterable_in_search' => true
                ]
            )
            ->willThrowException($exception);
          
        $this->loggerMock->expects(static::once())
          ->method('error');

        $this->instance->apply();
    }

    public function testApply(): void
    {
        $this->eavSetupFactoryMock->expects(static::once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetupMock])
            ->willReturn($this->eavSetupMock);
        
        $this->eavSetupMock->expects(static::once())
            ->method('addAttribute')
            ->with(
                Product::ENTITY,
                CreateMapSkuProductAttribute::MAP_SKU_ATTRIBUTE_CODE,
                [
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'input' => 'text',
                    'label' => __('Map SKU'),
                    'required' => false,
                    'type' => 'varchar',
                    'visible' => true,
                    'sort_order' => 50,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'searchable' => true,
                    'user_defined' => false,
                    'filterable' => true,
                    'filterable_in_search' => true
                ]
            );

        $this->instance->apply();
    }
}
