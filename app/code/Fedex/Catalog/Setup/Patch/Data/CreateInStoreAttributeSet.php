<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Setup\Patch\Data;

use Fedex\Canva\Setup\Patch\Data\AddCanvaSizeGroupWithCorrectName;
use Fedex\ProductEngine\Setup\Patch\Data\AddProductEngineAttributes;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class CreateInStoreAttributeSet implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private AttributeSetFactory $attributeSetFactory,
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
        $eavSetup = $this->eavSetupFactory->create();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeSetId = $this->eavSetupFactory->create()->getAttributeSetId(Product::ENTITY, 'FXOPrintProducts');
        $data = [
            'attribute_set_name' => 'FXOInStoreProducts',
            'entity_type_id' => $eavSetup->getEntityTypeId(Product::ENTITY),
            'sort_order' => 3,
        ];
        $attributeSet->setData($data);
        $attributeSet->validate();
        $attributeSet->save();
        $attributeSet->initFromSkeleton($attributeSetId);
        $attributeSet->save();

        $eavSetup->removeAttributeGroup(Product::ENTITY, $attributeSet->getAttributeSetId(), 'gift-options');
        $eavSetup->removeAttributeGroup(Product::ENTITY, $attributeSet->getAttributeSetId(), 'canva-sizes');
        $eavSetup->removeAttributeGroup(Product::ENTITY, $attributeSet->getAttributeSetId(), 'product-engine-attributes');
        $eavSetup->removeAttributeGroup(Product::ENTITY, $attributeSet->getAttributeSetId(), 'mirakl-marketplace');

        $this->createTextInStoreCTAAttributes($eavSetup);
        $this->createSelectInStoreCTAAttributes($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    protected function createTextInStoreCTAAttributes(EavSetup $eavSetup)
    {
        $textAttributes = [
            'order_form' => ['label' => 'Get Order Form Link', 'group' => 'General', 'sort_order'=> '996'],
            'find_location' => ['label' => 'Find a Location Link', 'group' => 'General', 'sort_order'=> '998']
        ];

        foreach ($textAttributes as $attributeCode => $attributeInfo) {

            $attribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            if (!$attribute || !$attribute->getId()) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    [
                        'group' => $attributeInfo['group'],
                        'type' => 'text',
                        'label' => $attributeInfo['label'],
                        'input' => 'text',
                        'class' => '',
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => null,
                        'searchable' => true,
                        'filterable' => false,
                        'comparable' => true,
                        'visible_on_front' => false,
                        'used_in_product_listing' => false,
                        'unique' => false,
                        'is_html_allowed_on_front' => true,
                        'is_wysiwyg_enabled' => false,
                        'is_pagebuilder_enabled' => false,
                        'sort_order' => $attributeInfo['sort_order']
                    ]
                );
            }
        }
    }

    protected function createSelectInStoreCTAAttributes(EavSetup $eavSetup)
    {
        $selectAttributes = [
            'order_form_target' => [
                'label'     => 'Get Order Form Target',
                'group'     => 'General',
                'comment'   => 'Open Get Order Form in a new page',
                'sort_order'=> '997'
            ],
            'find_location_target' => [
                'label' => 'Find a Location Target',
                'group' => 'General',
                'comment'   => 'Open Find a Location in a new page',
                'sort_order'=> '999',
            ]
        ];

        foreach ($selectAttributes as $attributeCode => $attributeInfo) {

            $attribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            if (!$attribute || !$attribute->getId()) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    [
                        'group' => $attributeInfo['group'],
                        'type' => 'int',
                        'label' => $attributeInfo['label'],
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'class' => '',
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => null,
                        'searchable' => true,
                        'filterable' => false,
                        'comparable' => true,
                        'visible_on_front' => false,
                        'used_in_product_listing' => false,
                        'unique' => false,
                        'is_html_allowed_on_front' => true,
                        'is_wysiwyg_enabled' => false,
                        'is_pagebuilder_enabled' => false,
                        'note' => $attributeInfo['comment'],
                        'sort_order' => $attributeInfo['sort_order']
                    ]
                );
            }
        }
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
        return [
            AddProductEngineAttributes::class,
            AddCanvaSizeGroupWithCorrectName::class
        ];
    }
}
