<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jyoti Thakur <jyoti.thakur.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;


class NoCustomerPaymentObserver implements ObserverInterface
{
    const NO_PAYMENT_CODE = 'NO_CUSTOMER_PAYMENT_CONFIRMATION';

    /**
     * Construct
     *
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        // Retrieve the order object with:
        $createOrder = $observer->getEvent()->getCreateOrder();

        // Modify the payment workflow:
        $createOrder->setPaymentWorkflow(self::NO_PAYMENT_CODE);
    }
}

