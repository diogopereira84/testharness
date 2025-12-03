<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\IntegrationNote\Command;

use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetList;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote\Collection as IntegrationNoteCollection;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote\CollectionFactory as IntegrationNoteCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetListTest extends TestCase
{
    /**
     * @var MockObject|SearchResultsFactory
     */
    private MockObject|SearchResultsFactory $searchResultsFactoryMock;

    /**
     * @var MockObject|IntegrationNoteCollectionFactory
     */
    private MockObject|IntegrationNoteCollectionFactory $collectionFactoryMock;

    /**
     * @var MockObject|CollectionProcessorInterface
     */
    private MockObject|CollectionProcessorInterface $collectionProcessorMock;

    /**
     * @var MockObject|SearchCriteriaInterface
     */
    private MockObject|SearchCriteriaInterface $criteriaMock;

    /**
     * @var MockObject|IntegrationNoteCollection
     */
    private MockObject|IntegrationNoteCollection $integrationNoteCollectionMock;

    /**
     * @var GetList
     */
    private GetList $getListCommand;

    protected function setUp(): void
    {
        $this->searchResultsFactoryMock = $this->createMock(SearchResultsFactory::class);
        $this->collectionFactoryMock = $this->createMock(IntegrationNoteCollectionFactory::class);
        $this->collectionProcessorMock = $this->createMock(CollectionProcessorInterface::class);
        $this->criteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->integrationNoteCollectionMock = $this->createMock(IntegrationNoteCollection::class);

        $this->getListCommand = new GetList(
            $this->searchResultsFactoryMock,
            $this->collectionFactoryMock,
            $this->collectionProcessorMock
        );
    }

    public function testExecute(): void
    {
        $this->collectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->integrationNoteCollectionMock);

        $this->collectionProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->criteriaMock, $this->integrationNoteCollectionMock);

        $this->integrationNoteCollectionMock
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $this->integrationNoteCollectionMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $searchResultsMock = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);

        $this->searchResultsFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchResultsMock);

        $searchResultsMock
            ->expects($this->once())
            ->method('setItems')
            ->with([]);

        $searchResultsMock
            ->expects($this->once())
            ->method('setTotalCount')
            ->with(0);

        $result = $this->getListCommand->execute($this->criteriaMock);

        $this->assertSame($searchResultsMock, $result);
    }
}
