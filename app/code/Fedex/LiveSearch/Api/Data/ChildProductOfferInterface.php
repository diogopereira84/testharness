<?php
declare(strict_types=1);

namespace Fedex\LiveSearch\Api\Data;

use Magento\Catalog\Model\Product;

interface ChildProductOfferInterface
{
    /**
     * @param Product $product
     * @param $selectedOptions
     * @return mixed
     */
    public function getChildProductOfferId($product,$selectedOptions);
}
