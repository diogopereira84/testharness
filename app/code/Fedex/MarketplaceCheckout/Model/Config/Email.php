<?php

namespace Fedex\MarketplaceCheckout\Model\Config;

use Fedex\MarketplaceCheckout\Model\OrderStoreRetriever;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Exception;

class Email
{

    /** @var string[]  */
    private const EMAIL_STATUS = [
        'shipped' => 'fedex/transactional_email/order_shipment_delivery_enable',
        'delivered' => 'fedex/transactional_email/order_shipment_delivery_enable',
        'delivered_multiple' => 'fedex/transactional_email/order_shipment_multiple_delivery_enable',
        'ready_for_pickup' => 'fedex/transactional_email/order_ready_for_pickup_enable',
        'cancelled' => 'fedex/transactional_email/order_cancelled_enable',
        'confirmed' => 'fedex/transactional_email/order_confirmed_enable',
        'delivery_date_updated' => 'fedex/transactional_email/order_delivery_date_updated_enable'
    ];

    /** @var string[]  */
    private const EMAIL_TEMPLATES = [
        'shipped' => 'fedex/transactional_email/order_shipment_delivery',
        'delivered' => 'fedex/transactional_email/order_shipment_delivery',
        'delivered_multiple' => 'fedex/transactional_email/order_shipment_multiple_delivery',
        'ready_for_pickup' => 'fedex/transactional_email/order_ready_for_pickup',
        'cancelled' => 'fedex/transactional_email/order_cancelled',
        'confirmed' => 'fedex/transactional_email/order_confirmed',
        'delivery_date_updated' => 'fedex/transactional_email/order_delivery_date_updated'
    ];

    /**
     * Data constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderStoreRetriever $orderStoreRetriever
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly OrderStoreRetriever $orderStoreRetriever
    ) {
    }

    /**
     * Get Email enabled value
     *
     * @param string $status
     * @param int|null $order_id
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getEmailEnabled(string $status, ?int $order_id = null): bool
    {
        $storeId = $this->getStoreIdFromOrderId($order_id);

        try {
            return (bool) $this->scopeConfig->getValue(
                self::EMAIL_STATUS[$status],
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?? false;
        } catch (Exception $e) {
            return false;
        }


    }

    /**
     * Get Email Template
     *
     * @param string $status
     * @param int|null $order_id
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getEmailTemplate(string $status, ?int $order_id = null): mixed
    {
        $storeId = $this->getStoreIdFromOrderId($order_id);

        try {
            return $this->scopeConfig->getValue(
                self::EMAIL_TEMPLATES[$status],
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?? false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param int|null $order_id
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStoreIdFromOrderId(?int $order_id = null): int
    {
        return $order_id ?
            $this->orderStoreRetriever->getStoreIdFromOrder($order_id) :
            $this->storeManager->getStore()->getId();
    }

}
