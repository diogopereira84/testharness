<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * ManageUserPermission class
 */
class ManageUserPermission implements DataPatchInterface
{
    /**
     * ManageUserPermission constructor
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
     * Create customer attribute
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributes = [
            'manage_user_email_approval' => 'Manage User Email Approval',
            'manage_catalog_permission' => 'Manage Catalog',
            'credit_cards_permission' => 'Credit Cards',
            'manage_user_permission' => 'Manage User',
            'shared_order_permission' => 'Shared Orders',
        ];

        foreach ($attributes as $code => $label) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                $code,
                [
                    'type' => 'int',
                    'label' => $label,
                    'input' => 'boolean',
                    'source' => '',
                    'required' => false,
                    'default' => '0',
                    'visible' => true,
                    'user_defined' => true,
                    'system' => false,
                    'position' => 108,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group' => 'General',
                ]
            );

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $code);
            $attribute->setData('used_in_forms', ['adminhtml_customer']);
            $attribute->save();
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {{inheritdoc}}
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {{inheritdoc}}
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}