<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Model;

use Fedex\Customer\Api\QuoteManagerInterface;
use Fedex\Customer\Model\ToggleConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

class QuoteManager implements QuoteManagerInterface
{
    public function __construct(
        private readonly CartRepositoryInterface     $quoteRepository,
        private readonly CartManagementInterface     $quoteManagement,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface       $storeManager,
        private readonly ToggleConfig                $config
    ) {
    }

    public function resetCustomerQuote(Quote $quote): void
    {
        if (!$this->config->isAdminResetCardUpdateToggleEnabled() || !$quote->getIsActive()) {
            return;
        }

        $customerId = $quote->getCustomerId();
        $targetStoreId = $quote->getStoreId();

        // Set current quote to inactive
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);

        // Create a new active quote for the customer
        if ($customerId > 0) {
            $newQuoteId = $this->quoteManagement->createEmptyCartForCustomer($customerId);
            $newQuote = $this->quoteRepository->get($newQuoteId);

            if ($targetStoreId > 0 && $newQuote->getStoreId() !== $targetStoreId) {
                $newQuote->setStoreId($targetStoreId);
            }

            $newQuote->setIsActive(true);
            $this->quoteRepository->save($newQuote);
        }
    }
}