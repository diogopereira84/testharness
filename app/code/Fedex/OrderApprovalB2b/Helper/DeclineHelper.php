<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Exception;

/**
 * DeclineHelper Helper class
 */
class DeclineHelper extends AbstractHelper
{
    /**
     * Initializing constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param AdminConfigHelper $adminConfigHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param SubmitOrderModelAPI $submitOrderModelApi
     */
    public function __construct(
        Context $context,
        private LoggerInterface $logger,
        protected OrderRepositoryInterface $orderRepository,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        protected AdminConfigHelper $adminConfigHelper,
        protected CartRepositoryInterface $quoteRepository,
        protected SubmitOrderModelAPI $submitOrderModelApi
    ) {
        parent::__construct($context);
    }

    /**
     * Decline order
     *
     * @param string $orderId
     * @param string $additionalComment
     * @return array[]
     */
    public function declinedOrder($orderId, $additionalComment = '')
    {
        $resData = [
            'success' => false,
            'msg' => 'System error. Please Try Again.'
        ];
        try {
            $order = $this->orderRepository->get($orderId);
            if ($order && $order->getStatus() == "pending_approval") {
                $order->setStatus("declined");
                if ($this->adminConfigHelper->isB2bDeclineReorderEnabled()) {
                    $order->setReorderable(1);
                }
                if (!empty($additionalComment)) {
                    $order->addStatusHistoryComment($additionalComment);
                    $this->logger->info(
                        __METHOD__.':'.__LINE__.
                        ' Decline comment ('.$additionalComment.') added for b2b order '
                        . $order->getIncrementId()
                    );
                }
                $order->save();
                $this->logger->info(
                    __METHOD__.':'.__LINE__.
                    ' Order Declined for b2b order '. $order->getIncrementId()
                );
                $resData = [
                    'success' => true,
                    'msg' => $this->adminConfigHelper->getB2bOrderApprovalConfigValue('order_decline_toast_msg')
                ];
                $orderData = [
                    'order_id' => $orderId,
                    'status' => OrderApprovalViewModel::DECLINE,
                    'decline_message' => $additionalComment,
                ];
                $this->orderApprovalViewModel->b2bOrderSendEmail($orderData);
                $this->deactivateOrderQuote($order);
            } else {
                $orderStatus = "approved";
                if ($order->getStatus() == "declined") {
                    $orderStatus = "declined";
                }
                $resData = [
                    'success' => false,
                    'msg' => "This order is already ". $orderStatus . "."
                ];
            }
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__.':'.__LINE__.
                ' Exception during declining order => '. $e->getMessage()
            );
            $order = $this->orderRepository->get($orderId);
            $this->deactivateOrderQuote($order);
        }

        return $resData;
    }

    /**
     * To deactivate the quote in case order decline.
     *
     * @param object $order
     * @return void
     */
    public function deactivateOrderQuote($order)
    {
        $quoteId = $order->getQuoteId();
        $quote = $this->quoteRepository->get($quoteId);
        /* deactivation of quote for declined order */
        $this->submitOrderModelApi->updateQuoteStatusAndTimeoutFlag($quote, false, 0);
        $this->logger->info(
            __METHOD__.':'.__LINE__.
            ' Quote '.$quote->getId().' deactivated for declined order id '. $order->getIncrementId()
        );
    }
}
