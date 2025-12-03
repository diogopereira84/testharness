<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Observer;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class SalesOrderCreditmemoSaveAfter implements ObserverInterface
{
    /**
     * SalesOrderCreditmemoAfter constructor.
     *
     * @param LoggerInterface $logger
     * @param HandleMktCheckout $handleMktCheckout
     */
    public function __construct(
        private LoggerInterface $logger,
        private readonly HandleMktCheckout $handleMktCheckout
    )
    {
    }

    /**
     * @param Observer $observer
     *
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $observer->getEvent()->getCreditmemo();

            /** @var \Magento\Sales\Model\Order $order */
            $order = $creditmemo->getOrder();
            if($creditmemo->getGrandTotal() == $order->getGrandTotal()) {
                $order->setState("canceled");
                $order->setStatus("Cancelled");
                $order->save();
                $this->logger->info(__METHOD__.':'.__LINE__.':'.$order->getId().
                'Credit memo order status has been changed to cancelled successfully');
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.
            ':Credit memo order status changed exception : '.$e->getMessage());
        }

        return;
    }
}
