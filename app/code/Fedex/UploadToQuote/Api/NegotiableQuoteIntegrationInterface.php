<?php
declare(strict_types=1);

namespace Fedex\UploadToQuote\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for NegotiableQuoteIntegration service
 *
 * Provides method to retrieve negotiable quotes that exist in quote_integration.
 */
interface NegotiableQuoteIntegrationInterface
{
    /**
     * Get negotiable quotes that exist in quote_integration
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
