<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

use Fedex\MarketplaceCheckout\Model\DTO\DeliveryDTO;
use Fedex\MarketplaceCheckout\Model\DTO\MarketplaceDTO;
use Fedex\MarketplaceCheckout\Model\DTO\PriceDTO;
use Fedex\MarketplaceCheckout\Model\DTO\SelectionDTO;
use Fedex\MarketplaceCheckout\Model\DTO\ShippingDetailsDTO;

class ShippingMethodDTO
{
    public function __construct(
        public ShippingDetailsDTO $shippingDetailsDTO,
        public PriceDTO           $priceDTO,
        public MarketplaceDTO     $marketplaceDTO,
        public SelectionDTO       $selectionDTO,
        public DeliveryDTO        $deliveryDTO,
        public string             $itemId,
    ) {
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return $this->shippingDetailsDTO->carrierCode;
    }

    /**
     * @return string
     */
    public function getMethodCode(): string
    {
        return $this->shippingDetailsDTO->methodCode;
    }

    /**
     * @return string
     */
    public function getCarrierTitle(): string
    {
        return $this->shippingDetailsDTO->carrierTitle;
    }

    /**
     * @return string
     */
    public function getMethodTitle(): string
    {
        return $this->shippingDetailsDTO->methodTitle;
    }

    /**
     * @return string
     */
    public function getShippingTypeLabel(): string
    {
        return $this->shippingDetailsDTO->shippingTypeLabel;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->priceDTO->amount;
    }

    /**
     * @return float
     */
    public function getBaseAmount(): float
    {
        return $this->priceDTO->baseAmount;
    }

    /**
     * @return float
     */
    public function getPriceInclTax(): float
    {
        return $this->priceDTO->priceInclTax;
    }

    /**
     * @return float
     */
    public function getPriceExclTax(): float
    {
        return $this->priceDTO->priceExclTax;
    }

    /**
     * @return string
     */
    public function getLiftGateAmount(): string
    {
        return $this->priceDTO->liftGateAmount;
    }

    /**
     * @return string
     */
    public function getOfferId(): string
    {
        return $this->marketplaceDTO->offerId;
    }

    /**
     * @return string
     */
    public function getShopName(): string
    {
        return $this->marketplaceDTO->shopName;
    }

    /**
     * @return string
     */
    public function getSellerId(): string
    {
        return $this->marketplaceDTO->sellerId;
    }

    /**
     * @return string
     */
    public function getSellerName(): string
    {
        return $this->marketplaceDTO->sellerName;
    }

    /**
     * @return string
     */
    public function getSelected(): string
    {
        return $this->getCarrierCode() . '_' . $this->selectionDTO->selected;
    }

    /**
     * @return string
     */
    public function getSelectedCode(): string
    {
        return $this->selectionDTO->selectedCode;
    }

    /**
     * @return string
     */
    public function getDeliveryDate(): string
    {
        return $this->deliveryDTO->deliveryDate;
    }

    /**
     * @return string
     */
    public function getDeliveryDateText(): string
    {
        return $this->deliveryDTO->deliveryDateText;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return bool
     */
    public function getAvailable(): bool
    {
        return true;
    }
}