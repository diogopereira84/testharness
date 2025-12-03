<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface StoreRefDTOInterface
{
    public function getStoreId(): string;
    public function getStoreNumber(): string;
    public function getStoreEmail(): string;
}

