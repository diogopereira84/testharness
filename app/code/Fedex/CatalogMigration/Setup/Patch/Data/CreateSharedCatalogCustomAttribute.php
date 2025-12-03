<?php
declare (strict_types=1);

namespace Fedex\CatalogMigration\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Fedex\CatalogMigration\Model\Entity\Attribute\Source\SharedCatalogOptions;

class CreateSharedCatalogCustomAttribute implements DataPatchInterface
{

    /**
     * @param ModuleDataSetupInterface
     * @param EavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory,
        private SharedCatalogOptions $sharedCatalogOptions
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(
            ['setup' => $this->moduleDataSetup]
        );

        $attributeCode = 'shared_catalogs';

        $eavSetup->addAttribute(
            Product::ENTITY,
            $attributeCode,
            [
                'type' => 'text',
                'label' => 'Shared Catalogs',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '15',
                'source' => SharedCatalogOptions::class,
                'backend' => ArrayBackend::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => false,
                'visible' => false,
                'user_defined' => true,
                'searchable' => true,
                'visible_in_advanced_search' => false,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'group' => 'General',
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'filterable_in_search' => true,
                'option' => [
                    'values' => $this->getSharedCatalogIds(),
                ],
            ]
        );

        $attributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);
        foreach ($attributeSetIds as $attributeSetId) {
            if ($attributeSetId) {
                $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'General');
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    $attributeSetId,
                    $groupId,
                    $attributeCode,
                    ''
                );
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    protected function getSharedCatalogIds(): array
    {
        $values = [];
        $sharedCatalog = $this->sharedCatalogOptions->getAllOptions();
        foreach ($sharedCatalog as $catalog) {
            $values[] = (string) $catalog['value'];
        }
        return $values;
    }
}