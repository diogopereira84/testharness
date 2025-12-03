<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\OrderApprovalB2b\Helper\OrderApprovalHelper;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\OrderApprovalB2b\Helper\DeclineHelper;
use Psr\Log\LoggerInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Magento\Checkout\Model\Session;
use Exception;

/**
 * ApproveOrder Controller
 */
class ApproveOrder implements ActionInterface
{
    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderApprovalHelper $orderApprovalHelper
     * @param SubmitOrderBuilder $submitOrderBuilder
     * @param CartRepositoryInterface $quoteRepository
     * @param JsonFactory $resultJsonFactory
     * @param RevieworderHelper $revieworderHelper
     * @param SubmitOrderModelAPI $submitOrderModelApi
     * @param AdminConfigHelper $adminConfigHelper
     * @param DeclineHelper $declineHelper
     * @param Session $checkoutSession
     */
    public function __construct(
        protected Context $context,
        protected LoggerInterface $logger,
        protected OrderRepositoryInterface $orderRepository,
        protected OrderApprovalHelper $orderApprovalHelper,
        protected SubmitOrderBuilder $submitOrderBuilder,
        protected CartRepositoryInterface $quoteRepository,
        protected JsonFactory $resultJsonFactory,
        protected RevieworderHelper $revieworderHelper,
        protected SubmitOrderModelAPI $submitOrderModelApi,
        protected AdminConfigHelper $adminConfigHelper,
        protected DeclineHelper $declineHelper,
        protected Session $checkoutSession
    )
    {
    }

    /**
     * Order Approve controller
     *
     * @return object ResultJson
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $fedexAccountNumber = '';
        $orderIsPickup = false;
        $quote = null;
        $orderId = $this->context->getRequest()->getPost('order_id');
        $resData = [
            'success' => false,
            'msg' => "System error. Please Try Again"
        ];
        try {
            if (!$this->revieworderHelper->checkIfUserHasReviewOrderPermission()) {
                $resData['msg'] = "You are not authorized to access this request.";

                return $this->revieworderHelper->sendResponseData($resData, $resultJson);
            }

            if (!$orderId) {
                return $this->revieworderHelper->sendResponseData($resData, $resultJson);
            }

            $order = $this->orderRepository->get($orderId);
            if (!$order || $order->getStatus() != "pending_approval") {
                $orderStatus = "approved";
                if ($order->getStatus() == "declined") {
                    $orderStatus = "declined";
                }
                $resData['msg'] = "This order is already ". $orderStatus . ".";

                return $this->revieworderHelper->sendResponseData($resData, $resultJson);
            }
            
            $quoteId = $order->getQuoteId();
            $shippingMethod = $order->getShippingMethod();
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote) {
                return $this->revieworderHelper->sendResponseData($resData, $resultJson);
            }
            $this->checkoutSession->setPendingOrderQuoteId($quoteId);

            if ($shippingMethod == 'fedexshipping_PICKUP') {
                $orderIsPickup = true;
                $requestData = $this->orderApprovalHelper->prepareOrderPickupRequest($order, $quote);
                $errorType = "pickup location";
            } else {
                $fedexAccountNumber = $order->getPayment()->getFedexAccountNumber();
                $requestData = $this->orderApprovalHelper
                ->prepareOrderShippingRequest($fedexAccountNumber, $order);
                $errorType = "delivery method";
            }

            $requestData = json_decode((string) $requestData);

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ' Request data before order approval for Order Id '.
                $order->getIncrementId().' : '. json_encode($requestData)
            );

            $response = $this->submitOrderBuilder->build(
                $requestData,
                $orderIsPickup,
                true,
                $quote
            );
        
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ' Response after order approval for Order Id '.
                $order->getIncrementId().' : '. json_encode($response)
            );

            if (is_array($response) && isset($response[0])) {
                $resData = [
                    'success' => true,
                    'msg' => $this->adminConfigHelper
                    ->getB2bOrderApprovalConfigValue('order_approve_toast_msg') ??
                    'Your order was approved and is now processing.'
                ];
                $this->checkoutSession->unsPendingOrderQuoteId();
            } elseif (!empty($response['error']) || !empty($response['msg'])) {
                $isTimeout = 0;
                if ($response['msg'] == "timeout") {
                   /* In case of time out set timeout as 1 */
                   $isTimeout = 1;
                }
                $errorCode = $response['response']['errors'][0]['code'] ?? '';
                if ($this->orderApprovalHelper->getErrorResponseMsgs($errorCode, true)) {
                    $erroMsg = "This order has been declined because the selected ". $errorType.
                    " is no longer available.";
                    $this->declineHelper->declinedOrder($orderId, $erroMsg);
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ .
                        ' After Order declined with Order Id '.
                        $order->getIncrementId().' due to '. $errorCode
                    );
                }

                if (isset($response['response']) &&
                    isset($response['response']['response'])
                 ) {
                    $responseDecoded = json_decode($response['response']['response'], true);
                    $errorCode = $responseDecoded['errors'][0]['code'] ?? '';
                    if ($errorCode == 'SPOS.TENDER.287') {
                        $resData['msg'] = 'Payment Authorization Failed: Please review your payment details and retry.';
                    }
                }
                $resData = [
                    'success' => false,
                    'msg' => $erroMsg ?? $resData['msg'],
                    'code' => $errorCode
                ];

                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ .
                    ' Before setting timeout '.$isTimeout.' and deactivating quote for Order Id : '.
                    $order->getIncrementId()
                );
                /* deactivation of quote in case of error response
                and set timout flag in case timeout from transaction API*/
                $this->submitOrderModelApi->updateQuoteStatusAndTimeoutFlag($quote, false, $isTimeout);
                
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ .
                    ' After setting timeout '.$isTimeout.' and deactivating quote for Order Id : '.
                    $order->getIncrementId()
                );

                $this->checkoutSession->unsPendingOrderQuoteId();
            }

        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .
                ' Exception occured during order Approval => '. $e->getMessage()
            );
            /* deactivation of order quote in case of exception occured */
            $this->submitOrderModelApi->updateQuoteStatusAndTimeoutFlag($quote, false, 0);
            $this->checkoutSession->unsPendingOrderQuoteId();
            $order = $this->orderRepository->get($orderId);
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ' After unsetting quote id '.
                ($this->checkoutSession->getPendingOrderQuoteId() ?? 'NULL').
                ' from checkout session B2b order Approval for Order Id '.
                $order->getIncrementId()
            );
        }

        return $this->revieworderHelper->sendResponseData($resData, $resultJson);
    }
}
