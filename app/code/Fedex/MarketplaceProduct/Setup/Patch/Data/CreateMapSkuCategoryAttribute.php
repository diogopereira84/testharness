<?php
/**
 * @category     Fedex
 * @package      Fedex_FujitsuCore
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class CreateMapSkuCategoryAttribute implements DataPatchInterface
{
    public const MAP_SKU_ATTRIBUTE_CODE = "map_sku";

    /**
     * CreateMapSkuCategoryAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }

    /**
     * Apply patch
     *
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        try {
            $eavSetup->addAttribute(
                Category::ENTITY,
                static::MAP_SKU_ATTRIBUTE_CODE,
                [
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'input' => 'text',
                    'label' => __('Map SKU'),
                    'required' => false,
                    'type' => 'varchar',
                    'visible' => true,
                    'sort_order' => 55,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'searchable' => true,
                    'user_defined' => false,
                    'filterable' => true,
                    'filterable_in_search' => true
                ]
            );
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
