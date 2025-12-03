<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\Escaper;

class Index extends \Magento\Framework\App\Action\Action
{
    // warning flag
    public const ACCOUNT = 'account';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RedirectFactory
     */
    protected $resRedirectFactory;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Http $request
     * @param Cart $cart
     * @param Session $checkoutSession
     * @param RedirectFactory $resultRedirectFactory
     * @param FXORate $fxoRateHelper
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        protected Http $request,
        protected Cart $cart,
        protected Session $checkoutSession,
        RedirectFactory $resultRedirectFactory,
        protected FXORate $fxoRateHelper,
        protected Escaper $escaper
    ) {
        $this->resRedirectFactory = $resultRedirectFactory;
        return parent::__construct($context);
    }

    /**
     * Promotion banner coupon apply.
     *
     * @return Object
     */
    public function execute()
    {
        $couponCode = $this->request->getParam('code');
        $this->cart->getQuote()->setCouponCode($couponCode)->save();
        
        $this->checkoutSession->setIsApplyCoupon(true);
        $resultRedirect = $this->resRedirectFactory->create();
        $resultRedirect->setPath($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
