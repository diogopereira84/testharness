<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\Connector\Model\Quote\Cache;
use Mirakl\GraphQl\Model\Resolver\CartItems;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class CartItemsPlugin
{
    private CONST QUOTE_ITEMS_CACHE_KEY = 'mirakl_quote_items_%d';

    /**
     * @param Cache $cache
     */
    public function __construct(
        private Cache $cache
    ) {
    }

    /**
     * @param CartItems $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        CartItems $subject,
        Field $field,
        ContextInterface $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $cart = $value['model'];
        $this->unsetQuoteItemsCache($cart->getId());

        return [$field, $context, $info, $value, $args];
    }

    /**
     * @param $cartId
     * @return void
     */
    private function unsetQuoteItemsCache($cartId): void
    {
        $registryKey = sprintf(self::QUOTE_ITEMS_CACHE_KEY, $cartId);
        $this->cache->register($registryKey, null);
    }
}
