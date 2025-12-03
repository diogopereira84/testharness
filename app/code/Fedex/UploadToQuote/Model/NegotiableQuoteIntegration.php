<?php
declare(strict_types=1);

namespace Fedex\UploadToQuote\Model;

use Fedex\UploadToQuote\Api\NegotiableQuoteIntegrationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory as NegotiableQuoteCollectionFactory;

/**
 * NegotiableQuoteIntegration Model
 *
 * Provides integration between quote_integration and negotiable_quote tables.
 */
class NegotiableQuoteIntegration implements NegotiableQuoteIntegrationInterface
{
    /**
     * Quote Integration table name
     */
    public const QUOTE_INTEGRATION = 'quote_integration';

    /**
     * Negotiable Quote table name
     */
    public const NEGOTIABLE_QUOTE = 'negotiable_quote';

    /**
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param NegotiableQuoteCollectionFactory $negotiableQuoteCollectionFactory
     */
    public function __construct(
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly NegotiableQuoteCollectionFactory $negotiableQuoteCollectionFactory
    ) {}

    /**
     * Retrieve list of negotiable quotes with integration info.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->negotiableQuoteCollectionFactory->create();

        $quoteIntegrationTable = $collection->getTable(self::QUOTE_INTEGRATION);
        $negotiableQuoteTable  = $collection->getTable(self::NEGOTIABLE_QUOTE);

        $collection->getSelect()->join(
            ['qi' => $quoteIntegrationTable],
            'main_table.entity_id = qi.quote_id',
            []
        )->join(
            ['nq' => $negotiableQuoteTable],
            'qi.quote_id = nq.quote_id',
            []
        );

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setSearchCriteria($searchCriteria);

        return $searchResults;
    }
}
