<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\OrderApprovalB2b\ViewModel;

use Exception;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\OrderApprovalB2b\Helper\OrderApprovalHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Fedex\Shipment\Model\ProducingAddressFactory;

class OrderApprovalViewModel implements ArgumentInterface
{
    /**
     * AdminConfigHelper Confirmed
     */
    public const CONFIRMED = AdminConfigHelper::CONFIRMED;

    /**
     * AdminConfigHelper Decline
     */
    public const DECLINE = AdminConfigHelper::DECLINE;

    /**
     * Initializing constructor
     *
     * @param AdminConfigHelper $adminConfigHelper
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param OrderApprovalHelper $orderApprovalHelper
     * @param StoreManagerInterface $storeManager
     * @param OrderEmailHelper $orderEmailHelper
     * @param OrderInterface $order
     * @param LoggerInterface $logger
     * @param ProducingAddressFactory $producingAddressFactory
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected OrderApprovalHelper $orderApprovalHelper,
        protected StoreManagerInterface $storeManager,
        protected OrderEmailHelper $orderEmailHelper,
        protected OrderInterface $order,
        protected LoggerInterface $logger,
        protected ProducingAddressFactory $producingAddressFactory
    )
    {
    }

    /**
     * Get order approval B2B  toggle
     *
     * @return boolean
     */
    public function isOrderApprovalB2bEnabled()
    {
        return $this->adminConfigHelper->isOrderApprovalB2bEnabled();
    }

    /**
     * Get B2B Pending Order Config Value
     *
     * @param string $key
     * @return string
     */
    public function getB2bOrderApprovalConfigValue($key)
    {
        return $this->adminConfigHelper->getB2bOrderApprovalConfigValue($key, $this->storeManager->getStore()->getId());
    }

    /**
     * Check quote is priceable or not
     *
     * @return boolean
     */
    public function checkoutQuotePriceisDashable()
    {
        return $this->uploadToQuoteViewModel->checkoutQuotePriceisDashable();
    }

    /**
     * Get pending order approval msg title
     *
     * @return string
     */
    public function getPendingOrderApprovalMsgTitle()
    {
        return 'Pending Approval';
    }

    /**
     * Get pending order approval msg
     *
     * @return string
     */
    public function getPendingOrderApprovalMsg()
    {
        return 'This order will require admin approval before we begin processing.
        The estimated delivery/pickup date and time may vary based on when this order is approved.';
    }

    /**
     * To get the Pending Order Response.
     *
     * @param object $dataObjectForFujistu
     * @param object $paymentData
     * @param object $order
     * @return array
     */
    public function getOrderPendingApproval($dataObjectForFujistu, $paymentData, $order)
    {
        return $this->orderApprovalHelper->buildOrderSuccessResponse($dataObjectForFujistu, $paymentData, $order);
    }

    /**
     * To email send.
     *
     * @param array $orderData
     * @return void
     */
    public function b2bOrderSendEmail($orderData)
    {
        $this->orderEmailHelper->sendOrderGenericEmail($orderData);
    }

    /**
     * Get order object by order IncrementId id.
     *
     * @param string $orderIncrementId
     * @return object
     */
    public function getOrder($orderIncrementId)
    {
        if (!empty($orderIncrementId)) {
            return $this->order->loadByIncrementId($orderIncrementId);
        }

        return null;
    }

    /**
     * Save Estimated Pickup time after order approval
     *
     * @param array $response
     * @param object $order
     * @return void
     */
    public function saveEstimatedPickupTime($response, $order)
    {
        try {
            $deliveryLines =
                $response['response']['output']['rateQuote']['rateQuoteDetails'][0]['deliveryLines'][0]
                ?? [];
            $estimatedDeliveryLocalTime = $deliveryLines['estimatedDeliveryLocalTime'] ?? null;
            if (!empty($estimatedDeliveryLocalTime)) {
                $formattedDateTime = date("l, F j, g:ia", strtotime($estimatedDeliveryLocalTime));
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                "Setting estimatedDeliveryLocalTime: Time Received From Rate API= " . $formattedDateTime);
                $order->setEstimatedPickupTime($formattedDateTime);
                $order->save();
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                 "OrderApproval:estimatedDeliveryLocalTime after order save => " . $order->getEstimatedPickupTime());
                 $formattedOrderCompletionTime = date('Y-m-d\TH:i:s', strtotime($estimatedDeliveryLocalTime));
                 $this->saveDataInOrderProductingTable($order, $formattedOrderCompletionTime);
            }
           
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .
                    ' Exception saving estimatedDeliveryLocalTime after order approval: '
                    . $e->getMessage()
            );
        }
    }

    /**
     * Update order producting table after approve
     *
     * @param object $order
     * @param string $orderCompletionTime
     * @return void
     */
    public function saveDataInOrderProductingTable($order, $orderCompletionTime)
    {
        $orderProducingAddressId = $this->getOrderProducingAddressIdByOrderId($order->getId());
        $this->logger->info(__METHOD__ . ':' . __LINE__ .
        'OrderApproval:Order Producing Address Id: ' . $orderProducingAddressId);
        if ($orderProducingAddressId) {
            $producingAddressModel = $this->producingAddressFactory->create()->load($orderProducingAddressId);
            $addtionalData = $producingAddressModel->getData('additional_data');
            if (!empty($addtionalData)) {
                $additionalDetails = json_decode($addtionalData, true);
                $additionalDetails['estimated_time'] = $orderCompletionTime;
                $updatedAdditionalData = json_encode($additionalDetails);
                $producingAddressModel->setData('additional_data', $updatedAdditionalData);
                $producingAddressModel->save();
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
             'OrderApproval:Order Producing data updated: ' . $producingAddressModel->getData('additional_data'));
        }
    }

    /**
     * Get Order Producing Address Id By Order Id
     *
     * @param int $orderId
     * @return int|null
     */
    public function getOrderProducingAddressIdByOrderId($orderId)
    {
        try {
            $producingAddress = $this->producingAddressFactory->create()->getCollection()
            ->addFieldToFilter('order_id', $orderId)->load();
            if (!empty($producingAddress)) {
                foreach ($producingAddress as $producingAddressData) {
                    $orderProducingAddressId = $producingAddressData->getId();
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $orderProducingAddressId ?? null;
    }
}
