<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Customer\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;

/**
 * InsertCustomerAttribute Patch class
 */
class InsertCustomerStatusAttribute implements DataPatchInterface
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
     * customer_status customer attribute
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
            'customer_status',
            [
                'type' => 'varchar',
                'label' => 'Customer Status',
                'input' => 'select',
                'source' => '',
                'required' => false,
                'visible' => true,
                'unique' => false,
                'position' => 20,
                'user_defined' => false,
                'system' => false,
                'backend' => '',
                'source' => \Fedex\Customer\Model\Attribute\Source\CustomOptions::class,
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

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'customer_status')->addData([
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
