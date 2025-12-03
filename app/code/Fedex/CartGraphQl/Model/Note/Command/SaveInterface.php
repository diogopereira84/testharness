<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Note\Command;

use Magento\Quote\Model\Quote;

interface SaveInterface
{
    /**
     * Persist order notes on quote integration
     *
     * @param Quote $cart
     * @param string $note
     * @return void
     */
    public function execute(Quote $cart, string $note): void;
}
