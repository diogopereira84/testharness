<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Setup\Patch\Data;

use Fedex\MarketplaceCustomer\Helper\Data;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 * Class DeleteSellerConfiguratorUniqueId
 */
class DeleteSellerConfiguratorUniqueId implements DataPatchInterface, PatchRevertableInterface
{
    const SELLER_CONFIGURATOR_UUID = 'seller_configurator_uuid';

    /**
     * Insert customer attribute Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param CollectionFactory $customerCollection
     * @param Config $eavConfig
     * @param Data $data
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CustomerSetupFactory $customerSetupFactory
    ) {}

    /**
     * Creating UUID value and Canva Id customer attribute
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        if($customerSetup->getAttribute(Customer::ENTITY, self::SELLER_CONFIGURATOR_UUID)) {
            $customerSetup->removeAttribute(Customer::ENTITY, self::SELLER_CONFIGURATOR_UUID);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(
            Customer::ENTITY,
            self::SELLER_CONFIGURATOR_UUID
        );
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
