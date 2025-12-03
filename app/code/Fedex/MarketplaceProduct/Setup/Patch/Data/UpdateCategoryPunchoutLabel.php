<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceProduct
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateCategoryPunchoutLabel implements DataPatchInterface
{
    private const CATEGORY_PUNCHOUT_ATTRIBUTE = 'navitor_is_category';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory,
        private Config                   $eavConfig
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Re-create new unit of measure attribute
        $attribute = $this->eavConfig->getAttribute(
            Product::ENTITY,
            self::CATEGORY_PUNCHOUT_ATTRIBUTE
        );

        if ($attribute && $attribute->getId()) {
            $eavSetup->updateAttribute(
                Product::ENTITY,
                self::CATEGORY_PUNCHOUT_ATTRIBUTE,
                'frontend_label',
                'Category Punchout'
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
