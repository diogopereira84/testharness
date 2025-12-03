<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface PageInfoDTOInterface
{
    public function getCurrentPage(): int;
    public function getPageSize(): int;
    public function getTotalPages(): int;
    public function getHasNextPage(): bool;
}
