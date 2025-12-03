<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface OrderDetailsDTOInterface
{
    public function getOrderId(): string;
    public function getStatus(): string;
    public function getCreatedAt(): string;
    public function getCustomer(): CustomerDTOInterface;
    public function getFulfillment(): ?FulfillmentDTOInterface;
    public function getStore(): StoreRefDTOInterface;
    public function getItems(): array;
    public function getOrderNotes(): ?string;
    public function getIs1p(): bool;
}

