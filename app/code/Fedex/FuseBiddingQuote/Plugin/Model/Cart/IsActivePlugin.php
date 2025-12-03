<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FuseBiddingQuote\Plugin\Model\Cart;

use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\UploadToQuote\Helper\AdminConfigHelper as UploadToQuoteAdminConfigHelper;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin class to return is_active as true when negotiable quote is created for cart
 */
class IsActivePlugin
{
    /**
     * Initializing Constructor
     *
     * @param FuseBidViewModel $fuseBidViewModel
     * @param UploadToQuoteAdminConfigHelper $uploadToQuoteAdminConfigHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected FuseBidViewModel $fuseBidViewModel,
        protected UploadToQuoteAdminConfigHelper $uploadToQuoteAdminConfigHelper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Make cart active when loadCartByQuoteId API is called
     *
     * @param object $subject
     * @param object $result
     * @param CartInterface $cart
     * @return boolean
     */
    public function afterExecute($subject, $result, CartInterface $cart)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__
        .' : Making cart as active with quote id : '.$cart->getId().' and is_bid flag as '.$cart->getIsBid());
        if (!$result
            && $cart->getIsBid()
            && $this->uploadToQuoteAdminConfigHelper->isQuoteNegotiated($cart->getId())
            && $this->fuseBidViewModel->isFuseBidToggleEnabled()
        ) {
            $result = true;
        }

        return $result;
    }
}
