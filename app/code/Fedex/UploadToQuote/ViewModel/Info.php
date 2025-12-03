<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\UploadToQuote\ViewModel;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Framework\Phrase;
use Magento\NegotiableQuote\Model\Company\DetailsProviderFactory;
use Magento\NegotiableQuote\Model\Creator;

class Info implements ArgumentInterface
{

    /**
     * @param AdminConfigHelper $adminConfigHelper
     * @param Quote $quoteHelper
     * @param DetailsProviderFactory $companyDetailsProviderFactory
     * @param Creator $creator
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper,
        protected Quote $quoteHelper,
        protected DetailsProviderFactory $companyDetailsProviderFactory,
        protected Creator $creator
    ) {
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

    /**
    * Toggle for MazegeeksD234006Adminquoteissueforfusebidding
    *
    * @return boolean
    */
    public function isMazegeeksD234006Adminquoteissueforfusebidding()
    {
       return $this->adminConfigHelper->isMazegeeksD234006Adminquoteissueforfusebidding();
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
        if ($negotiableQuote->getCreatorId() == 0) {
            return $createdBy = __(
                '%customer',
                ['customer' => $customerName]
            );
        }
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
        return $createdBy;
    }
}