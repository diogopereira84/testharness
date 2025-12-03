<?php
namespace Fedex\SDE\Plugin\Checkout\Model;

use Fedex\SDE\Helper\SdeHelper;
use Magento\Checkout\Model\Cart as CartModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote as MagentoQuote;

class Cart
{
    /**
     * Cart constructor
     *
     * @param CheckoutSession $checkoutSession
     * @param SdeHelper $sdeHelper
     * @return void
     */
    public function __construct(
        protected CheckoutSession $checkoutSession,
        protected SdeHelper $sdeHelper
    )
    {
    }

    /**
     * Always return quote from checkout session
     * D-85079 : SDE: Cart is showing 0(zero) when logged in
     *
     * @param CartModel
     * @param MagentoQuote
     * @return MagentoQuote
     */
    public function afterGetQuote(CartModel $subject, MagentoQuote $result)
    {
        if ($this->sdeHelper->getIsSdeStore()) {
            return $this->checkoutSession->getQuote();
        }

        return $result;
    }
}
