<?php

declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\Model;

use Fedex\UploadToQuote\Model\NegotiableQuoteIntegration;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory as NegotiableQuoteCollectionFactory;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\Collection;
use PHPUnit\Framework\TestCase;

class NegotiableQuoteIntegrationTest extends TestCase
{
    public function testGetListReturnsExpectedSearchResults()
    {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $searchResults = $this->createMock(SearchResultsInterface::class);
        $searchResultsFactory = $this->createMock(SearchResultsInterfaceFactory::class);
        $negotiableQuoteCollectionFactory = $this->createMock(NegotiableQuoteCollectionFactory::class);
        $collection = $this->createMock(Collection::class);
        $select = $this->getMockBuilder('Magento\Framework\DB\Select')->disableOriginalConstructor()->getMock();

        $items = [['entity_id' => 1], ['entity_id' => 2]];

        // Mock factory create
        $negotiableQuoteCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        // Mock getTable
        $collection->expects($this->any())
            ->method('getTable')
            ->withConsecutive([
                NegotiableQuoteIntegration::QUOTE_INTEGRATION
            ], [
                NegotiableQuoteIntegration::NEGOTIABLE_QUOTE
            ])
            ->willReturnOnConsecutiveCalls('quote_integration_table', 'negotiable_quote_table');

        // Mock getSelect
        $collection->expects($this->once())
            ->method('getSelect')
            ->willReturn($select);

        // Add this after creating $select mock
        $select->expects($this->any())
            ->method('join')
            ->willReturnSelf();

        // Simulate collection getItems
        $collection->expects($this->once())
            ->method('getItems')
            ->willReturn($items);

        // Mock SearchResultsFactory create
        $searchResultsFactory->expects($this->once())
            ->method('create')
            ->willReturn($searchResults);

        // Mock SearchResultsInterface methods
        $searchResults->expects($this->once())
            ->method('setItems')
            ->with($items)
            ->willReturnSelf();
        $searchResults->expects($this->once())
            ->method('setTotalCount')
            ->willReturnSelf();
        $searchResults->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteria)
            ->willReturnSelf();

        $model = new NegotiableQuoteIntegration($searchResultsFactory, $negotiableQuoteCollectionFactory);
        $result = $model->getList($searchCriteria);

        $this->assertSame($searchResults, $result);
    }
}

