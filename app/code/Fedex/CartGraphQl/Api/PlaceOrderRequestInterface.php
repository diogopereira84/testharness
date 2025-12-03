<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api;

use \Magento\Quote\Api\Data\CartInterface;

interface PlaceOrderRequestInterface
{
    public const PAYMENT_METHOD = "instore";

    /**
     * @param CartInterface $quote
     * @param array|null $notes
     * @return object
     */
    public function build(
        CartInterface $quote,
        ?array $notes = null
    ): object;
}
