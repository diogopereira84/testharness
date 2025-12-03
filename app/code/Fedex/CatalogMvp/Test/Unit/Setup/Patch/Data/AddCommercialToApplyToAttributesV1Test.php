<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Setup\Patch\Data;

use Fedex\CatalogMvp\Setup\Patch\Data\AddCommercialToApplyToAttributesV1;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddCommercialToApplyToAttributesV1Test extends TestCase
{
    /** @var ModuleDataSetupInterface&MockObject */
    private $moduleDataSetup;
    /** @var EavSetupFactory&MockObject */
    private $eavSetupFactory;
    /** @var EavSetup&MockObject */
    private $eavSetup;
    /** @var AdapterInterface&MockObject */
    private $connection;
    /** @var AddCommercialToApplyToAttributes */
    private $patch;
    
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactory = $this->createMock(EavSetupFactory::class);
        $this->eavSetup = $this->createMock(EavSetup::class);
        $this->connection = $this->createMock(AdapterInterface::class);

        $this->moduleDataSetup->method('getConnection')->willReturn($this->connection);
        $this->eavSetupFactory->method('create')->willReturn($this->eavSetup);

        $this->patch = new AddCommercialToApplyToAttributesV1(
            $this->moduleDataSetup,
            $this->eavSetupFactory
        );
    }

    public function testApplyAddsCommercialToApplyToIfNotPresent(): void
    {
        $attributes = [
            'price', 'special_price', 'special_from_date', 'special_to_date', 'cost', 'weight',
            'manufacturer', 'tier_price', 'color', 'minimal_price', 'published', 'country_of_manufacture',
            'msrp', 'msrp_display_actual_price_type', 'tax_class_id', 'gift_wrapping_available',
            'gift_wrapping_price', 'customizable', 'mirakl_shop_ids', 'mirakl_offer_state_ids',
            'mirakl_sync', 'mirakl_category_id', 'mirakl_authorized_shop_ids', 'mirakl_shops_skus',
            'mirakl_mcm_product_id', 'mirakl_mcm_is_operator_master', 'mirakl_mcm_variant_group_code',
            'production_days', 'shape', 'cta_value', 'unit_of_measure', 'category_punchout',
            'weight_unit', 'upload_file_search_action', 'customize_search_action',
            'product_created_date', 'product_updated_date', 'product_attribute_sets_id',
            'unit_cost', 'base_quantity', 'base_price'
        ];
        // Simulate getAttribute returning an attribute with apply_to not containing 'commercial'
        $this->eavSetup->method('getAttribute')
            ->willReturn(['apply_to' => 'simple,virtual']);
        // updateAttribute should be called for each attribute
        $this->eavSetup->expects($this->exactly(count($attributes)))
            ->method('updateAttribute')
            ->withConsecutive(
                ...array_map(function ($attributeCode) {
                    return [
                        Product::ENTITY,
                        $attributeCode,
                        'apply_to',
                        'simple,virtual,commercial'
                    ];
                }, $attributes)
            );
        $this->connection->expects($this->once())->method('startSetup');
        $this->connection->expects($this->once())->method('endSetup');
        $this->patch->apply();
    }
    
    public function testApplyDoesNotAddCommercialIfAlreadyPresent(): void
    {
        $this->eavSetup->method('getAttribute')
            ->willReturn(['apply_to' => 'commercial,simple,virtual']);
        $this->eavSetup->expects($this->never())->method('updateAttribute');
        $this->connection->expects($this->once())->method('startSetup');
        $this->connection->expects($this->once())->method('endSetup');
        $this->patch->apply();
    }

    public function testApplySkipsIfApplyToIsEmpty(): void
    {
        $this->eavSetup->method('getAttribute')
            ->willReturn(['apply_to' => '']);
        $this->eavSetup->expects($this->never())->method('updateAttribute');
        $this->connection->expects($this->once())->method('startSetup');
        $this->connection->expects($this->once())->method('endSetup');
        $this->patch->apply();
    }

    public function testGetDependenciesReturnsEmptyArray(): void
    {
        $this->assertSame([], AddCommercialToApplyToAttributesV1::getDependencies());
    }

    public function testGetAliasesReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }
}