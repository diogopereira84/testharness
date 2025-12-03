<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Quote\Model\QuoteFactory;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;

class QuoteHistory implements ArgumentInterface
{
    /**
     * Initializing constructor
     *
     * @param AdminConfigHelper $adminConfigHelper
     * @param PriceHelper $priceHelper
     * @param QuoteFactory $quoteFactory
     * @param DeliveryDataHelper $deliveryDataHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper,
        protected PriceHelper $priceHelper,
        protected QuoteFactory $quoteFactory,
        protected DeliveryDataHelper $deliveryDataHelper,
        protected StoreManagerInterface $storeManager,
        protected CustomerSession $customerSession
    )
    {
    }

    /**
     * Get expiry date
     *
     * @param int $quoteId
     * @param string $format
     * @return string
     */
    public function getExpiryDate($quoteId, $format, $quote = null)
    {
        return $this->adminConfigHelper->getExpiryDate($quoteId, $format, $quote);
    }

    /**
     * Get formatted date
     *
     * @param string $dateString
     * @return string
     */
    public function getFormattedDate($dateString)
    {
        return $this->adminConfigHelper->getFormattedDate($dateString);
    }

    /**
     * Get formatted price
     *
     * @param string $price
     * @param int $quoteId
     * @return string
     */
    public function getFormattedPrice($price, $quoteId)
    {
        if($this->isToggleD206707Enabled() && is_object($quoteId)) {
            $quote = $this->quoteFactory->create()->load($quoteId->getEntityId());
        } else {
            $quote = $this->quoteFactory->create()->load($quoteId);
        }
        $formattedPrice = '$--.--';
        if (!$this->adminConfigHelper->checkoutQuotePriceisDashable($quote)) {
            $formattedPrice = $this->priceHelper->currency($price, true, false);
        }

        return $formattedPrice;
    }

    /**
     * Get status label
     *
     * @param int $quoteId
     * @return string
     */
    public function getStatusLabel($quoteId)
    {
        return $this->adminConfigHelper->getNegotiableQuoteStatus($quoteId);
    }

    /**
     * Get data by status label
     *
     * @param string $statusLabel
     * @return array
     */
    public function getDataByStatusLebel(string $statusLabel)
    {
        if (strtolower($statusLabel) == 'set to expire') {
            $data = [
                'dotIconClass' => 'set-to-expire',
                'linkText' => 'Review'
            ];
        } elseif (strtolower($statusLabel) == 'ready for review') {
            $data = [
                'dotIconClass' => 'ready-for-review',
                'linkText' => 'Review'
            ];
        } else {
            $data = [
                'dotIconClass' => '',
                'linkText' => 'View'
            ];
        }

        return $data;
    }

    /**
     * Get quote status label by key
     *
     * @param string $key
     * @param string $createdAt
     * @return string
     */
    public function getQuoteStatusLabel($key, $createdAt)
    {
        return $this->adminConfigHelper->getQuoteStatusLabel($key, $createdAt);
    }

    /**
     * Check if customer is Epro Customer or not
     *
     * @return bool
     */
    public function isEproCustomer()
    {
        return $this->deliveryDataHelper->isEproCustomer();
    }

    /**
     * Get Upload to quote toggle value
     *
     * @return boolean
     */
    public function isUploadToQuoteEnable()
    {
        $companyData = $this->customerSession->getOndemandCompanyInfo();
        $companyId = (isset($companyData['company_id']) && !empty($companyData['company_id']))
        ? trim($companyData['company_id']) : null;

        return $this->adminConfigHelper
        ->isUploadToQuoteEnable($this->storeManager->getStore()->getId(), $companyId);
    }

    /**
     * Change my quotes url if uploadtoquote feature is enable.
     *
     * @return string
     */
    public function myQuotesAccountNavigationUrl()
    {
        if ($this->isUploadToQuoteEnable() || $this->isEproCustomer()) {
            return 'uploadtoquote/index/quotehistory';
        }

        return "negotiable_quote/quote";
    }

    /**
     * Check if D-206707 toggle is enabled
     * @return bool
     */
    public function isToggleD206707Enabled()
    {
        return $this->adminConfigHelper->isToggleD206707Enabled();
    }

    /**
    * Toggle for upload to quote submit date
    *
    * @return boolean
    */
    public function toggleUploadToQuoteSubmitDate()
    {
       return $this->adminConfigHelper->toggleUploadToQuoteSubmitDate();
    }

    /**
     * Get submit date
     *
     * @param int $quoteId
     * @return string
     */
    public function getSubmitDate($quoteId)
    {
        return $this->adminConfigHelper->getSubmitDate($quoteId);
    }
}
