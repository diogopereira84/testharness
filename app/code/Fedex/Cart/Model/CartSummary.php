<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Model;

use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Model\TotalsInformationManagement;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class CartSummary
 */
class CartSummary extends TotalsInformationManagement
{
    /**
     * @param CartRepositoryInterface      $cartRepository
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        CartRepositoryInterface      $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    )
    {
        parent::__construct($cartRepository, $cartTotalRepository);
    }

    /**
     * {@inheritDoc}
     */
    public function calculate(
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        $quote = $this->cartRepository->get($cartId);
        $originalSubtotal = $quote->getSubtotal();
        $originalGrantotal = $quote->getGrandTotal();
        if (!$this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            $this->validateQuote($quote);
        }
        if ($quote->getIsVirtual()) {
            $quote->setBillingAddress($addressInformation->getAddress());
        } else {
            $quote->setShippingAddress($addressInformation->getAddress());
            $quote->getShippingAddress()->setCollectShippingRates(true)->setShippingMethod(
                $addressInformation->getShippingCarrierCode() . '_' . $addressInformation->getShippingMethodCode()
            );
        }
        $quote->collectTotals();

        $total = $this->cartTotalRepository->get($cartId);
        if (!$this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
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
