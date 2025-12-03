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
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Fedex\ProductEngine\Setup\AddOptionToAttribute;

class Update3pCostAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * New Attributes.
     */
    private const MIRAKL_ATTRIBUTES = [
        'unit_cost',
        'base_quantity',
        'base_price'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     * @param AddOptionToAttribute $addOptionToAttribute
     * @param CollectionFactory $attributeFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger,
        private AddOptionToAttribute $addOptionToAttribute,
        private CollectionFactory $attributeFactory
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

        try {
            $this->addUnitCost($eavSetupFactoryObject);
            $this->addBaseQuantity($eavSetupFactoryObject);
            $this->addBasePrice($eavSetupFactoryObject);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Add unit cost attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addUnitCost(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->updateAttribute(
            Product::ENTITY,
            'unit_cost',
            'mirakl_is_exportable',
            false
        );
    }

    /**
     * Add base quantity attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addBaseQuantity(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->updateAttribute(
            Product::ENTITY,
            'base_quantity',
            'mirakl_is_exportable',
            false
        );
    }

    /**
     * Add base price attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addBasePrice(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->updateAttribute(
            Product::ENTITY,
            'base_price',
            'mirakl_is_exportable',
            false
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
        foreach (self::MIRAKL_ATTRIBUTES as $attribute) {
            $eavSetupFactoryObject->updateAttribute(
                Product::ENTITY,
                $attribute,
                'mirakl_is_exportable',
                true
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
