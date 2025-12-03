<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\ShippingMethodBuilderInterface;
use Fedex\MarketplaceCheckout\Model\DTO\ShippingMethodDTO;
class ShippingMethodBuilder implements ShippingMethodBuilderInterface
{
    /**
     * Creates a shipping method based on the provided data.
     *
     * @param ShippingMethodDTO $data
     * @return array
     */
    public function createShippingMethod(ShippingMethodDTO $data): array
    {
        return [
            'carrier_code' => $data->getCarrierCode(),
            'method_code' => $data->getMethodCode(),
            'carrier_title' => $data->getCarrierTitle(),
            'method_title' => $data->getMethodTitle(),
            'amount' => $data->getAmount(),
            'base_amount' => $data->getBaseAmount(),
            'available' => $data->getAvailable(),
            'price_incl_tax' => $data->getPriceInclTax(),
            'price_excl_tax' => $data->getPriceExclTax(),
            'offer_id' => $data->getOfferId(),
            'title' => $data->getShopName(),
            'selected' => $data->getSelected(),
            'selected_code' => $data->getSelectedCode(),
            'item_id' => $data->getItemId(),
            'shipping_type_label' => $data->getShippingTypeLabel(),
            'deliveryDate' => $data->getDeliveryDate(),
            'deliveryDateText' => $data->getDeliveryDateText(),
            'marketplace' => true,
            'seller_id' => $data->getSellerId(),
            'seller_name' => $data->getSellerName(),
            'surcharge_amount' => $data->getLiftGateAmount()
        ];
    }
}