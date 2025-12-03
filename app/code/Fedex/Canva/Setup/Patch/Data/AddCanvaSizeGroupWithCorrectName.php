<?php
declare(strict_types=1);

namespace Fedex\Canva\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Fedex\Canva\Model\Size;

/**
 * @codeCoverageIgnore
 */
class AddCanvaSizeGroupWithCorrectName implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
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
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetupFactoryObj = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $defaultAttrSetId = $eavSetupFactoryObj->getAttributeSetId(Product::ENTITY, 'Default');
        $eavSetupFactoryObj->removeAttributeGroup(Product::ENTITY, $defaultAttrSetId, 'Canva');
        $eavSetupFactoryObj->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, Size::CANVA_SIZE_GROUP_ID);
        $eavSetupFactoryObj->addAttributeGroup(
            Product::ENTITY,
            $defaultAttrSetId,
            Size::CANVA_SIZE_GROUP_NAME,
            13
        );
        $eavSetupFactoryObj->updateAttributeGroup(
            Product::ENTITY,
            $defaultAttrSetId,
            Size::CANVA_SIZE_GROUP_NAME,
            'tab_group_code',
            'basic'
        );
        $eavSetupFactoryObj->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            Size::CANVA_SIZE_GROUP_ID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'label' => Size::CANVA_SIZE_GROUP_NAME,
                'input' => 'text',
                'source' => '',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'sort_order' => '30',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'group' => Size::CANVA_SIZE_GROUP_NAME,
                'used_in_product_listing' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'option' => ['values' => ['']]
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $defaultAttrSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
        $eavSetup->removeAttributeGroup(Product::ENTITY, $defaultAttrSetId, Size::CANVA_SIZE_GROUP_NAME);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, Size::CANVA_SIZE_GROUP_ID);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}
