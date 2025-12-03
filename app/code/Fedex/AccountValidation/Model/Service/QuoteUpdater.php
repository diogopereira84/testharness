<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Model\Service;

use Exception;
use Magento\Checkout\Model\CartFactory;

class QuoteUpdater
{
    public function __construct(
        private readonly CartFactory $cartFactory
    ) {}

    /**
     * Update quote with the FedEx shipping account number.
     *
     * @param string $accountNumber
     * @param bool $isValid
     * @throws Exception
     */
    public function update(string $accountNumber, bool $isValid): void
    {
        $quote = $this->cartFactory->create()->getQuote();
        $quote->setData('fedex_ship_account_number', $isValid ? $accountNumber : null);
        $quote->save();
    }
}
