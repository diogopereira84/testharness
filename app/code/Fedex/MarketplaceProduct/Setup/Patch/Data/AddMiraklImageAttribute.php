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

class AddMiraklImageAttribute implements DataPatchInterface, PatchRevertableInterface
{

    const MIRAKL_IMAGE_ATTRIBUTES = ['mirakl_image_1','mirakl_image_2','mirakl_image_3','mirakl_image_4','mirakl_image_5'];


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

        foreach(self::MIRAKL_IMAGE_ATTRIBUTES as $image_code){
            $eavSetupFactoryObject->removeAttribute(
                Product::ENTITY,
                $image_code
            );
        }

        $attributeData = [
            'group'                   => 'Mirakl Marketplace',
            'type'                    => 'varchar',
            'label'                   => 'Mirakl Image',
            'input'                   => 'text',
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
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


        foreach(self::MIRAKL_IMAGE_ATTRIBUTES as $image_code){
            try {
                $eavSetupFactoryObject->addAttribute(
                    Product::ENTITY,
                    $image_code,
                    $attributeData
                );
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }

        }

    }

    /**
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach(self::MIRAKL_IMAGE_ATTRIBUTES as $image_code){
            $eavSetupFactoryObject->removeAttribute(
                Product::ENTITY,
                $image_code
            );
        }

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
