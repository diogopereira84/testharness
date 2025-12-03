<?php
declare(strict_types=1);

namespace Fedex\PageBuilderExtensionProducts\Setup\Patch\Data;

use Fedex\Canva\Model\Size;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class AddThirdPartyContentBlocks
 * @package Fedex\PageBuilderBlocks\Setup\Patch\Data
 */
class AddShopByTypeImageRole implements DataPatchInterface, PatchRevertableInterface
{
    const SHOP_BY_TYPE_IMAGE_MEDIA_ROLE = 'shop_by_type_image';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::SHOP_BY_TYPE_IMAGE_MEDIA_ROLE,
            [
                'type' => Table::TYPE_TEXT,
                'label' => 'Shop By Type',
                'input' => 'media_image',
                'required' => false,
                'sort_order' => 30,
                'frontend' => \Magento\Catalog\Model\Product\Attribute\Frontend\Image::class,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'used_in_product_listing' => true,
                'user_defined' => true,
                'visible' => true,
                'visible_on_front' => true
            ]
        );

        $shopByTypeRoleId = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            self::SHOP_BY_TYPE_IMAGE_MEDIA_ROLE
        );

        $allAttributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);
        foreach ($allAttributeSetIds as $attributeSetId) {
            $eavSetup->addAttributeToGroup(\Magento\Catalog\Model\Product::ENTITY, $attributeSetId, 'image-management', $shopByTypeRoleId, 10);
        }

        $this->moduleDataSetup->endSetup();
    }
    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
    public function revert()
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, self::SHOP_BY_TYPE_IMAGE_MEDIA_ROLE);

        $this->moduleDataSetup->endSetup();
    }
    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

}
