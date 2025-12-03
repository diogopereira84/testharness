<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Plugin\Checkout\Model;

use Magento\Checkout\Model\Cart as CheckoutCart;
use Psr\Log\LoggerInterface;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\UploadToQuote\Helper\QueueHelper;

/**
 * Cart plugin class for declined quote when all items are deleted
 */
class Cart
{
    public const DECLINED_REASON = 'Declined on delete all items';

    /**
     * Cart Constructor
     *
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param QueueHelper $queueHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected QueueHelper $queueHelper,
        protected LoggerInterface $logger
    )
    {
    }
    
    /**
     * Decline quote after delete all items
     *
     * @param object $subject
     * @param object $result
     * @return object
     */
    public function afterRemoveItem(CheckoutCart $subject, $result)
    {
        try {
            if ($this->uploadToQuoteViewModel->isMarkAsDeclinedEnabled()
            && $this->uploadToQuoteViewModel->isUploadToQuoteEnable()
            && !count($subject->getQuote()->getAllVisibleItems())) {
                $quoteId = $subject->getQuote()->getId();
                $this->declineQuote($quoteId);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Quote is not declined on item deletion : ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Decline quote after clear cart items
     *
     * @param object $subject
     * @param object $result
     * @return object
     */
    public function afterTruncate(CheckoutCart $subject, $result)
    {
        try {
            if ($this->uploadToQuoteViewModel->isMarkAsDeclinedEnabled()
            && $this->uploadToQuoteViewModel->isUploadToQuoteEnable()) {
                $quoteId = $subject->getQuote()->getId();
                $this->declineQuote($quoteId);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Quote is not declined on cart truncate : ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Decline quote by id
     *
     * @param int $quoteId
     * @return void
     */
    public function declineQuote($quoteId)
    {
        $this->queueHelper->updateQuoteStatusByKey($quoteId);
    }
}
