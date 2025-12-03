<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\CartItemPrices;

class CartItemPricesPlugin
{
    private const PRODUCT_LINE_PRICE = 'productLinePrice';

    private const PRODUCT_RETAIL_PRICE = 'productRetailPrice';

    /**
     * @param RequestQueryValidator $requestQueryValidator
     */
    public function __construct(
        private readonly RequestQueryValidator $requestQueryValidator
    ) {
    }

    /**
     * @param CartItemPrices $subject
     * @param array $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function afterResolve(
        CartItemPrices $subject,
        array $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!$this->requestQueryValidator->isGraphQl()) {
            return $result;
        }

        $cartItem = $value['model'];
        $additionalData = json_decode($cartItem->getAdditionalData() ?? '{}', true);

        $result['row_total']['value'] = $additionalData[self::PRODUCT_RETAIL_PRICE] ?? $result['row_total']['value'];
        $result['total_item_discount']['value'] = $cartItem->getDiscount() ?? $result['total_item_discount']['value'];
        $result['row_total_including_discounts'] = [
            'value' => $additionalData[self::PRODUCT_LINE_PRICE] ?? null,
            'currency' => $cartItem->getQuote()->getQuoteCurrencyCode()
        ];

        return $result;
    }
}
