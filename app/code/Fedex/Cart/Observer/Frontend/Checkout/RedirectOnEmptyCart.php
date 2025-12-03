<?php
namespace Fedex\Cart\Observer\Frontend\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\UrlInterface;

class RedirectOnEmptyCart implements ObserverInterface
{
    public function __construct(
        protected Cart $cart,
        protected UrlInterface $url
    )
    {
    }

    public function execute(Observer $observer)
    {
        if ($this->cart->getQuote()->getItemsCount() == 0) {
            $url = $this->url->getUrl('checkout/cart');
            $controller = $observer->getControllerAction();
            $controller->getResponse()->setRedirect($url);
        }
    }
}