<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api;

use Fedex\B2b\Model\Quote\Address;

interface CollectRatesInterface
{
    /**
     * @param Address $address
     * @return void
     */
    public function execute(Address $address): void;
}
