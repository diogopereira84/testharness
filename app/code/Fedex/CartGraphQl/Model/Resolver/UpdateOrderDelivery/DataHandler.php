<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery;

use Fedex\CartGraphQl\Api\UpdateOrderDeliveryInterface;
use Magento\Quote\Model\Quote;

class DataHandler implements UpdateOrderDeliveryInterface
{
    /**
     * @param array $deliveryDataHandler
     */
    public function __construct(
        private array $deliveryDataHandlers = []
    ) {
    }

    /**
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    public function execute(Quote $cart, array $data): void
    {
        foreach ($this->deliveryDataHandlers as $deliveryDataHandler) {
            $deliveryDataHandler->setData($cart, $data);
        }
    }
}
