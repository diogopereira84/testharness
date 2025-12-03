<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\OrderApprovalB2b\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper as OrderApprovalAdminConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * View model class for review order
 */
class ReviewOrderViewModel implements ArgumentInterface
{
    /**
     * Initializing constructor
     *
     * @param RevieworderHelper $revieworderHelper
     * @param OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper
     * @param CustomerSession $customerSession
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        protected RevieworderHelper $revieworderHelper,
        protected OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper,
        protected CustomerSession $customerSession,
        protected Session $checkoutSession,
        protected CartRepositoryInterface $quoteRepository
    )
    {
    }

    /**
     * Get formatted price
     *
     * @param string $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->revieworderHelper->getFormattedPrice($price);
    }

    /**
     * Get formatted date
     *
     * @param string $dateString
     * @param string $format
     * @return string
     */
    public function getFormattedDate($dateString, $format = 'm/d/Y')
    {
        return $this->revieworderHelper->getFormattedDate($dateString, $format);
    }

    /**
     * Check Order Approval B2B is enabled or not
     *
     * @return boolean
     */
    public function isOrderApprovalB2bEnabled()
    {
        return $this->orderApprovalAdminConfigHelper->isOrderApprovalB2bEnabled();
    }

    /**
     * Check is review action is set or not
     *
     * @return boolean
     */
    public function checkIsReviewActionSet()
    {
        return $this->orderApprovalAdminConfigHelper->checkIsReviewActionSet();
    }

    /**
     * Unset success toast msg.
     *
     * @return void
     */
    public function unsetSuccessErrorData()
    {
        $this->customerSession->unsSuccessErrorData();
    }
    
    /**
     * Get success toast msg.
     *
     * @return void
     */
    public function getSuccessErrorData()
    {
        return $this->customerSession->getSuccessErrorData() ?? '';
    }

    /**
     * Check if user has a permission for review orders
     *
     * @return boolean
     */
    public function checkIfUserHasReviewOrderPermission()
    {
        return $this->revieworderHelper->checkIfUserHasReviewOrderPermission();
    }

    /**
     * Get quote object
     *
     * @param int|string $quoteId
     * @return object
     */
    public function getPendingOrderQuoteId()
    {
        return $this->checkoutSession->getPendingOrderQuoteId();
    }

    /**
     * Unset Quote Id from checkout session.
     *
     * @return void
     */
    public function unsetPendingOrderQuoteId()
    {
        $this->checkoutSession->unsPendingOrderQuoteId();
    }

    /**
     * Get quote object
     *
     * @param int|string $quoteId
     * @return object
     */
    public function getQuoteObj($quoteId)
    {
        return $this->quoteRepository->get($quoteId);
    }
}
