<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * EmailTemplate Block Class
 */
class EmailTemplate extends Template
{
    /**
     * @param Context $context
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected QuoteEmailHelper $quoteEmailHelper,
        protected StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get email template
     *
     * @param array $quoteData
     * @return string
     */
    public function getEmailTemplate($quoteData)
    {
        return $this->quoteEmailHelper->getEmailTemplate($quoteData);
    }

    /**
     * Get media path
     *
     * @return string
     */
    public function getMediaPath()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }
}
