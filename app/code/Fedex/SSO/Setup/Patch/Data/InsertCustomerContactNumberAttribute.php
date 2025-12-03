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
 * InsertCustomerContactNumberAttribute Patch class
 */
class InsertCustomerContactNumberAttribute implements DataPatchInterface
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
     * Creating FCL Profile Contact Number
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
            'fcl_profile_contact_number',
            [
                'type' => 'varchar',
                'label' => 'FCL Profile Contact Number',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'unique' => false,
                'position' => 500,
                'user_defined' => false,
                'system' => false,
                'backend' => '',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'is_used_in_grid' => true,
                'apply_to' => 'simple,grouped,bundle,configurable,virtual',
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'visible' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => true
            
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'fcl_profile_contact_number')->addData([
            'used_in_forms' => [
                'adminhtml_customer'
            ]
        ]);
        $attribute->save();
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
