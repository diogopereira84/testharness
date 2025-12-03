<?php

/**
 * Copyright Infogain All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;

class RetryShipmentCreationListener
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SubmitOrderHelper $submitOrderHelper
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected SubmitOrderHelper $submitOrderHelper,
        private QuoteFactory $quoteFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Use to process data from RabbitMq queue
     * If shipment failed do 5 times max retry
     * @param string $messageRequest [$orderId, $counter 0 - 4]
     * @return boolean
     */
    public function retryShipmentCreation($messageRequest)
    {
        try {
            $requestData = json_decode((string)$messageRequest, true);
            $orderId = $requestData['orderId'];
            $counter = $requestData['counter'];

            $counter++;
            $order = $this->orderRepository->get($orderId);
            $quoteId = $order->getQuoteId();
            $quote = $this->quoteFactory->create()->load($quoteId);

            // Call Create Shipment
            $this->logger->info(__METHOD__.':'.__LINE__.
                ' Retrying shipment creation for order id:' . $orderId .' with attempt:' .$counter);
            $shipmentCreated = $this->submitOrderHelper->createShipment($quote, $orderId);

            if (!$shipmentCreated && $counter < 5) {
                $messageRequest = ['orderId' => $order->getId(), 'counter' => $counter];

                $this->submitOrderHelper->pushOrderIdInQueueForShipmentCreation(json_encode($messageRequest));
            }
            $this->logger->info(__METHOD__.':'.__LINE__.
                ' Shipment was created for order id:' . $orderId .' with retry attempt:' .$counter);

            return true;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.
                ' An error occured while retrying shipment creation for the message:' .
                     $messageRequest .' with error:'. $e->getMessage());
        }
    }
}
