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
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Magento\QuoteGraphQl\Model\Resolver\Discounts;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

class DiscountsPlugin
{
    private const FEDEX_DISCOUNT_AMOUNT_LABEL = 'Fedex Discount Amount';

    /**
     * @param TotalsCollector $totalsCollector
     * @param RequestQueryValidator $requestQueryValidator
     * @param ConfigInterface $config
     */
    public function __construct(
        private TotalsCollector $totalsCollector,
        private readonly RequestQueryValidator $requestQueryValidator,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @param Discounts $subject
     * @param array|null $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     */
    public function afterResolve(
        Discounts $subject,
        ?array $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        if (!$this->requestQueryValidator->isGraphQl()) {
            return $result;
        }

        $quote = $value['model'];
        $cartTotals = $this->totalsCollector->collectQuoteTotals($quote);
        $currency = $quote->getQuoteCurrencyCode();

        $discount = $cartTotals->getFedexDiscountAmount();
        if ($this->config->canApplyShippingDiscount() && $quote->getDiscount() > 0) {
            $discount = $quote->getDiscount();
        }

        $result['discounts'] = [
            'label' => self::FEDEX_DISCOUNT_AMOUNT_LABEL,
            'amount' => ['value' => $discount ?? 0, 'currency' => $currency]
        ];
        return $result;
    }
}
