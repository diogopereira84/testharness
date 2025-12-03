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
class AddCanvaSizeProductAttribute implements DataPatchInterface, PatchRevertableInterface
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
        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $defaultAttrSetId = $eavSetupFactoryObject->getAttributeSetId(Product::ENTITY, 'Default');
        $eavSetupFactoryObject->addAttributeGroup(
            Product::ENTITY,
            $defaultAttrSetId,
            Size::CANVA_SIZE_GROUP_NAME,
            13
        );
        $eavSetupFactoryObject->updateAttributeGroup(
            Product::ENTITY,
            $defaultAttrSetId,
            Size::CANVA_SIZE_GROUP_NAME,
            'tab_group_code',
            'basic'
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
        $eavSetup->removeAttributeGroup(Product::ENTITY, $defaultAttrSetId, 'Canva');
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
        return [];
    }
}
