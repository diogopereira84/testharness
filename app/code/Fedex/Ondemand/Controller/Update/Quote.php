<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;

class Quote extends \Magento\Framework\App\Action\Action
{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Store\Model\GroupFactory $groupFactory
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private \Magento\Store\Model\GroupFactory $groupFactory,
        private \Magento\Store\Model\StoreFactory $storeFactory,
        private \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        private LoggerInterface $logger,
        private Data $jsonHelper,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute view action of Pickup Address
     *
     * @return null
     */
    public function execute()
    {
        $tables = ['negotiable_quote_grid', 'quote', 'quote_address_item', 'quote_item', 'quote_integration'];
        $b2bStoreCode = "b2b_store";
        $sdeStoreCode = "sde_store";
        $ondemandStoreCode = "ondemand";
        $updateResult = [];
        $b2bStoreIds = [];

        try {

            $retailStoreId = $this->storeFactory->create()->load("default", 'code')->getId();
            $insStoreId = $this->storeFactory->create()->load("IN_STORE_RETAIL_STORE_VIEW", 'code')->getId();
            $ondemandStoreId = $this->storeFactory->create()->load("ondemand", 'code')->getId();
            // update table
            $this->moduleDataSetup->startSetup();
            foreach ($tables as $table) {

                $tableObj = $this->moduleDataSetup->getTable($table);
                $updatedRecordCount = $this->moduleDataSetup->getConnection()->update(
                    $tableObj,
                    ['store_id' => $ondemandStoreId],
                    "store_id not in ('0','$retailStoreId','$insStoreId','$ondemandStoreId')"
                );
                $updateResult[$table] = $updatedRecordCount;
            }

            $updateResult = $this->jsonHelper->jsonEncode($updateResult);
            $this->logger->info('Ondemand controller hit : Quote table updated : '. $updateResult);
            $this->moduleDataSetup->endSetup();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
