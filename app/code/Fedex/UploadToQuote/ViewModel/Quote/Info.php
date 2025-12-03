<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\ViewModel\Quote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Phrase;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Company\DetailsProviderFactory;
use Magento\NegotiableQuote\Model\Creator;

/**
 * Provide quote information
 */
class Info extends \Magento\NegotiableQuote\ViewModel\Quote\Info
{
    /**
     * @param DetailsProviderFactory $companyDetailsProviderFactory
     * @param Quote $quoteHelper
     * @param Creator $creator
     */
    public function __construct(
        private DetailsProviderFactory $companyDetailsProviderFactory,
        private Quote $quoteHelper,
        private Creator $creator
    ) {
    }

    /**
     * Get quote creator
     *
     * @return Phrase|string
     */
    public function getQuoteCreatedBy(): Phrase|string
    {
        $quote = $this->quoteHelper->resolveCurrentQuote();
        /** @var \Magento\NegotiableQuote\Model\Company\DetailsProvider $companyDetailsProvider */
        $companyDetailsProvider = $this->companyDetailsProviderFactory->create(['quote' => $quote]);
        $createdBy = $customerName = $companyDetailsProvider->getQuoteOwnerName();
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($negotiableQuote->getCreatorId() !== 0) {
            if (in_array(
                $negotiableQuote->getCreatorType(),
                [UserContextInterface::USER_TYPE_ADMIN, UserContextInterface::USER_TYPE_INTEGRATION]
            )) {
                $creatorName = $this->creator->retrieveCreatorName(
                    $negotiableQuote->getCreatorType(),
                    $negotiableQuote->getCreatorId(),
                    $negotiableQuote->getQuoteId()
                );
                $createdBy = __(
                    '%creator for %customer',
                    ['creator' => $creatorName, 'customer' => $customerName]
                );
            }
        }
        return $createdBy;
    }
}
