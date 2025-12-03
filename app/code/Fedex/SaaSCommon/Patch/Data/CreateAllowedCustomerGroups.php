<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Patch\Data;

use Fedex\SaaSCommon\Model\Entity\Attribute\Source\CustomerGroupsOptions;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateAllowedCustomerGroups implements DataPatchInterface
{
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected EavSetupFactory $eavSetupFactory,
        protected CustomerGroupsOptions $customerGroupsOptions
    ) {}

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'allowed_customer_groups',
            [
                'type' => 'text',
                'label' => 'Allowed Customer Groups',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '15',
                'source' => CustomerGroupsOptions::class,
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
                'mirakl_is_exportable' => false,
                'option' => [
                    'values' => $this->getCustomerGroupIds(),
                ],
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    protected function getCustomerGroupIds(): array
    {
        $values = [];
        $customerGroups = $this->customerGroupsOptions->getAllOptions();
        foreach ($customerGroups as $customerGroup) {
            $values[] = (string) $customerGroup['value'];
        }
        return $values;
    }
}
