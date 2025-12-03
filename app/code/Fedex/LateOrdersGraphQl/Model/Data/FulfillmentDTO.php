<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\FulfillmentDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\AddressDTOInterface;

class FulfillmentDTO implements FulfillmentDTOInterface
{
    public function __construct(
        public ?string $type,
        public ?string $pickupTime,
        public ?string $deliveryTime,
        public ?string $shippingAccountNumber,
        public ?AddressDTOInterface $shippingAddress
    ) {}

    public function getType(): ?string { return $this->type; }
    public function getPickupTime(): ?string { return $this->pickupTime; }
    public function getDeliveryTime(): ?string { return $this->deliveryTime; }
    public function getShippingAccountNumber(): ?string { return $this->shippingAccountNumber; }
    public function getShippingAddress(): ?AddressDTOInterface { return $this->shippingAddress; }
}
