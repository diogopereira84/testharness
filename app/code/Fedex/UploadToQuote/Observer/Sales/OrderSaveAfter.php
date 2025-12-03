<?php
/**
 * @category    Fedex
 * @package     Fedex_UploadToQuote
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Observer\Sales;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;

class OrderSaveAfter implements ObserverInterface
{
    public const TIGER_DISPLAY_SELFREG_CART_FXO_DISCOUNT_3P_ONLY = 'tiger_b1973447_display_selfreg_cart_fxo_discount_3P_only';

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected ToggleConfig $toggleConfig
    )
    {}

    /**
     * @param Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer): void {
        if($this->isTigerDisplaySelfregCartFxoDiscount3POnlyEnabled()) {

            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            if ($order && is_null($order->getShippingAmount()) && is_null($order->getBaseShippingAmount())
                && $this->orderHas1pProduct($order) && $shippingValue = $this->checkIfQuoteHasShippingFor1p($order))
            {
                $order->setShippingAmount($shippingValue);
                $order->setBaseShippingAmount($shippingValue);
                $order->save();
            }
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function orderHas1pProduct(Order $order) {
        foreach ($order->getAllItems() as $item) {
            if (!$item->getMiraklOfferId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Order $order
     * @return float|null
     */
    protected function checkIfQuoteHasShippingFor1p(Order $order) {
        try {
            $quote = $this->cartRepository->get($order->getQuoteId());
            $quoteAddress = $quote->getShippingAddress();
            if ($quoteAddress->getShippingAmount() != '0' && $quoteAddress->getBaseShippingAmount() != '0'
                && $quoteAddress->getShippingMethod() && $quoteAddress->getShippingDescription())
            {
                return $quoteAddress->getShippingAmount();
            }
        } catch (\Exception $e) {}

        return null;
    }

    protected function isTigerDisplaySelfregCartFxoDiscount3POnlyEnabled(): bool {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_DISPLAY_SELFREG_CART_FXO_DISCOUNT_3P_ONLY);
    }
}
