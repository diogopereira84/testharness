<?php
/**
 * Interface FreightInterface
 *
 * Defines methods for handling freight-related operations in the Fedex MarketplaceCheckout module.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Fedex\MarketplaceCheckout\Model\DTO\FedexRateResponseDTO;
use Fedex\MarketplaceCheckout\Model\DTO\FreightRequestDTO;

interface FreightInterface
{
    /**
     * Retrieves the freight surcharge amount from the FedEx API response.
     *
     * @param FedexRateResponseDTO $dto
     * @return string
     */
    public function getFreightSurchargeAmount(FedexRateResponseDTO $dto): string;

    /**
     * Determines if the loading dock option is selected in the request.
     * @param FreightRequestDTO $request
     * @return bool
     */
    public function isLoadingDockSelected(FreightRequestDTO $request): bool;

    /**
     * @param array $shopShippingInfo
     * @param array $freightInfo
     * @param bool $hasOnlySamplePackProduct
     * @return bool
     */
    public function isFreightShipping(array $shopShippingInfo, array $freightInfo, bool $hasOnlySamplePackProduct): bool;
}