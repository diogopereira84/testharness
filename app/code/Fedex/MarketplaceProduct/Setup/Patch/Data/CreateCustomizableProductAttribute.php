<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

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

class CreateCustomizableProductAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * New Attributes.
     */
    private const ATTRIBUTES = [
        'customizable_product'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     * @param AddOptionToAttribute $addOptionToAttribute
     */
    public function __construct(
        private readonly ModuleDataSetupInterface   $moduleDataSetup,
        private readonly EavSetupFactory            $eavSetupFactory,
        private readonly LoggerInterface            $logger,
        private readonly AddOptionToAttribute       $addOptionToAttribute
    ) {}

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (self::ATTRIBUTES as $code) {
            $eavSetupFactoryObject->removeAttribute(
                Product::ENTITY,
                $code
            );
        }

        try {
            $this->addCustomizableProduct($eavSetupFactoryObject);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Add Customizable Product attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addCustomizableProduct(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'customizable_product',
            [
                'type' => 'int',
                'label' => 'Customizable Product',
                'input' => 'boolean',
                'group' => 'Mirakl Marketplace',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => 1,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => '',
                'filterable_in_search' => true,
                'mirakl_is_exportable'    => false
            ]
        );
    }

    /**
     * Revert
     *
     * @return void
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach (self::ATTRIBUTES as $attributes) {
            $eavSetupFactoryObject->removeAttribute(
                Product::ENTITY,
                $attributes
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
