<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class ShippingDetailsDTO
{
    public function __construct(
        public string $carrierCode,
        public string $methodCode,
        public string $carrierTitle,
        public string $methodTitle,
        public string $shippingTypeLabel,
    ) {
    }
}
