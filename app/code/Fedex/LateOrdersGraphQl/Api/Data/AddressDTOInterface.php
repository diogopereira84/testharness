<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface AddressDTOInterface
{
    public function getLine1(): ?string;
    public function getLine2(): ?string;
    public function getCity(): ?string;
    public function getRegion(): ?string;
    public function getPostalCode(): ?string;
    public function getCountry(): ?string;
}

