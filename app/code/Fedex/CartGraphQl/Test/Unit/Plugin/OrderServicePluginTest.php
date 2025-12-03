<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\CartGraphQl\Plugin\OrderServicePlugin;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\OrderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderServicePluginTest extends TestCase
{
    /**
     * @var MockObject|CartIntegrationNoteRepositoryInterface
     */
    protected MockObject|CartIntegrationNoteRepositoryInterface $integrationNoteRepositoryMock;

    /**
     * @var MockObject|SearchCriteriaBuilderFactory
     */
    protected MockObject|SearchCriteriaBuilderFactory $searchCriteriaBuilderFactoryMock;

    /**
     * @var MockObject|SearchCriteriaBuilder
     */
    protected MockObject|SearchCriteriaBuilder $searchCriteriaBuilderMock;

    /**
     * @var MockObject|OrderService
     */
    protected MockObject|OrderService $orderService;

    /**
     * @var OrderInterface|MockObject
     */
    protected OrderInterface|MockObject $orderMock;

    /**
     * @var SearchResultsInterface|MockObject
     */
    protected SearchResultsInterface|MockObject $searchResultsMock;

    /**
     * @var OrderServicePlugin
     */
    protected OrderServicePlugin $orderServicePlugin;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->integrationNoteRepositoryMock = $this->createMock(CartIntegrationNoteRepositoryInterface::class);
        $this->searchCriteriaBuilderFactoryMock = $this->createMock(SearchCriteriaBuilderFactory::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->orderService = $this->getMockBuilder(OrderService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderServicePlugin = new OrderServicePlugin(
            $this->integrationNoteRepositoryMock,
            $this->searchCriteriaBuilderMock
        );

        $quoteId = 1;
        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['addCommentToStatusHistory'])
            ->onlyMethods(['getQuoteId'])
            ->getMockForAbstractClass();

        $this->orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(1);

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('parent_id', $quoteId)
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->searchResultsMock = $this->createMock(SearchResultsInterface::class);
        $this->integrationNoteRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($this->searchResultsMock);
    }

    /**
     * @return void
     */
    public function testBeforePlace(): void
    {
        $noteText = 'Some note text';
        $noteData = ['text' => $noteText];
        $noteJson = json_encode($noteData);

        $noteMock = $this->createMock(\Fedex\Cart\Api\Data\CartIntegrationNoteInterface::class);
        $noteMock->expects($this->exactly(2))
            ->method('getNote')
            ->willReturn($noteJson);
        $this->searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$noteMock]);
        $this->orderMock->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with($noteText);

        $result = $this->orderServicePlugin->beforePlace($this->orderService, $this->orderMock);

        $this->assertEquals([$this->orderMock], $result);
    }

    /**
     * @return void
     */
    public function testBeforePlaceWithNoNotes(): void
    {
        $this->searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $result = $this->orderServicePlugin->beforePlace($this->orderService, $this->orderMock);

        $this->assertEquals([$this->orderMock], $result);
    }

    /**
     * @return void
     */
    public function testBeforePlaceWithArrayListNotes(): void
    {
        $noteText = 'Some note text';
        $noteData = [0 =>['text' => $noteText]];
        $noteJson = json_encode($noteData);

        $noteMock = $this->createMock(\Fedex\Cart\Api\Data\CartIntegrationNoteInterface::class);
        $noteMock->expects($this->exactly(2))
            ->method('getNote')
            ->willReturn($noteJson);
        $this->searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$noteMock]);
        $this->orderMock->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with($noteText);

        $result = $this->orderServicePlugin->beforePlace($this->orderService, $this->orderMock);

        $this->assertEquals([$this->orderMock], $result);
    }
}
