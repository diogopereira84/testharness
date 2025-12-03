<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\ExpiredItems\Observer\Frontend\Checkout;

use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Context as AuthContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * Class ExpiredCheckout
 *
 * This class is responsible for redirecting checkout page to cart page if expired item exist in cart
 */
class ExpiredCheckout implements ObserverInterface
{
    /**
     * Constructor function
     *
     * @param ExpiredItem $expiredItemHelper
     * @param ResponseFactory $responseFactory
     * @param UrlInterface $urlInterface
     * @param HttpContext $httpContext
     * @param CustomerSession $customerSession
     * @param AuthHelper $authHelper
     */
    public function __construct(
        protected ExpiredItem $expiredItemHelper,
        protected ResponseFactory $responseFactory,
        protected UrlInterface $urlInterface,
        private HttpContext $httpContext,
        protected CustomerSession $customerSession,
        protected AuthHelper $authHelper
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
        if ($this->authHelper->isLoggedIn()) {
            $expiredItems = $this->expiredItemHelper->getExpiredInstanceIds();
            if (!empty($expiredItems)) {
                $redirectionUrl = $this->urlInterface->getUrl('checkout/cart/index');
                $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
            }
        }

        return $this;
    }
}
