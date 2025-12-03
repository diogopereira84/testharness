<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api;

use Magento\Quote\Model\Quote;

interface UpdateOrderDeliveryInterface
{
    /**
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    public function execute(Quote $cart, array $data): void;
}
