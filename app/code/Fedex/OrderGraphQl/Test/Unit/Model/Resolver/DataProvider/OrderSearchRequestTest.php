<?php

declare(strict_types=1);

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\DataProvider;

use Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderSearchRequest;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderSearchRequestTest extends TestCase
{
    private OrderSearchRequest $orderSearchRequest;

    private MockObject $searchCriteriaBuilder;
    private MockObject $orderRepository;
    private MockObject $sortOrderBuilder;
    private MockObject $timezone;
    private MockObject $collectionFactory;
    private MockObject $toggleConfig;
    private array $filters = [];

    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->orderSearchRequest = new OrderSearchRequest(
            $this->searchCriteriaBuilder,
            $this->orderRepository,
            $this->sortOrderBuilder,
            $this->timezone,
            $this->collectionFactory,
            $this->toggleConfig,
            $this->filters
        );
    }

    public function testOrderSearchRequestReturnsOrders(): void
    {
        $args = [
            'filters' => [
                'contact' => [
                    'emailDetail' => ['emailAddress' => 'test@example.com']
                ]
            ],
            'sorts' => [
                ['attribute' => 'submissionTimeDateRange', 'ascending' => true]
            ]
        ];
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchCriteria->method('getFilterGroups')->willReturn([]);
        $searchCriteria->method('setPageSize')->willReturnSelf();
        $searchCriteria->method('setCurrentPage')->willReturnSelf();

        $searchResult = $this->createMock(OrderSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn(['order1', 'order2']);

        // Setup toggleConfig mock
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        // Create mock search criteria builder chain
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        // Final search results from repository
        $this->orderRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);

        $result = $this->orderSearchRequest->orderSearchRequest($args);

        $this->assertIsArray($result);
        $this->assertEquals(['order1', 'order2'], $result['orders']);
        $this->assertFalse($result['partial']);
    }
}
