<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Block;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\UploadToQuote\Model\QuoteHistory\GetAllQuotes;
use Magento\Theme\Block\Html\Pager;

/**
 * QuoteHistory Block class
 */
class QuoteHistory extends Template
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param GetAllQuotes $getAllQuotes
     */
    public function __construct(
        Context $context,
        protected RequestInterface $request,
        protected GetAllQuotes $getAllQuotes
    ) {
        parent::__construct($context);
    }

    /**
     * Prepare layout for template.
     *
     * @return QuoteHistory
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $getAllNegotiableQuote = $this->getAllNegotiableQuote();
        if ($getAllNegotiableQuote) {
            $pager = $this->getLayout()->createBlock(Pager::class, 'quotes.history.pager');
            $pager->setCollection($this->getAllNegotiableQuote());
            $this->setChild('pager', $pager);
        }

        return $this; 
    }

    /**
     * To get current url
     */
    public function getCurrentUrl()
    {
        return $this->getUrl(
            'uploadtoquote/index/quotehistory',
            ['_current' => true, '_use_rewrite' => true]
        );
    }

    /**
     * Get all negotiable quote
     *
     * @return QuoteHistory
     */
    public function getAllNegotiableQuote()
    {
        return $this->getAllQuotes->getAllNegotiableQuote();
    }

    /**
     * Check negotialble quotes are created or not
     *
     * @return boolean
     */
    public function isNegotiableQuotesCreated()
    {
        $isnegotiableQuotesCreated = false;
        if (count($this->getAllQuotes->getAllNegotiableQuote(true))) {
            $isnegotiableQuotesCreated = true;
        }

        return $isnegotiableQuotesCreated;
    }
}
