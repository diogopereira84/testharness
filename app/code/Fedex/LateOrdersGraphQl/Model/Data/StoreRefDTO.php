<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\StoreRefDTOInterface;

class StoreRefDTO implements StoreRefDTOInterface
{
    public function __construct(
        public string $storeId,
        public string $storeNumber,
        public string $storeEmail
    ) {}

    public function getStoreId(): string { return $this->storeId; }
    public function getStoreNumber(): string { return $this->storeNumber; }
    public function getStoreEmail(): string { return $this->storeEmail; }
}
