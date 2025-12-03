<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
namespace Fedex\Cart\Observer\Frontend\Cart;

use Fedex\Cart\Helper\Data;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Checkout\Helper\Cart;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;

/**
 * Class CartLimitValidation
 *
 * @package Fedex\Cart\Observer\Frontend\Page
 */
class CartLimitValidation implements \Magento\Framework\Event\ObserverInterface
{
    private static $hasExecuted = null;

    /**
     * Constructor
     *
     * @param Data               $cartDataHelper
     * @param Cart               $checkoutCartHelper
     * @param Http               $request
     * @param DeliveryDataHelper $deliveryHelper
     * @param RedirectInterface  $redirect
     */
    public function __construct(
        private Data $cartDataHelper,
        private Cart $checkoutCartHelper,
        private Http $request,
        private DeliveryDataHelper $deliveryHelper,
        private RedirectInterface $redirect,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
    )
    {
    }

    /**
     * Execute Method
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            if(self::$hasExecuted){
                return;
            }
            self::$hasExecuted = true;

            $paramEdit = $this->request->getparam('edit');
            $cartThresholdLimitsArray = $this->cartDataHelper->getMaxCartLimitValue();
            $quoteItemsCount = $this->checkoutCartHelper->getItemsCount();

            if (!empty($cartThresholdLimitsArray['maxCartItemLimit'])
                && ($quoteItemsCount >= $cartThresholdLimitsArray['maxCartItemLimit']) && empty($paramEdit)) {
                $redirectUrl = $this->checkoutCartHelper->getCartUrl();
                $controller = $observer->getControllerAction();
                $this->redirect->redirect($controller->getResponse(), $redirectUrl);
            }

            return;
        }

        $restrictedRoutes = [];
        $isLoggedIn = $this->deliveryHelper->isCommercialCustomer();
        if ($isLoggedIn) {
            $restrictedRoutes = [
                'iframe_index_index',
                'configurator_index_index',
                'catalogsearch_result_index',
                'catalog_category_view',
                'catalog_product_view',
                'checkout_cart_add'
            ];
        } else {
            $restrictedRoutes = [
                'iframe_index_index',
                'configurator_index_index'
            ];
        }

        $paramEdit = $this->request->getparam('edit');
        //Get Threshold and Max Cart limit
        $cartThresholdLimitsArray = $this->cartDataHelper->getMaxCartLimitValue();
        // Get number of current quote line items.
        $quoteItemsCount = $this->checkoutCartHelper->getItemsCount();

        if (!empty($cartThresholdLimitsArray['maxCartItemLimit'])
            && ($quoteItemsCount >= $cartThresholdLimitsArray['maxCartItemLimit'])
        ) {
            $actionFullName = trim($this->request->getFullActionName());

            if (in_array($actionFullName, $restrictedRoutes) && empty($paramEdit)) {
                $redirectUrl = $this->checkoutCartHelper->getCartUrl();
                $controller = $observer->getControllerAction();
                $this->redirect->redirect($controller->getResponse(), $redirectUrl);
            }
        }
    }
}
