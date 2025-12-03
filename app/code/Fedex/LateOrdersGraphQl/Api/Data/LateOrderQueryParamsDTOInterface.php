<?php
namespace Fedex\LateOrdersGraphQl\Api\Data;

interface LateOrderQueryParamsDTOInterface
{
    public function getSince(): ?string;
    public function getUntil(): ?string;
    public function getStatus(): string;
    public function getIs1p(): bool;
    public function getCurrentPage(): int;
    public function getPageSize(): int;
}

