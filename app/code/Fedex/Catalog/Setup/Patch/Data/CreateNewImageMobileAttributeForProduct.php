<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Iago Lima <iago.lima.osv@fedex.com>
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

class CreateNewImageMobileAttributeForProduct implements DataPatchInterface
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
        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
        // Define image type attributes to create
        $imageAttributes = [
            'product_info_image_mobile' => ['label' => 'Product Info Mobile'],
            'product_ideas_image_mobile' => ['label' => 'Product Ideas Mobile']
        ];

        foreach ($imageAttributes as $attributeCode => $attributeInfo) {
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
                        'label' => $attributeInfo['label'],
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

                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    $attributeSetId,
                    'image-management',
                    $attribute->getId(),
                    10
                );
            }
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
        return [CreateNewImageAttributesForProduct::class];
    }
}
