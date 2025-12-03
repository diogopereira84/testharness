<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DataSourceInterface;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;

class ThirdPartyDeliveryComposite implements DataSourceInterface
{
    /**
     * @param ThirdPartyDeliveryDataSource $thirdPartyDeliveryDataSource
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory $orderItemCollectionFactory
     */
    public function __construct(
        private readonly ThirdPartyDeliveryDataSource $thirdPartyDeliveryDataSource,
        private readonly SearchCriteriaBuilder      $searchCriteriaBuilder,
        private readonly OrderRepositoryInterface   $orderRepository,
        private readonly CollectionFactory          $orderItemCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function map(UnifiedDataLayerInterface $unifiedDataLayer, array $checkoutData = []): void
    {
        $orderNumber = '';

        if (isset($checkoutData[0])) {
            $checkoutData2 = json_decode($checkoutData[0], true);
            if (isset($checkoutData2["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["origin"]["orderNumber"])) {
                $orderNumber = $checkoutData2["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["origin"]["orderNumber"];
            }
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderNumber)->create();
        $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();
        $orderId = $order->getId();

        $orderItemCollection = $this->orderItemCollectionFactory->create();
        $orderItemCollection->addFieldToFilter('order_id', $orderId);
        $orderItemCollection->getSelect()->join(
            ['miraklShopTable' => $orderItemCollection->getTable('mirakl_shop')],
            'main_table.mirakl_shop_id = miraklShopTable.id',
            ['name' => 'miraklShopTable.name']
        );

        foreach ($orderItemCollection->getIterator() as $orderItem) {
            $this->thirdPartyDeliveryDataSource->setShopId($orderItem->getMiraklShopId());
            $this->thirdPartyDeliveryDataSource->map($unifiedDataLayer, $checkoutData);
        }
    }
}
