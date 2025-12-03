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

class AddCtaButtonAttribute implements DataPatchInterface, PatchRevertableInterface
{

    private const CTA_VALUE_ATTRIBUTE = 'cta_value';


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
            self::CTA_VALUE_ATTRIBUTE
        );

        $attributeData = [
            'group'                   => 'Mirakl Marketplace',
            'type'                    => 'varchar',
            'label'                   => 'CTA Button Value',
            'input'                   => 'text',
            'sort_order'              => 0,
            'default'                 => 'Explore Options',
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
            'used_in_product_listing' => false,
        ];


        try {
            $eavSetupFactoryObject->addAttribute(
                Product::ENTITY,
                self::CTA_VALUE_ATTRIBUTE,
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
            self::CTA_VALUE_ATTRIBUTE
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
