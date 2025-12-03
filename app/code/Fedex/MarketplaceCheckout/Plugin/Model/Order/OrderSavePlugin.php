<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Plugin\Model\Order;

use Closure;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Mirakl\Connector\Helper\Config;
use Psr\Log\LoggerInterface;

class OrderSavePlugin
{
    /**
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param PublisherInterface $publisher
     * @param Config $config
     * @param CacheInterface $cache
     * @param MarketPlaceHelper $marketPlaceHelper
     */
    public function __construct(
        private LoggerInterface    $logger,
        private Data               $helper,
        private PublisherInterface $publisher,
        private Config             $config,
        private CacheInterface     $cache,
        private MarketPlaceHelper  $marketPlaceHelper
    ) {
    }

    /**
     * @param OrderResourceInterface $subject
     * @param Closure $proceed
     * @param AbstractModel $order
     * @return OrderResourceInterface
     */
    public function aroundSave(
        OrderResourceInterface $subject,
        Closure                $proceed,
        AbstractModel          $order
    ) {
        $return = $proceed($order);
        try {
            $isNew = $order->isObjectNew();

            if (!$isNew) {
                $validStatus = in_array($order->getStatus(), $this->config->getCreateOrderStatuses());
                if ($validStatus) {
                    $this->publisher->publish('sendOrderToMiraklQueue', json_encode(['order_id' => $order->getId()]));
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' sendOrderToMiraklQueue Publisher - Order Increment ID ' . $order->getIncrementId());

                    if ($this->marketPlaceHelper->isFreightShippingEnabled()) {
                        $cacheKey = 'freight_packaging_response_' . $order->getQuoteId();
                        $this->cache->remove($cacheKey);
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore to avoid errors in frontend
            $this->logger->warning($e->getMessage());
        }
        return $return;
    }
}
