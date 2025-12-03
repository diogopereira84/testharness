<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Mars\Observer\Sales;

use Fedex\Mars\Model\Client;
use Fedex\Mars\Model\ClientFactory;
use Fedex\Mars\Model\Config;
use Fedex\Mars\Model\OrderProcess;
use Fedex\Mars\Model\OrderProcessFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\Mars\Helper\PublishToQueue;

class OrderSaveAfter implements ObserverInterface
{
    /**
     * @param OrderProcessFactory $orderProcessFactory
     * @param Config $moduleConfig
     * @param ClientFactory $clientFactory
     * @param PublishToQueue $publish
     */
    public function __construct(
        private OrderProcessFactory $orderProcessFactory,
        private Config $moduleConfig,
        private ClientFactory $clientFactory,
        private PublishToQueue $publish
    )
    {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ): void {
        if (!$this->moduleConfig->isEnabled()) {
            return;
        }
        $order = $observer->getEvent()->getOrder();
        $this->publish->publish((int)$order->getId(), 'order');
    }
}
