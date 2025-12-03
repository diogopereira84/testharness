<?php
declare (strict_types = 1);

namespace Fedex\Commercial\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class PageLayoutSearchAttribute implements DataPatchInterface
{

    /**
     * @param ModuleDataSetupInterface
     * @param EavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'page_layout_search',
            [
                'type' => 'text',
                'label' => 'Layout Search',
                'input' => 'textarea',
                'source' => null,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible' => false,
                'required' => false,
                'user_defined' => true,
                'default' => null,
                'group' => 'Design',
                'sort_order' => 90,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => '',
            ]
        );

        $attributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);
            foreach ($attributeSetIds as $attributeSetId) {
                if ($attributeSetId) {
                    $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'Design');
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $attributeSetId,
                        $groupId,
                        'page_layout_search',
                        ''
                    );
                }
            }

        $this->moduleDataSetup->endSetup();

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
        return [];
    }
}
