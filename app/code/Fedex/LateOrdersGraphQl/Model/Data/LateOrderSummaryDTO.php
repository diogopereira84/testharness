<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\LateOrderSummaryDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\StoreRefDTOInterface;

class LateOrderSummaryDTO implements LateOrderSummaryDTOInterface
{
    public function __construct(
        public string $orderId,
        public string $createdAt,
        public string $status,
        public bool $is_1p
    ) {}

    public function getOrderId(): string { return $this->orderId; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getStatus(): string { return $this->status; }
    public function getIs1p(): bool { return $this->is_1p; }
}
