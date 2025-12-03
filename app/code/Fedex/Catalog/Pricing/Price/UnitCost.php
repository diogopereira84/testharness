<?php
declare(strict_types=1);

namespace Fedex\Catalog\Pricing\Price;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\FinalPriceInterface;

class UnitCost extends FinalPrice implements FinalPriceInterface
{
    /**
     * Default price type
     */
    const PRICE_CODE = 'unit_cost';

    /**
     * Get price value
     *
     * @return float
     */
    public function getValue()
    {
        if ($this->value === null) {
            $price = $this->product->getUnitCost();
            $priceInCurrentCurrency = $this->priceCurrency->convertAndRound($price);
            $this->value = $priceInCurrentCurrency ? (float)$priceInCurrentCurrency : 0;
        }
        return $this->value;
    }
}
