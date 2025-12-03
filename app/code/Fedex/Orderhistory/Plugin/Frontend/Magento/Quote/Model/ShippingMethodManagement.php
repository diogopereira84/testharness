<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Quote\Model;

class ShippingMethodManagement
{
    /**
     * @inheritDoc
     */
    public function __construct(
        private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        private \Fedex\Orderhistory\Helper\Data $helper
    )
    {
    }

    /**
     * @inheritDoc
     *
     * B-1063523
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetList(\Magento\Quote\Model\ShippingMethodManagement $subject, callable $proceed, $cartId)
    {
        if ($this->helper->isModuleEnabled()) {
            $quote = $this->quoteRepository->getActive($cartId);
            $shippingAddress = $quote->getShippingAddress();
            if (!$shippingAddress->getCountryId()) {
                return [];
            }
        }
        return $proceed($cartId);
    }
}
