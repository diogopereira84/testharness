<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\FedexRateApiDataInterface;
use Fedex\MarketplaceCheckout\Api\FreightInterface;
use Fedex\MarketplaceCheckout\Model\DTO\FreightRequestDTO;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingConstants;
use Fedex\MarketplaceCheckout\Model\DTO\FedexRateResponseDTO;
use Fedex\MarketplaceRates\Helper\Data;

class Freight implements FreightInterface
{
    public function __construct(
        private Data $helper
    ) {}
    /**
     * Retrieves the freight surcharge amount from the FedEx API response.
     *
     * @param array $ratesFromRatesFedexApi The response data from the FedEx API containing rate details.
     * @return string The formatted freight surcharge amount.
     */
    public function getFreightSurchargeAmount(FedexRateResponseDTO $dto): string
    {
        $amount = $dto->getSurchargeAmountByType(ShippingConstants::LIFTGATE_DELIVERY);
        return number_format($amount, 2);
    }

    /**
     * Determines if the loading dock option is selected in the request.
     *
     * @param mixed $request The request object or data to check for the loading dock selection.
     * @return bool True if the loading dock option is selected, false otherwise.
     */
    public function isLoadingDockSelected(FreightRequestDTO $request): bool
    {
        return $request->hasLiftGate();
    }

    /**
     * @param array $shopShippingInfo
     * @param array $freightInfo
     * @param bool $hasOnlySamplePackProduct
     * @return bool
     */
    public function isFreightShipping(array $shopShippingInfo, array $freightInfo, bool $hasOnlySamplePackProduct): bool
    {
        return $this->helper->isFreightShippingEnabled() &&
            $shopShippingInfo['freight_enabled'] &&
            $freightInfo &&
            !$hasOnlySamplePackProduct;
    }
}