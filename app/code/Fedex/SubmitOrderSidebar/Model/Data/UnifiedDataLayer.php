<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\Data;

use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection;

class UnifiedDataLayer extends DataObject implements UnifiedDataLayerInterface
{
    /**
     * Order number key
     */
    private const ORDER_NUMBER = 'orderNumber';

    /**
     * Order total key
     */
    private const ORDER_TOTAL = 'orderTotal';

    /**
     * Order currency key
     */
    private const CURRENCY = 'currency';

    /**
     * Order customer name key
     */
    private const CUSTOMER_NAME = 'customerName';

    /**
     * Order customer type key
     */
    private const CUSTOMER_TYPE = 'customerType';

    /**
     * Order site key
     */
    private const SITE = 'site';

    /**
     * Order customer email key
     */
    private const CUSTOMER_EMAIL = 'customerEmail';

    /**
     * Order customer session key
     */
    private const CUSTOMER_SESSION_ID = 'customerSessionId';

    /**
     * Order promo code key
     */
    private const PROMO_CODE = 'promoCode';

    /**
     * Order deliveries key
     */
    private const DELIVERIES = 'deliveries';

    /**
     * @inheritDoc
     */
    public function getOrderNumber(): string
    {
        return $this->getData(self::ORDER_NUMBER) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setOrderNumber(string $orderNumber): UnifiedDataLayerInterface
    {
        return $this->setData(self::ORDER_NUMBER, $orderNumber);
    }

    /**
     * @inheritDoc
     */
    public function getOrderTotal(): string
    {
        return $this->getData(self::ORDER_TOTAL) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setOrderTotal(string $orderTotal): UnifiedDataLayerInterface
    {
        return $this->setData(self::ORDER_TOTAL, $orderTotal);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->getData(self::CURRENCY) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): UnifiedDataLayerInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName(): string
    {
        return $this->getData(self::CUSTOMER_NAME) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setCustomerName(string $customerName): UnifiedDataLayerInterface
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerType(): string
    {
        return $this->getData(self::CUSTOMER_TYPE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setCustomerType(string $customerType): UnifiedDataLayerInterface
    {
        return $this->setData(self::CUSTOMER_TYPE, $customerType);
    }

    /**
     * @inheritDoc
     */
    public function getSite(): string
    {
        return $this->getData(self::SITE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setSite(string $site): UnifiedDataLayerInterface
    {
        return $this->setData(self::SITE, $site);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerEmail(): string
    {
        return $this->getData(self::CUSTOMER_EMAIL) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setCustomerEmail(string $customerEmail): UnifiedDataLayerInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerSessionId(): string
    {
        return $this->getData(self::CUSTOMER_SESSION_ID) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setCustomerSessionId(string $customerSessionId): UnifiedDataLayerInterface
    {
        return $this->setData(self::CUSTOMER_SESSION_ID, $customerSessionId);
    }

    /**
     * @inheritDoc
     */
    public function getPromoCode(): string
    {
        return $this->getData(self::PROMO_CODE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setPromoCode(string $promoCode): UnifiedDataLayerInterface
    {
        return $this->setData(self::PROMO_CODE, $promoCode);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveries(): array
    {
        return $this->getData(self::DELIVERIES) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setDeliveries(array $deliveries): UnifiedDataLayerInterface
    {
        return $this->setData(self::DELIVERIES, $deliveries);
    }
}
