<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SSO\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * UpgradeData class to delete customer attribute
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Init UpgradeData constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    /**
     * Delete customer_fdx_cbid customer attribute
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * 
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create([ 'setup' => $setup ]);
        $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'customer_fdx_cbid');
    }
}
