<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api\Data;

use Magento\Quote\Model\Quote;

interface DeliveryDataHandlerInterface
{
    /**
     * @return string
     */
    public function getDataKey(): string;

    /**
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    public function setData(Quote $cart, array $data): void;

    /**
     * Proceed setting delivery data if current data applies to the current delivery method
     *
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    public function proceed(Quote $cart, array $data): void;
}
