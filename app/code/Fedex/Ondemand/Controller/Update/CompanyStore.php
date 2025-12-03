<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreFactory;

class CompanyStore extends \Magento\Framework\App\Action\Action
{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Store\Model\GroupFactory $groupFactory
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     * @param ManagerInterface $eventManager
     * @param StoreFactory $storeFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private \Magento\Store\Model\GroupFactory $groupFactory,
        private \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        private LoggerInterface $logger,
        private ManagerInterface $eventManager,
        private StoreFactory $storeFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute view action of Pickup Address
     *
     * @return void|boolean
     */
    public function execute()
    {
        $store = $this->storeFactory->create();
        $store->load('ondemand');
        $this->eventManager->dispatch('store_add', ['store' => $store]);

        $companyAdditionalDataTable = 'company_additional_data';
        $ondemandStoreCode = "ondemand";

        $group = $this->groupFactory->create();
        $group = $group->load($ondemandStoreCode, 'code');

        if ($group && $group->getId() > 0 && $group->getDefaultStoreId() > 0) {
            $ondemandGroupId = $group->getId();
            $ondemandStoreId = $group->getDefaultStoreId();
            $tableObj = $this->moduleDataSetup->getTable($companyAdditionalDataTable);
            try {
                $this->moduleDataSetup->getConnection()->update(
                    $tableObj,
                    ['new_store_id' => $ondemandGroupId, 'new_store_view_id' => $ondemandStoreId,
                    'store_id' => null, 'store_view_id' => null]
                );
                $this->moduleDataSetup->endSetup();

            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }
        }


    }
}
