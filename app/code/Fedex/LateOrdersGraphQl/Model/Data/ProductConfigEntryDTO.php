<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\ProductConfigEntryDTOInterface;

class ProductConfigEntryDTO implements ProductConfigEntryDTOInterface
{
    public function __construct(
        public string $key,
        public ?string $value
    ) {}

    public function getKey(): string { return $this->key; }
    public function getValue(): ?string { return $this->value; }
}
