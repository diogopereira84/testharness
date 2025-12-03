<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class PriceDTO
{
    public function __construct(
        public float  $amount,
        public float  $baseAmount,
        public float  $priceInclTax,
        public float  $priceExclTax,
        public string $liftGateAmount
    ) {
    }
}
