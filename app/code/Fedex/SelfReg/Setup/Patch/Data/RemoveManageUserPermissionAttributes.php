<?php
declare(strict_types=1);

namespace Fedex\SelfReg\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveManageUserPermissionAttributes implements DataPatchInterface
{
    /**
     * RemoveCustomerAttributes constructor.
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
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // List of attribute codes to remove
        $attributesToRemove = [
            'manage_user_email_approval',
            'manage_catalog_permission',
            'credit_cards_permission',
            'manage_user_permission',
            'shared_order_permission'
        ];

        // Remove attributes
        foreach ($attributesToRemove as $attributeCode) {
            if ($customerSetup->getAttribute(Customer::ENTITY, $attributeCode)) {
                $customerSetup->removeAttribute(Customer::ENTITY, $attributeCode);
            }
        }

        $this->moduleDataSetup->endSetup();
    }
}