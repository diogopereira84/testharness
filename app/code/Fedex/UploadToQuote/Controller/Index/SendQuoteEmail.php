<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;

/**
 * SendQuoteEmail Controller class
 */
class SendQuoteEmail implements ActionInterface
{
    /**
     * SendQuoteEmail class constructor
     *
     * @param Context $context
     * @param QuoteEmailHelper $quoteEmailHelper
     */
    public function __construct(
        protected Context $context,
        protected QuoteEmailHelper $quoteEmailHelper
    )
    {
    }

    /**
     * To submit account request form data
     *
     * @return json|boolean
     */
    public function execute()
    {
        $status = $this->context->getRequest()->getParam('status');
        $quoteId= $this->context->getRequest()->getParam('quote_id');
        $quoteData=[
            'quote_id' => $quoteId,
            'status' => $status
        ];
        return $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
    }
}
