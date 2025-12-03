<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\OrderHistorySearch\Model\Order\Status;

use Fedex\Orderhistory\Helper\Data;

/**
 * Class DataProvider.
 *
 * Options data provider for order statuses.
 */
class DataProvider
{
    /**
     * DataProvider constructor.
     *
     * @param Data $orderHistoryDataHelper
     */
    public function __construct(
        private Data $orderHistoryDataHelper
    )
    {
    }

    /**
     * Get order statuses options array.
     *
     * @param \Magento\OrderHistorySearch\Model\Order\Status\DataProvider $subject
     * @param array $result
     * @param bool $onlyVisibleOnStorefront
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetOrderStatusOptions(
        \Magento\OrderHistorySearch\Model\Order\Status\DataProvider $subject,
        $result
    ) {
        if ($this->orderHistoryDataHelper->isRetailOrderHistoryEnabled()) {
            $retailOrderStatus = [];
            $retailOrderStatusArray = [
                'new',
                'confirmed',
                'canceled',
                'ready_for_pickup',
                'complete',
                'shipped'
            ];
            foreach ($result as $key => $val) {
                if (in_array($val['value'], $retailOrderStatusArray)) {
                    $retailOrderStatus[] = ['value' => $val['value'], 'label' => $val['label']];
                }
            }
            return $retailOrderStatus;
        }
        if ($this->orderHistoryDataHelper->isCommercialCustomer()) {
            $finalStatus = [];
            foreach ($result as $status) {
                $finalStatus[$status['label']] =[
                    'value' => $status['value'],
                    'label' => $status['label'],
                ];
            }
            if (is_array($finalStatus) && array_key_exists('Processing', $finalStatus) ) {
                $finalStatus['Processing']['value'] = 'confirmed';
            }

            return array_values($finalStatus);
        }

        return $result;
    }
}
