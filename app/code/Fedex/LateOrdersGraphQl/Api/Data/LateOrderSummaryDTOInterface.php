<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface LateOrderSummaryDTOInterface
{
    public function getOrderId(): string;
    public function getCreatedAt(): string;
    public function getStatus(): string;
    public function getIs1p(): bool;
}

