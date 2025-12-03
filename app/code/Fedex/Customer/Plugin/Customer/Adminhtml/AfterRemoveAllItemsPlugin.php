<?php

declare(strict_types=1);

namespace Fedex\Customer\Plugin\Customer\Adminhtml;

use Fedex\Customer\Model\QuoteManager;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class AfterRemoveAllItemsPlugin
{
    public function __construct(
        private readonly QuoteManager    $quoteManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * After plugin for Quote::removeAllItems()
     *
     * @param Quote $quote
     * @param Quote $result
     * @return Quote
     */
    public function afterRemoveAllItems(Quote $quote, $result): Quote
    {
        try {
            $this->quoteManager->resetCustomerQuote($quote);
        } catch (\Throwable $e) {
            $this->logger->error(
                '[Fedex_Customer] AfterRemoveAllItemsPlugin error: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        return $result;
    }
}
