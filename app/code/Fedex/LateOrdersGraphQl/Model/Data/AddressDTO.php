<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\AddressDTOInterface;

class AddressDTO implements AddressDTOInterface
{
    public function __construct(
        public ?string $line1,
        public ?string $line2,
        public ?string $city,
        public ?string $region,
        public ?string $postalCode,
        public ?string $country
    ) {}

    public function getLine1(): ?string { return $this->line1; }
    public function getLine2(): ?string { return $this->line2; }
    public function getCity(): ?string { return $this->city; }
    public function getRegion(): ?string { return $this->region; }
    public function getPostalCode(): ?string { return $this->postalCode; }
    public function getCountry(): ?string { return $this->country; }
}
