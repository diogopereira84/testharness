<?php
declare(strict_types=1);

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\TaxCollector\Observer\Sales;

/**
 * SaveOrderBeforeSalesModelQuoteObserver Observer
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SaveOrderBeforeSalesModelQuoteObserver implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig
     * @param \Fedex\Delivery\Helper\Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected \Magento\Framework\DataObject\Copy $objectCopyService,
        private \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig,
        private \Fedex\Delivery\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $discount = $quote->getDiscount();
        $customTaxAmount = $quote->getCustomTaxAmount();

        //B-995245 - Sanchit Bhatia Save production location id in Sales order Table.

        if ($this->helper->isCommercialCustomer() &&
            $quote->getProductionLocationId() !='' &&
            $quote->getProductionLocationId() != null) {
            $order->setProductionLocationId($quote->getProductionLocationId());
        }
        $order->setDiscountAmount($discount);
        $order->setBaseDiscountAmount($discount);
        $order->setTaxAmount($customTaxAmount);
        $order->setBaseTaxAmount($customTaxAmount);
        $estimatePickupTime = $quote->getEstimatedPickupTime() ? $quote->getEstimatedPickupTime() : '';
        $order->setEstimatedPickupTime($estimatePickupTime);
        $items = $quote->getAllVisibleItems();

        foreach ($items as $quoteItem) {
            $origOrderItem = $order->getItemByQuoteItemId($quoteItem->getId());

            // update order item according your need
            $origOrderItem->setDiscountAmount($quoteItem->getDiscount());
            $origOrderItem->setBaseDiscountAmount($quoteItem->getDiscount());
        }

        $this->objectCopyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Sales Convert Quote Exception : '.$e->getMessage());
        }
        return $this;
    }
}
