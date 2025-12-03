<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;

class Sales extends \Magento\Framework\App\Action\Action
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
        private \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        private LoggerInterface $logger,
        private Data $jsonHelper,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute view action of Pickup Address
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $tables = ['sales_order', 'sales_order_grid', 'sales_order_item'];
        $b2bStoreCode = "b2b_store";
        $sdeStoreCode = "sde_store";
        $ondemandStoreCode = "ondemand";
        $updateResult = [];
        $b2bStoreIds = [];

        try {
            // get group Obj from store code i.e. "b2b_store"
            $b2bGroupObj = $this->groupFactory->create()->load($b2bStoreCode, 'code');
            $b2bStoreGroupId = $b2bGroupObj->getGroupId();
            $storeIds = $b2bGroupObj->getStoreIds();

            // get group Obj from store code i.e. "sde_store"
            $sdeGroupObj = $this->groupFactory->create()->load($sdeStoreCode, 'code');
            $sdeStoreGroupId = $sdeGroupObj->getGroupId();
            $sdeStoreIds = $sdeGroupObj->getStoreIds();

            // all store ids
            $b2bStoreIds = $storeIds + $sdeStoreIds;
            $b2bStoreIds = implode(",", $b2bStoreIds);

            // get group Obj from store code i.e. "ondemand"
            $ondemandGroupObj = $this->groupFactory->create()->load($ondemandStoreCode, 'code');
            $ondemandStoreIds = $ondemandGroupObj->getStoreIds();
            $ondemandStoreId = reset($ondemandStoreIds);

            // update table
            $this->moduleDataSetup->startSetup();
            foreach ($tables as $key => $table) {

                $tableObj = $this->moduleDataSetup->getTable($table);
                $updatedRecordCount = $this->moduleDataSetup->getConnection()->update(
                    $tableObj,
                    ['store_id' => $ondemandStoreId],
                    'store_id in ('.$b2bStoreIds.')'
                );
                $updateResult[$table] = $updatedRecordCount;
            }

            $updateResult = $this->jsonHelper->jsonEncode($updateResult);
            $this->logger->info('Ondemand controller hit : Sales table updated : '. $updateResult);
            $this->logger->info('Ondemand controller hit : Previous storeIds : '. $b2bStoreIds);
            $this->moduleDataSetup->endSetup();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
