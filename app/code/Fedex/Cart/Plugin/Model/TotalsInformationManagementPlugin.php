<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Plugin\Model;

use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\TotalsInformationManagement;
use Magento\Quote\Api\Data\TotalsInterface;

class TotalsInformationManagementPlugin
{
    /**
     * @param CartRepositoryInterface $cartRepository
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    )
    {
    }
    /**
     * After plugin for calculate
     *
     * @param TotalsInformationManagement $subject
     * @param TotalsInterface $total
     * @param int $cartId
     */
    public function aftercalculate(
        TotalsInformationManagement $subject,
        TotalsInterface $total,
        $cartId
    ) {
        if ($this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            $quote = $this->cartRepository->get($cartId);
            $this->validateQuote($quote);
            $originalSubtotal = $quote->getSubtotal();
            $originalGrantotal = $quote->getGrandTotal();
            $total->setSubtotal($originalSubtotal);
            $total->setGrandTotal($originalGrantotal);
        }
        return $total;
    }

    /**
     * Validate Quote
     *
     * @param  \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Totals calculation is not applicable to empty cart');
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Totals calculation is not applicable to empty cart')
            );
        }
    }
}
