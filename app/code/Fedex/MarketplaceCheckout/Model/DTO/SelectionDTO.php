<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class SelectionDTO
{
    public function __construct(
        public string $selected,
        public string $selectedCode
    ) {
    }
}
