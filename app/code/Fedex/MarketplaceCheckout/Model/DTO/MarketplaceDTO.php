<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class MarketplaceDTO
{
    public function __construct(
        public string $offerId,
        public string $shopName,
        public string $sellerId,
        public string $sellerName
    ) {
    }
}
