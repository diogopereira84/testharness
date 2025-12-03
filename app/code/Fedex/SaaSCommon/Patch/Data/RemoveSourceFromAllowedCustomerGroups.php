<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveSourceFromAllowedCustomerGroups implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    ) {}

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            // Remove source model from the attribute by setting source_model to null
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'allowed_customer_groups',
                'source_model',
                null
            );
        } finally {
            $this->moduleDataSetup->getConnection()->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [
            CreateAllowedCustomerGroups::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
