<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface LateOrderSearchResultDTOInterface
{
    public function getItems(): array;
    public function getTotalCount(): int;
    public function getPageInfo(): PageInfoDTOInterface;
}

