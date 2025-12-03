<?php

namespace Fedex\Ondemand\Observer\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\GridPool;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreRepository;

class BeforeOrderView implements \Magento\Framework\Event\ObserverInterface

{
    /**
     * @param RequestInterface $requestInterface
     * @param StoreFactory $storeFactory
     * @param StoreRepository $storeRepository
     * @param OrderFactory $orderFactory
     * @param GridPool $gridPool
     */

    public function __construct(
        protected RequestInterface $requestInterface,
        protected StoreFactory $storeFactory,
        protected StoreRepository $storeRepository,
        protected OrderFactory $orderFactory,
        protected GridPool $gridPool
    )
    {
    }

    /**
     * @param Magento\Framework\Event\Observer $observer
     * Set Ondemnd Store Id for order where store is deleted on order view
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderId = $this->requestInterface->getParam('order_id');

        $ondemandStoreId = $this->storeFactory->create()->load("ondemand", 'code')->getId();
        $storeLists = $this->storeRepository->getList();

        $availableStores = [];
        foreach ($storeLists as $storeList) {
            $availableStores[] = $storeList->getData('store_id');
        }
        if ($orderId) {
            $order = $this->orderFactory->create()->load($orderId);
            $orderStoreId = $order->getStoreId();
            if (!in_array($orderStoreId, $availableStores)) {
                $order->setStoreId($ondemandStoreId)->save();
                $this->gridPool->refreshByOrderId($orderId);
            }
        }
    }
}
