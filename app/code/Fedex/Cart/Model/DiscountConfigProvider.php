<?php

namespace Fedex\Cart\Model;

use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\ConfigProviderInterface;

class DiscountConfigProvider implements ConfigProviderInterface
{
    /**
     * @param Session $checkoutSession
     */
    public function __construct(
        protected Session $checkoutSession
    )
    {
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $discountConfig = [];
        $quote = $this->checkoutSession->getQuote();
        $discount = $quote->getId();
        $discountConfig['discount'] = $discount;
        $quote->collectTotals();

        return $discountConfig;
    }
}
