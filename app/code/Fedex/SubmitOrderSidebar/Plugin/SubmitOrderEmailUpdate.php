<?php

/**
 * @category Fedex
 * @package  Fedex_SubmitOrderSidebar
 * @copyright  Copyright (c) 2022 Fedex
 * @author  Attri Kumar <attri.kumar@infogain.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Plugin;

use Exception;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class SubmitOrderEmailUpdate
{
    /**
     * @param ConfigInterface $configInterface
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        protected CartFactory $cartFactory,
        protected CheckoutSession $checkoutSession
    )
    {
    }

    /**
     * Plugin to override the email if alternate contact is selected
     *
     * @param  Object $orderService
     * @param  Object $order
     * @return Object $order
     */
    public function beforePlace(\Magento\Sales\Model\Service\OrderService $orderService, $order)
    {
        if (!empty($this->checkoutSession->getAlternateContactAvailable())) {
            $quote = $this->cartFactory->create()->getQuote();
            $order->getShippingAddress()->setEmail($quote->getShippingAddress()->getEmail());
        }

        return [$order];
    }
}
