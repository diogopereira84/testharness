<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\PageInfoDTOInterface;

class PageInfoDTO implements PageInfoDTOInterface
{
    public function __construct(
        public int $currentPage,
        public int $pageSize,
        public int $totalPages,
        public bool $hasNextPage
    ) {}

    public function getCurrentPage(): int { return $this->currentPage; }
    public function getPageSize(): int { return $this->pageSize; }
    public function getTotalPages(): int { return $this->totalPages; }
    public function getHasNextPage(): bool { return $this->hasNextPage; }
}
