<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Setup\Patch\Data\Create3PAttributes;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Fedex\ProductEngine\Setup\AddOptionToAttribute;

class Create3PAttributesTest extends TestCase
{
    /**
     * @var Create3PAttributes
     */
    private $create3PAttributes;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AddOptionToAttribute
     */
    private $addOptionToAttribute;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactory = $this->createMock(EavSetupFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->addOptionToAttribute = $this->createMock(AddOptionToAttribute::class);

        $objectManager = new ObjectManager($this);
        $this->create3PAttributes = $objectManager->getObject(
            Create3PAttributes::class,
            [
                'moduleDataSetup' => $this->moduleDataSetup,
                'eavSetupFactory' => $this->eavSetupFactory,
                'logger' => $this->logger,
                'addOptionToAttribute' => $this->addOptionToAttribute
            ]
        );
    }

    /**
     * Test apply
     *
     * @return void
     */
    public function testApply()
    {
        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturnSelf();
        $this->moduleDataSetup->expects($this->any())
            ->method('startSetup');
        $eavSetupFactoryObject = $this->createMock(EavSetup::class);
        $this->eavSetupFactory->expects($this->any())
            ->method('create')
            ->willReturn($eavSetupFactoryObject);
        $eavSetupFactoryObject->expects($this->any())
            ->method('removeAttribute')
            ->withConsecutive(
                [Product::ENTITY, 'unit_of_measure'],
                [Product::ENTITY, 'uom_quantity']
            );
        $eavSetupFactoryObject->expects($this->any())
            ->method('addAttribute')
            ->withConsecutive(
                [
                    Product::ENTITY,
                    'unit_of_measure',
                    [
                        'group' => 'Mirakl Marketplace',
                        'type' => 'varchar',
                        'label' => 'Unit of Measure',
                        'input' => 'select',
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => true,
                        'searchable' => true,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'unique' => false,
                        'apply_to' => 'simple',
                        'is_configurable' => false,
                        'used_in_product_listing' => true,
                        'default' => null,
                        'mirakl_is_exportable' => true,
                        'visible_in_advanced_search' => false,
                        'is_used_in_grid' => false,
                        'is_visible_in_grid' => false,
                        'is_filterable_in_grid' => false,
                        'is_product_level_default' => true,
                    ]
                ],
                [
                    Product::ENTITY,
                    'uom_quantity',
                    [
                        'group' => 'Mirakl Marketplace',
                        'type' => 'varchar',
                        'label' => 'UOM Quantity',
                        'input' => 'multiselect',
                        'backend' => ArrayBackend::class,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => true,
                        'searchable' => true,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'unique' => false,
                        'apply_to' => 'simple',
                        'is_configurable' => false,
                        'used_in_product_listing' => true,
                        'default' => null,
                        'mirakl_is_exportable' => true,
                        'visible_in_advanced_search' => false,
                        'is_used_in_grid' => false,
                        'is_visible_in_grid' => false,
                        'is_filterable_in_grid' => false,
                        'is_product_level_default' => true,
                    ]
                ]
            );
        $eavSetupFactoryObject->expects($this->any())
            ->method('getAttributeId')
            ->withConsecutive(
                [Product::ENTITY, 'unit_of_measure'],
                [Product::ENTITY, 'uom_quantity']
            )
            ->willReturnOnConsecutiveCalls(1, 2);
        $this->addOptionToAttribute->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                [
                    [
                        'attribute_id' => 1,
                        'values' => [
                            ['value' => 'box', 'choice_id' => '1448988601300'],
                            ['value' => 'each', 'choice_id' => '1448988601301']
                        ]
                    ]
                ],
                [
                    [
                        'attribute_id' => 2,
                        'values' => [
                            ['value' => '25', 'choice_id' => '1448988601302'],
                            ['value' => '50', 'choice_id' => '1448988601303'],
                            ['value' => '250', 'choice_id' => '1448988601304']
                        ]
                    ]
                ]
            );

        $this->create3PAttributes->apply();
    }
}
