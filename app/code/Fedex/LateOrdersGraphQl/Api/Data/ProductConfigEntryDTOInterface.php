<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface ProductConfigEntryDTOInterface
{
    public function getKey(): string;
    public function getValue(): ?string;
}

