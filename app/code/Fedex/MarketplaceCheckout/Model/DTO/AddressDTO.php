<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

use Fedex\MarketplaceCheckout\Model\Constants\ShippingAddressKeys;

class AddressDTO
{
    public function __construct(
        public readonly string $city,
        public readonly string $state,
        public readonly string $postalCode
    ) {}

    public function toArray(): array
    {
        return [
            ShippingAddressKeys::CITY => $this->city,
            ShippingAddressKeys::STATE_OR_PROVINCE => $this->state,
            ShippingAddressKeys::POSTAL_CODE => $this->postalCode,
        ];
    }
}
