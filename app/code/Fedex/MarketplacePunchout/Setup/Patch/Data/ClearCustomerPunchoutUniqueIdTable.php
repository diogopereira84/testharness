<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Setup\Patch\Data;

use Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueId\CollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 * Class ClearCustomerPunchoutUniqueIdTable
 */
class ClearCustomerPunchoutUniqueIdTable implements DataPatchInterface, PatchRevertableInterface
{
    const SELLER_CONFIGURATOR_UUID = 'seller_configurator_uuid';

    /**
     * Clear customer punchout unique id table constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $customerUniqueIdsCollectionFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CollectionFactory        $customerUniqueIdsCollectionFactory
    ) {
    }

    /**
     * Clearing customer punchout unique id table
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerUniqueIdsCollection = $this->customerUniqueIdsCollectionFactory->create();
        $customerUniqueIdsCollection->walk('delete');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $customerUniqueIdsCollection = $this->customerUniqueIdsCollectionFactory->create();
        $customerUniqueIdsCollection->walk('delete');
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
