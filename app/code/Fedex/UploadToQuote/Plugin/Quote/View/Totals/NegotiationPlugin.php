<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Plugin\Quote\View\Totals;

use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals\Negotiation;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class NegotiationPlugin
{

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        private RequestInterface $request,
        private CartRepositoryInterface $quoteRepository
    )
    {
    }

    /**
     * Get Quote ID
     *
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        return (int)$this->request->getParam('quote_id') ?: null;
    }

    /**
     * Modify the options returned by the getTotalOptions method.
     *
     * @param Negotiation $subject
     * @param array $result
     * @return array
     */
    public function afterGetTotalOptions(Negotiation $subject, $result)
    {
        $quoteId = $this->getQuoteId();
        $quote = $this->quoteRepository->get($quoteId);
        $result['amount'] = new \Magento\Framework\DataObject([
            'label' => __('Amount Discount'),
            'is_price' => true,
            'value' => $quote->getDiscount()
        ]);

        return $result;
    }
}
