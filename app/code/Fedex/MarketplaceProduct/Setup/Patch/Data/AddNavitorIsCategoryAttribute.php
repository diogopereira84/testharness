<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;

class AddNavitorIsCategoryAttribute implements DataPatchInterface, PatchRevertableInterface
{

    private const ATTRIBUTE_CODE = 'navitor_is_category';


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @return AddMiraklImageAttribute|void
     */
    public function apply()
    {
        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetupFactoryObject->removeAttribute(
            Product::ENTITY,
            self::ATTRIBUTE_CODE
        );

        $attributeData = [
            'group'                   => 'Mirakl Marketplace',
            'type'                    => 'int',
            'label'                   => 'Navitor Is Category',
            'input'                   => 'boolean',
            'source'                  => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'sort_order'              => 10,
            'default'                 => null,
            'global'                  => ScopedAttributeInterface::SCOPE_STORE,
            'visible'                 => true,
            'required'                => false,
            'user_defined'            => true,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'visible_on_front'        => false,
            'unique'                  => false,
            'apply_to'                => 'simple',
            'is_configurable'         => false,
            'used_in_product_listing' => true,
            'mirakl_is_exportable'    => true
        ];

        try {
            $eavSetupFactoryObject->addAttribute(
                Product::ENTITY,
                self::ATTRIBUTE_CODE,
                $attributeData
            );
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetupFactoryObject->removeAttribute(
            Product::ENTITY,
            self::ATTRIBUTE_CODE
        );

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
