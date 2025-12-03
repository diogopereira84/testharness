<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface CustomerDTOInterface
{
    public function getName(): string;
    public function getEmail(): string;
    public function getPhone(): ?string;
}

