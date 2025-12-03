<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api\Data;

interface UnifiedDataLayerInterface
{
    /**
     * Retrieve order number
     *
     * @return string
     */
    public function getOrderNumber(): string;

    /**
     * Set order number
     *
     * @param string $orderNumber
     *
     * @return UnifiedDataLayerInterface
     */
    public function setOrderNumber(string $orderNumber): UnifiedDataLayerInterface;

    /**
     * Retrieve order total
     *
     * @return string
     */
    public function getOrderTotal(): string;

    /**
     * Set order total
     *
     * @param string $orderTotal
     *
     * @return UnifiedDataLayerInterface
     */
    public function setOrderTotal(string $orderTotal): UnifiedDataLayerInterface;

    /**
     * Retrieve currency
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return UnifiedDataLayerInterface
     */
    public function setCurrency(string $currency): UnifiedDataLayerInterface;

    /**
     * Retrieve customer name
     *
     * @return string
     */
    public function getCustomerName(): string;

    /**
     * Set customer name
     *
     * @param string $customerName
     *
     * @return UnifiedDataLayerInterface
     */
    public function setCustomerName(string $customerName): UnifiedDataLayerInterface;

    /**
     * Retrieve customer type
     *
     * @return string
     */
    public function getCustomerType(): string;

    /**
     * Set customer type
     *
     * @param string $customerType
     *
     * @return UnifiedDataLayerInterface
     */
    public function setCustomerType(string $customerType): UnifiedDataLayerInterface;

    /**
     * Retrieve site
     *
     * @return string
     */
    public function getSite(): string;

    /**
     * Set site
     *
     * @param string $site
     *
     * @return UnifiedDataLayerInterface
     */
    public function setSite(string $site): UnifiedDataLayerInterface;

    /**
     * Retrieve customer email
     *
     * @return string
     */
    public function getCustomerEmail(): string;

    /**
     * Set customer email
     *
     * @param string $customerEmail
     *
     * @return UnifiedDataLayerInterface
     */
    public function setCustomerEmail(string $customerEmail): UnifiedDataLayerInterface;

    /**
     * Retrieve customer session id
     *
     * @return string
     */
    public function getCustomerSessionId(): string;

    /**
     * Set customer session id
     *
     * @param string $customerSessionId
     *
     * @return UnifiedDataLayerInterface
     */
    public function setCustomerSessionId(string $customerSessionId): UnifiedDataLayerInterface;

    /**
     * Retrieve promo code
     *
     * @return string
     */
    public function getPromoCode(): string;

    /**
     * Set promo code
     *
     * @param string $promoCode
     *
     * @return UnifiedDataLayerInterface
     */
    public function setPromoCode(string $promoCode): UnifiedDataLayerInterface;

    /**
     * Retrieve deliveries
     *
     * @return array
     */
    public function getDeliveries(): array;

    /**
     * Set deliveries
     *
     * @param array $deliveries
     *
     * @return UnifiedDataLayerInterface
     */
    public function setDeliveries(array $deliveries): UnifiedDataLayerInterface;
}
