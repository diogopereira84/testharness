<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;

/**
 * Class used to send reminder email to admin about order about to expire
 */
class OrderSendReminderEmail
{
    /**
     * OrderSendReminderEmail constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     * @param OrderEmailHelper $orderEmailHelper
     */
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected FilterBuilder $filterBuilder,
        protected TimezoneInterface $timezone,
        protected LoggerInterface $logger,
        protected OrderEmailHelper $orderEmailHelper
    )
    {
    }

    /**
     * Cron execute method
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->declineOldOrders();
            $this->sendReminderEmails();
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ': Error while ruuning cron for reminder email ' . $e->getMessage()
            );
        }
    }

    /**
     * Used to decline orders
     */
    public function declineOldOrders()
    {
            $date = $this->timezone->date()->sub(new \DateInterval('P31D'))->format('Y-m-d H:i:s');
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'DeclineOldOrders date:' . $date
            );
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('created_at')
                    ->setConditionType('lt')
                        ->setValue($date)
                        ->create(),
                ]
            );
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('status')
                    ->setConditionType('eq')
                        ->setValue('pending_approval')
                        ->create(),
                ]
            );
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        foreach ($orders as $order) {
            $order->setState("declined");
            $order->setStatus("declined");
            $this->orderRepository->save($order);
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ': Order status changed to declined for order id:' . $order->getEntityId()
            );
        }
    }
   
    /**
     * Used to send email to admins
     */
    public function sendReminderEmails()
    {
            $date = $this->timezone->date()->sub(new \DateInterval('P26D'))->format('Y-m-d');
            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'SendReminderEmails date:' . $date
            );
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('created_at')
                    ->setConditionType('gteq')
                        ->setValue($startDate)
                        ->create(),
                ]
            );
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('created_at')
                    ->setConditionType('lteq')
                        ->setValue($endDate)
                        ->create(),
                ]
            );
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('status')
                    ->setConditionType('eq')
                        ->setValue('pending_approval')
                        ->create(),
                ]
            );
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        foreach ($orders as $order) {
            $orderData=[
                'order_id' => $order->getEntityId(),
                'status' => 'expired',
            ];
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ': Order approve reminder email sent for :' . $order->getEntityId()
            );
            $this->orderEmailHelper->sendOrderGenericEmail($orderData);
        }
    }
}
