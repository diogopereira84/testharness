<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Plugin;

use Magento\NegotiableQuote\Model\Quote\Totals;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class TotalsTaxPlugin
{
    /**
     * Constructor TotalsTaxPlugin.
     *
     * @param AdminConfigHelper $adminConfigHelper
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper
    ) {
    }
    
    /**
     * Modify the tax value returned by getTaxValue().
     *
     * @param Totals $subject
     * @param float $result
     * @param bool $useQuoteCurrency
     * @return float
     */
    public function afterGetTaxValue(Totals $subject, $result, $useQuoteCurrency = false)
    {
        // Check if the toggle is enabled
        $quoteDetailEnhancementToggle = $this->adminConfigHelper->isMagentoQuoteDetailEnhancementToggleEnabled();

        // If toggle is disabled, return the default functionality (result)
        if (!$quoteDetailEnhancementToggle) {
            return $result;
        }

        $quote = $subject->getQuote();
        $customTaxAmount = $quote->getData('custom_tax_amount');
        return $customTaxAmount ?? $result;
    }

    /**
     * After plugin for getTotalCost()
     *
     * @param Totals $subject
     * @param float $result
     * @param bool $useQuoteCurrency
     * @return float
     */
    public function afterGetTotalCost(Totals $subject, $result, $useQuoteCurrency = false)
    {
        // Check if the toggle is enabled
        $quoteDetailEnhancementToggle = $this->adminConfigHelper->isMagentoQuoteDetailEnhancementToggleEnabled();

        // If toggle is disabled, return the default functionality (result)
        if (!$quoteDetailEnhancementToggle) {
            return $result;
        }

        $quote = $subject->getQuote();
        $totalCost = $quote->getData('subtotal');

        return $totalCost ?? $result;
    }

    /**
     * After plugin for getSubtotal to fix discount subtraction
     *
     * @param Totals $subject
     * @param float|int $result
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetSubtotal(Totals $subject, $result, $useQuoteCurrency = false)
    {
        // Check if the toggle is enabled
        $quoteDetailEnhancementToggle = $this->adminConfigHelper->isMagentoQuoteDetailEnhancementToggleEnabled();

        // If toggle is disabled, return the default functionality (result)
        if (!$quoteDetailEnhancementToggle) {
            return $result;
        }

        $quote = $subject->getQuote();
        $discount = $quote->getData('discount');
        $correctedSubtotal = $result - $discount;
        return $correctedSubtotal ?? $result;
    }

    /**
     * After plugin for getCatalogTotalPrice()
     *
     * @param Totals $subject
     * @param float|int $result
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetCatalogTotalPrice(Totals $subject, $result, $useQuoteCurrency = false)
    {
        // Check if the toggle is enabled
        $quoteDetailEnhancementToggle = $this->adminConfigHelper->isMagentoQuoteDetailEnhancementToggleEnabled();

        // If toggle is disabled, return the default functionality (result)
        if (!$quoteDetailEnhancementToggle) {
            return $result;
        }

        $quote = $subject->getQuote();
        $subtotalWithDiscount = $quote->getData('subtotal_with_discount');

        return $subtotalWithDiscount ?? $result;
    }

    /**
     * After plugin for getCatalogTotalPriceWithoutTax()
     *
     * @param Totals $subject
     * @param float|int $result
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetCatalogTotalPriceWithoutTax(Totals $subject, $result, $useQuoteCurrency = false)
    {
        // Check if the toggle is enabled
        $quoteDetailEnhancementToggle = $this->adminConfigHelper->isMagentoQuoteDetailEnhancementToggleEnabled();

        // If toggle is disabled, return the default functionality (result)
        if (!$quoteDetailEnhancementToggle) {
            return $result;
        }

        $quote = $subject->getQuote();
        $customCatalogTotal = $quote->getData('base_subtotal');

        return $customCatalogTotal ?? $result;
    }
}
