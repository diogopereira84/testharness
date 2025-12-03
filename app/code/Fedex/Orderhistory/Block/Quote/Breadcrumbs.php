<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

/* B-B-1060636 - RT-ECVS- Quote Order Details */

namespace Fedex\Orderhistory\Block\Quote;

use Magento\Framework\View\Element\Template\Context;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Fedex\Orderhistory\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Breadcrumbs extends \Magento\Framework\View\Element\Template
{
    /**
     * @param Context $context
     * @param OrderRepositoryInterface $quoteRepository
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        public NegotiableQuoteRepositoryInterface $quoteRepository,
        public Data $helper,
        public StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Set Page Title
     *
     * @codeCoverageIgnore
     */
    protected function _prepareLayout()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        if ($this->helper->isModuleEnabled() && $quoteId) {
            $quoteData = $this->quoteRepository->getById($quoteId);
            $quoteName = $quoteData->getQuoteName();
            $this->pageConfig->getTitle()->set($quoteName);
        }
        return parent::_prepareLayout();
    }

    /**
     * Retrieve BreadCrumbs on Quote View Page
     */
    public function getBreadcrumbs()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        if ($this->helper->isModuleEnabled() && $quoteId) {

            $quoteListUrl = $this->storeManager->getStore()->getBaseUrl()
                            .'negotiable_quote/quote';
            $quoteData = $this->quoteRepository->getById($quoteId);
            $quoteName = $quoteData->getQuoteName();
            
            $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
            $breadcrumbs->addCrumb(
                'quote_view',
                [
                    'label'=>'My Quotes',
                    'title'=>'My Quotes',
                    'link' =>$quoteListUrl
                ]
            );
            if ($this->helper->isEnhancementEnabeled()) {
                $breadcrumbs->addCrumb(
                    'quote_id',
                    [
                        'label'=>$quoteId.'-SEPO'
                    ]
                );
            } else {
                $breadcrumbs->addCrumb(
                    'quote_id',
                    [
                        'label'=>$quoteName
                    ]
                );
            }
            
            return $this->getLayout()->getBlock('breadcrumbs')->toHtml();
        }
    }
}
