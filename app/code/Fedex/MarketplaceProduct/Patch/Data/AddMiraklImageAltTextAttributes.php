<?php
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;

class AddMiraklImageAltTextAttributes implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected EavSetupFactory          $eavSetupFactory,
        private LoggerInterface            $logger
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        for ($i = 1; $i <= 10; $i++) {
            $attributeCode = 'alt_text_mirakl_image_' . $i;
            $label = 'Mirakl Image ' . $i . ' Alt Text';

            $attributeData = [
                'group' => 'Mirakl Marketplace',
                'type' => 'varchar',
                'label' => $label,
                'input' => 'text',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'is_configurable' => false,
                'used_in_product_listing' => true,
                'mirakl_is_exportable' => true,
                'default' => ''
            ];

            try {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    $attributeData
                );
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
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
        return [];
    }
}
