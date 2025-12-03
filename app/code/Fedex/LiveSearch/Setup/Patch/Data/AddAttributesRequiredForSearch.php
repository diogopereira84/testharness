<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddAttributesRequiredForSearch implements DataPatchInterface
{
    private const ATTRIBUTE_SET_GROUP = 'Autosettings';
    private const UPLOAD_FILE_ATTRIBUTE_SET_NAME = 'FXOPrintProducts';
    private const CUSTOMIZE_ATTRIBUTE_SET_NAME = 'PrintOnDemand';
    private const UPLOAD_FILE_ATTRIBUTE_NAME = 'upload_file_search_action';
    private const CUSTOMIZE_ATTRIBUTE_NAME = 'customize_search_action';

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly ProductRepository $productRepository,
        private readonly ProductResourceModel $productResourceModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetupFactoryObj = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $uploadAFileSetId = $eavSetupFactoryObj->getAttributeSetId(Product::ENTITY, self::UPLOAD_FILE_ATTRIBUTE_SET_NAME);
        $customizeSetId = $eavSetupFactoryObj->getAttributeSetId(Product::ENTITY, self::CUSTOMIZE_ATTRIBUTE_SET_NAME);
        $eavSetupFactoryObj->addAttribute(
            Product::ENTITY,
            self::UPLOAD_FILE_ATTRIBUTE_NAME,
            [
                'type' => 'int',
                'label' => 'Upload File Search Action',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => false,
                'sort_order' => 90,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => '1',
                'user_defined' => true,
                'searchable' => false,
                'visible_in_advanced_search' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => 'simple,grouped,bundle,configurable,virtual',
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
            ]
        );
        $eavSetupFactoryObj->addAttributeToGroup(
            Product::ENTITY,
            self::UPLOAD_FILE_ATTRIBUTE_SET_NAME,
            self::ATTRIBUTE_SET_GROUP,
            self::UPLOAD_FILE_ATTRIBUTE_NAME
        );

        $products = $this->moduleDataSetup->getConnection()->fetchAll(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('catalog_product_entity'),
                    ['entity_id', 'attribute_set_id']
                )
                ->where('attribute_set_id = ?', $uploadAFileSetId)
        );

        foreach ($products as $product) {
            $productObj = $this->productRepository->getById($product['entity_id']);
            $productObj->setStoreId(0);
            $productObj->setData(self::UPLOAD_FILE_ATTRIBUTE_NAME, true);
            $this->productResourceModel->saveAttribute($productObj, self::UPLOAD_FILE_ATTRIBUTE_NAME);
        }

        $eavSetupFactoryObj->addAttribute(
            Product::ENTITY,
            self::CUSTOMIZE_ATTRIBUTE_NAME,
            [
                'type' => 'int',
                'label' => 'Customize Search Action',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => false,
                'sort_order' => 10,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'Search Engine Optimization',
                'default' => '1',
                'user_defined' => true,
                'searchable' => false,
                'visible_in_advanced_search' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => 'simple,grouped,bundle,configurable,virtual',
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
            ]
        );
        $eavSetupFactoryObj->addAttributeToGroup(
            Product::ENTITY,
            self::CUSTOMIZE_ATTRIBUTE_SET_NAME,
            self::ATTRIBUTE_SET_GROUP,
            self::CUSTOMIZE_ATTRIBUTE_NAME
        );

        $products = $this->moduleDataSetup->getConnection()->fetchAll(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('catalog_product_entity'),
                    ['entity_id', 'attribute_set_id']
                )
                ->where('attribute_set_id = ?', $customizeSetId)
        );

        foreach ($products as $product) {
            $productObj = $this->productRepository->getById($product['entity_id']);
            $productObj->setStoreId(0);
            $productObj->setData(self::CUSTOMIZE_ATTRIBUTE_NAME, true);
            $this->productResourceModel->saveAttribute($productObj, self::CUSTOMIZE_ATTRIBUTE_NAME);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

    }
}
