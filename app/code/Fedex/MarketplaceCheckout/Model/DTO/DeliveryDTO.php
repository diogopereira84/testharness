<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class DeliveryDTO
{
    public function __construct(
        public string $deliveryDate,
        public string $deliveryDateText
    ) {
    }
}
