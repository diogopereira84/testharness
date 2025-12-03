<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\CustomerDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\FulfillmentDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\StoreRefDTOInterface;

class OrderDetailsDTO implements OrderDetailsDTOInterface
{
    public function __construct(
        public string $orderId,
        public string $status,
        public string $createdAt,
        public CustomerDTOInterface $customer,
        public ?FulfillmentDTOInterface $fulfillment,
        public StoreRefDTOInterface $store,
        public array $items,
        public ?string $orderNotes,
        public bool $is_1p
    ) {}

    public function getOrderId(): string { return $this->orderId; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getCustomer(): CustomerDTOInterface { return $this->customer; }
    public function getFulfillment(): ?FulfillmentDTOInterface { return $this->fulfillment; }
    public function getStore(): StoreRefDTOInterface { return $this->store; }
    public function getItems(): array { return $this->items; }
    public function getOrderNotes(): ?string { return $this->orderNotes; }
    public function getIs1p(): bool { return $this->is_1p; }
}
