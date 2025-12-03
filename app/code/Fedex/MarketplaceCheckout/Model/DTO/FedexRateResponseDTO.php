<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

use Fedex\MarketplaceCheckout\Api\FedexRateApiDataInterface;

class FedexRateResponseDTO
{
    public function __construct(private array $response) {}

    public function getSurchargeAmountByType(string $type): float
    {
        $surcharges = $this->response[FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS][0]
        [FedexRateApiDataInterface::SHIPMENT_RATE_DETAIL]
        [FedexRateApiDataInterface::SURCHARGES] ?? [];

        foreach ($surcharges as $surcharge) {
            if ($surcharge['type'] === $type) {
                return (float)($surcharge['amount'] ?? 0);
            }
        }

        return 0.0;
    }
}
