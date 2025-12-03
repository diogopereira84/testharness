<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\GroupRepository;
use Magento\Store\Model\StoreRepository;
use Magento\Framework\Registry;

class RemoveStore extends \Magento\Framework\App\Action\Action

{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param StoreRepository $storeRepository
     * @param GroupRepository $groupRepository
     * @param GroupFactory $groupFactory
     * @param Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private StoreRepository $storeRepository,
        private GroupRepository $groupRepository,
        private GroupFactory $groupFactory,
        private Registry $registry
    ) {
        parent::__construct($context);
    }
    public function execute()
    {
        if ($this->registry->registry('isSecureArea') === null) {
            $this->registry->register('isSecureArea', true);
        }

        $b2bStoreCode = "b2b_store";
        $sdeStoreCode = "sde_store";
        $b2bGroupObj = $this->groupFactory->create()->load($b2bStoreCode, 'code');
        $b2bGroupId = $b2bGroupObj->getId();
        $b2bStoreIds = $b2bGroupObj->getStoreIds();

        // get group Obj from store code i.e. "sde_store"
        $sdeGroupObj = $this->groupFactory->create()->load($sdeStoreCode, 'code');
        $sdeGroupId = $sdeGroupObj->getId();
        $sdeStoreIds = $sdeGroupObj->getStoreIds();
        // all store ids
        $removableStoreIds = $b2bStoreIds + $sdeStoreIds;
        if (!empty($removableStoreIds)) {
            foreach ($removableStoreIds as $removableStoreId) {
                $store = $this->storeRepository->get($removableStoreId);
                $store->delete();
            }
        }

        //Delete Group
        $group = $this->groupRepository->get($b2bGroupId);
        $group->delete();

        $group = $this->groupRepository->get($sdeGroupId);
        $group->delete();
    }
}
