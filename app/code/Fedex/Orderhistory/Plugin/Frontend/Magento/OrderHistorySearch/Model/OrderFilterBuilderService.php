<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\OrderHistorySearch\Model;

use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Fedex\Orderhistory\Helper\Data;
use DateInterval;

class OrderFilterBuilderService
{
    /**
     * @inheritDoc
     */
    public function __construct(
        protected Data $helper,
        protected TimezoneInterface $localeDate
    )
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundApplyOrderFilters(
        \Magento\OrderHistorySearch\Model\OrderFilterBuilderService $subject,
        callable $proceed,
        \Magento\Sales\Model\ResourceModel\Order\Collection $ordersCollection,
        array $params = []
    ) {
        if ($this->helper->isModuleEnabled() || $this->helper->isRetailOrderHistoryEnabled()) {
            $params = $this->unsetNonRequiredParams($params);
            if (isset($params['order-number'])) {
                $orderNum = $params['order-number'];
                unset($params['order-number']);
                $ordersCollection = $this->filterByOrderNumber($ordersCollection, $orderNum);
            }

            if (isset($params['invoice-number']) && $params['invoice-number']) {
                $poNum = $params['invoice-number'];
                unset($params['invoice-number']);
                $joinConditions = 'main_table.entity_id = sales_order_payment.parent_id';
                $ordersCollection->addAttributeToSelect('*');
                $ordersCollection->getSelect()->join(
                    ['sales_order_payment'],
                    $joinConditions,
                    []
                )->columns('sales_order_payment.po_number');

                $ordersCollection->addFieldToFilter('po_number', ['like' => '%'.$poNum.'%']);
            }
        }

        if (isset($params['order-date'])) {
            $orderDateArray = explode('-', $params['order-date']);
            unset($params['order-date']);
            $ordersCollection = $this->filterByOrderDate($ordersCollection, $orderDateArray);
        }

        // B-1145903 - Show Order History with only shipped, ready for pickup or delivered
		if ($this->helper->isSDEHomepageEnable() && !empty($params['order-status'])
         && $params['order-status'] == 'shipped;ready_for_pickup;complete') {

			$ordersCollection->addFieldToFilter('status', ['in' => ['ready_for_pickup', 'shipped', 'complete']]);
			unset($params['order-status']);
		}

        // B-1213999 - "View Order" for completed should redirect to
        // Order History with only shipped, ready for pickup, or delivered
        if ($this->helper->isEProHomepageEnable() && !empty($params['order-status'])
         && $params['order-status'] == 'shipped;ready_for_pickup;complete'
        ) {
            $ordersCollection->addFieldToFilter(
                'status',
                [
                    'in' => [
                        'ready_for_pickup',
                        'shipped', 'complete'
                    ]
                ]
            );
            unset($params['order-status']);
        }

        return $proceed($ordersCollection, $params);
    }

    public function unsetNonRequiredParams($params)
    {
        if (isset($params['orderby'])) {
            unset($params['orderby']);
        }

        if (isset($params['sortby'])) {
            unset($params['sortby']);
        }

        return $params;
    }

    public function filterByOrderNumber($ordersCollection, $orderNum)
    {
        if ($orderNum) {
            $ordersCollection->addFieldToFilter(
                ['main_table.ext_order_id','main_table.increment_id'],
                [
                    ['like' => '%' . $orderNum . '%'],
                    ['like' => '%' . $orderNum . '%']
                ]
            );
        }

        return $ordersCollection;
    }

    public function filterByOrderDate($ordersCollection, $orderDateArray)
    {
        $orderFrom = isset($orderDateArray[0]) ? trim($orderDateArray[0]) :null;
        $orderTo = isset($orderDateArray[1]) ? trim($orderDateArray[1]) : null;

        if ($orderFrom) {
            $date = $this->localeDate->date($orderFrom);
            $utcTimestamp = $date->format(DateTime::DATETIME_PHP_FORMAT);
            $ordersCollection->addFieldToFilter('main_table.'.OrderInterface::CREATED_AT, ['gteq' => $utcTimestamp]);
        }

        if ($orderTo) {
            $date = $this->localeDate->date($orderTo);
            $nextDayUtcTimestamp = $date->add(new DateInterval('P1D'))->format(DateTime::DATETIME_PHP_FORMAT);
            $ordersCollection->addFieldToFilter(
                'main_table.'.OrderInterface::CREATED_AT,
                ['lt' => $nextDayUtcTimestamp]
            );
        }

        return $ordersCollection;
    }
}
