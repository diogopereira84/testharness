<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;

/**
 * InsertCustomerExternalUserIdAttribute Patch class
 */
class InsertCustomerExternalUserIdAttribute implements DataPatchInterface
{
    /**
     * Insert customer attribute Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CustomerSetupFactory $customerSetupFactory
    )
    {
    }

    /**
     * Creating UUID value and Canva Id customer attribute
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'external_user_id',
            [
                'type' => 'varchar',
                'label' => 'User ID',
                'input' => 'text',
                'source' => '',
                'required' => false,
                'visible' => true,
                'unique' => true,
                'sort_order' => 80,
                'position' => 80,
                'user_defined' => false,
                'system' => false,
                'backend' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_searchable_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => true
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'external_user_id')->addData([
            'used_in_forms' => [
                'adminhtml_customer'
            ]
        ]);
        $attribute->save();

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

