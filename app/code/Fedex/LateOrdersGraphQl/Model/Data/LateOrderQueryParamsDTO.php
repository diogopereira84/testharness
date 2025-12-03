<?php
namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\LateOrderQueryParamsDTOInterface;

class LateOrderQueryParamsDTO implements LateOrderQueryParamsDTOInterface
{
    public function __construct(
        public ?string $since = null,
        public ?string $until = null,
        public string $status = 'NEW',
        public bool $is1p = true,
        public int $currentPage = 1,
        public int $pageSize = 100
    ) {}

    public function getSince(): ?string { return $this->since; }
    public function getUntil(): ?string { return $this->until; }
    public function getStatus(): string { return $this->status; }
    public function getIs1p(): bool { return $this->is1p; }
    public function getCurrentPage(): int { return $this->currentPage; }
    public function getPageSize(): int { return $this->pageSize; }
}
