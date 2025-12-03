<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\LateOrderSearchResultDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\PageInfoDTOInterface;

class LateOrderSearchResultDTO implements LateOrderSearchResultDTOInterface
{
    public function __construct(
        public array $items,
        public int $totalCount,
        public PageInfoDTOInterface $pageInfo
    ) {}

    public function getItems(): array { return $this->items; }
    public function getTotalCount(): int { return $this->totalCount; }
    public function getPageInfo(): PageInfoDTOInterface { return $this->pageInfo; }
}
