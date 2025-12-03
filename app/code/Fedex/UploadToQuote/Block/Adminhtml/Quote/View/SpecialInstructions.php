<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Block\Adminhtml\Quote\View;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class SpecialInstructions extends Template
{

    /**
     * Constructor
     *
     * @param Context $context
     * @param CartRepositoryInterface $quoteRepository
     * @param AdminConfigHelper $adminConfigHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected CartRepositoryInterface $quoteRepository,
        protected AdminConfigHelper $adminConfigHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Quote ID
     *
     * @return int|null
     */
    public function getQuoteId()
    {
        try {
            $quoteId = $this->getRequest()->getParam('quote_id');
            return $quoteId ? (int)$quoteId : null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

     /**
      * Get Special instructions
      *
      * @return array
      */
    public function getSpecialInstructions()
    {
        $quoteId = $this->getQuoteId();
        if (!$quoteId) {
            return [];
        }

        try {
            $quote = $this->quoteRepository->get($quoteId);
            $itemsData = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                $productJson= $this->adminConfigHelper->getProductJson($item, $quote->getId());
                $specialInstruction = $this->adminConfigHelper->isProductLineItems($productJson, true);
                $itemsData[] = [
                    'title' => $item->getName(),
                    'sku' => $item->getSku(),
                    'details'=>$specialInstruction
                ];
            }

            return $itemsData;
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     * Get Special instructions
     *
     * @return boolean
     */
    public function isEnhancementToggleEnabled()
    {
        return $this->adminConfigHelper->isMagentoQuoteDetailEnhancementToggleEnabled();
    }
}
