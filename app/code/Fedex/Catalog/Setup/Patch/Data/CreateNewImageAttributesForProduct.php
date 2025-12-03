<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product\Attribute\Frontend\Image as ImageFrontendModel;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class CreateNewImageAttributesForProduct implements DataPatchInterface
{
    /**
     * CreateNewImageAttributesForProduct constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private Config $eavConfig
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

        /**
         * Create new product_recently_related_image image type for Product Page
         */
        $attributeCode = 'product_recently_related_image';
        $attribute = $this->eavConfig->getAttribute(
            Product::ENTITY,
            $attributeCode
        );

        if (!$attribute || !$attribute->getId()) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                [
                    'type' => 'varchar',
                    'label' => 'Recently Viewed And Related Product',
                    'input' => 'media_image',
                    'frontend' => ImageFrontendModel::class,
                    'required' => false,
                    'visible' => true,
                    'group' => "image-management",
                    'visible_on_front' => false,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'used_for_promo_rules' => false,
                    'used_in_product_listing' => true,
                    'user_defined' => true
                ]
            );

            $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                'image-management',
                $attribute->getId(),
                10
            );
        }

        /**
         * Update existing attributes for used_in_product_listing
         */
        $imageAttributes = ['product_info_image', 'product_ideas_image'];

        foreach ($imageAttributes as $attributeCode) {
            $eavSetup->updateAttribute(
                Product::ENTITY,
                $attributeCode,
                'used_in_product_listing',
                1
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
