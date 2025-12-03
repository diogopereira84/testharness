<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Magento\QuoteGraphQl\Model\Resolver\CartPrices;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

class CartPricesPlugin
{
    /**
     * @param TotalsCollector $totalsCollector
     * @param RequestQueryValidator $requestQueryValidator
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param ConfigInterface $config
     */
    public function __construct(
        private TotalsCollector $totalsCollector,
        private readonly RequestQueryValidator $requestQueryValidator,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @param CartPrices $subject
     * @param array $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function afterResolve(CartPrices $subject, array $result, Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        if (!$this->requestQueryValidator->isGraphQl()) {
            return $result;
        }

        $quote = $result['model'];
        $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
        $cartTotals = $this->totalsCollector->collectQuoteTotals($quote);
        $currency = $quote->getQuoteCurrencyCode();

        $subtotal = $cartTotals->getBaseSubtotal();
        if ($this->config->isEnabledCartPricingFix() &&
            (int)$quote->getBaseSubtotalWithDiscount() > 0 &&
            !$this->config->canApplyShippingDiscount()) {
            $subtotal = $quote->getBaseSubtotalWithDiscount();
        }
        if ($this->config->canApplyShippingDiscount()) {
            $result['grand_total'] = ['value' => $quote->getGrandTotal(), 'currency' => $currency];
        }
        $result['subtotal_excluding_tax'] = ['value' => $subtotal, 'currency' => $currency];
        $result['subtotal_with_discount_excluding_tax'] = [
            'value' => $quoteIntegration->getRaqNetAmount(),
            'currency' => $currency
        ];
        $result['applied_taxes'] = [];
        $result['applied_taxes'][] = [
            'label' => 'Fedex Tax Amount',
            'amount' => ['value' => $cartTotals->getCustomTaxAmount() ?? 0, 'currency' => $currency]
        ];

        return $result;
    }
}
