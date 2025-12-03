<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\Observer\Frontend\Catalog;

use Magento\Checkout\Helper\Cart;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Event\ObserverInterface;

class CartProductAddComplete implements ObserverInterface
{
    protected const MIN_CART_ITEM_THRESHOLD = 'minCartItemThreshold';
    protected const MAX_CART_ITEM_LIMIT = 'maxCartItemLimit';

    /**
     * CartProductAddComplete Constructor.
     *
     * @param Cart $checkoutCartHelper
     * @param CartDataHelper $cartDataHelper
     * @param DeliveryDataHelper $deliveryHelper
     * @param ManagerInterface $messageManager
     *
     * @return void
     */
    public function __construct(
        private Cart $checkoutCartHelper,
        private CartDataHelper $cartDataHelper,
        protected DeliveryDataHelper $deliveryHelper,
        private ManagerInterface $messageManager
    )
    {
    }

    /**
     * Execute Method.
     * To check max cart limit and display warning message with epro flow
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $isLoggedIn = $this->deliveryHelper->isCommercialCustomer();
        if ($isLoggedIn) {
            //Get Threshold and Max Cart limit
            $cartThresholdLimitsArray = $this->cartDataHelper->getMaxCartLimitValue();
            // Get number of current quote line items.
            $quoteItemsCount = $this->checkoutCartHelper->getItemsCount();

            if (is_array($cartThresholdLimitsArray)
                && !empty($cartThresholdLimitsArray[self::MIN_CART_ITEM_THRESHOLD])
                && !empty($cartThresholdLimitsArray[self::MAX_CART_ITEM_LIMIT])) {

                if ($quoteItemsCount >= $cartThresholdLimitsArray[self::MIN_CART_ITEM_THRESHOLD]
                && $quoteItemsCount < $cartThresholdLimitsArray[self::MAX_CART_ITEM_LIMIT]) {
                    $this->messageManager->addWarningMessage(
                        __('You currently have ' . $cartThresholdLimitsArray[self::MIN_CART_ITEM_THRESHOLD] .
                        '+ items in your cart. You may add up to '
                        . $cartThresholdLimitsArray[self::MAX_CART_ITEM_LIMIT] . '
                    items to the cart per order.')
                    );
                } elseif ($quoteItemsCount >= $cartThresholdLimitsArray[self::MAX_CART_ITEM_LIMIT]) {
                    $this->messageManager->addWarningMessage(__(
                        'You may add up to ' . $cartThresholdLimitsArray[self::MAX_CART_ITEM_LIMIT] .
                        ' items to the cart per order.'
                    ));
                }
            }
        }
    }
}
