<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface FulfillmentDTOInterface
{
    public function getType(): ?string;
    public function getPickupTime(): ?string;
    public function getDeliveryTime(): ?string;
    public function getShippingAccountNumber(): ?string;
    public function getShippingAddress(): ?AddressDTOInterface;
}

