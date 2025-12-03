<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Plugin\Model;

use Magento\Quote\Api\Data\CartItemInterface;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;

class SubmitOrderApi
{
    /**
     * Construct
     *
     * @param QuoteOptions $quoteOptions
     * @param QuoteHelper $quoteHelper
     */
    public function __construct(
        private QuoteOptions            $quoteOptions,
        private QuoteHelper             $quoteHelper
    ) {
    }

    /**
     * @param \Fedex\SubmitOrderSidebar\Model\SubmitOrderApi $subject
     * @param $paymentData
     * @param $dataObjectForFujistu
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeCreateOrderBeforePayment(
        \Fedex\SubmitOrderSidebar\Model\SubmitOrderApi $subject,
        $paymentData,
        $dataObjectForFujistu
    ) {
        $quote = $dataObjectForFujistu->getQuoteData();
        if ($this->quoteHelper->isMiraklQuote($quote)) {
            $this->quoteOptions->setMktShippingAndTaxInfo($quote);
        }
    }
}
