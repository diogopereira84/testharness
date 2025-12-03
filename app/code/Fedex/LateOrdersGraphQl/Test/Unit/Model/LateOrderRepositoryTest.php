<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model;

use Fedex\LateOrdersGraphQl\Model\LateOrderRepository;
use Fedex\LateOrdersGraphQl\Model\OrderDetailsAssembler;
use Fedex\LateOrdersGraphQl\Api\ConfigInterface;
use Fedex\LateOrdersGraphQl\Api\Data\LateOrderSearchResultDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSummaryDTO;
use Fedex\LateOrdersGraphQl\Model\Data\PageInfoDTO;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSearchResultDTO;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderQueryParamsDTO;
use Fedex\LateOrdersGraphQl\Model\Service\WindowResolverService;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory as OrderGridCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class LateOrderRepositoryTest extends TestCase
{
    private $orderGridCollectionFactory;
    private $config;
    private $orderRepository;
    private $searchCriteriaBuilder;
    private $orderDetailsAssembler;
    private $windowResolverService;
    private $repository;

    protected function setUp(): void
    {
        $this->orderGridCollectionFactory = $this->createMock(OrderGridCollectionFactory::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->orderDetailsAssembler = $this->createMock(OrderDetailsAssembler::class);
        $this->windowResolverService = $this->createMock(WindowResolverService::class);
        $this->repository = new LateOrderRepository(
            $this->orderGridCollectionFactory,
            $this->config,
            $this->orderRepository,
            $this->searchCriteriaBuilder,
            $this->orderDetailsAssembler,
            $this->windowResolverService
        );
    }

    public function testGetListThrowsOnInvalidSinceDate()
    {
        $this->orderGridCollectionFactory->method('create')->willReturn($this->getMockBuilder('stdClass')->addMethods(['addFieldToSelect','addFieldToFilter','setOrder','setCurPage','setPageSize','getSize'])->getMock());
        $this->windowResolverService->method('resolveAndCapWindow')->willThrowException(new LocalizedException(__('Invalid since date')));
        $this->expectException(LocalizedException::class);
        $params = new LateOrderQueryParamsDTO('invalid-date', null);
        $this->repository->getList($params);
    }

    public function testGetListThrowsOnInvalidUntilDate()
    {
        $this->orderGridCollectionFactory->method('create')->willReturn($this->getMockBuilder('stdClass')->addMethods(['addFieldToSelect','addFieldToFilter','setOrder','setCurPage','setPageSize','getSize'])->getMock());
        $this->windowResolverService->method('resolveAndCapWindow')->willThrowException(new LocalizedException(__('Invalid until date')));
        $this->expectException(LocalizedException::class);
        $params = new LateOrderQueryParamsDTO(null, 'invalid-date');
        $this->repository->getList($params);
    }

    public function testGetListReturnsResult()
    {
        $collection = $this->getMockBuilder('stdClass')
            ->addMethods(['addFieldToSelect','addFieldToFilter','setOrder','setCurPage','setPageSize','getSize','getItems'])
            ->getMock();
        $collection->method('addFieldToSelect')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('setCurPage')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getSize')->willReturn(1);
        $order = $this->getMockBuilder(OrderInterface::class)
            ->onlyMethods(['getIncrementId','getCreatedAt','getStatus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $order->method('getIncrementId')->willReturn('1000001');
        $order->method('getCreatedAt')->willReturn('2025-10-01 12:00:00');
        $order->method('getStatus')->willReturn('new');
        $collection->method('getItems')->willReturn([$order]);
        $this->orderGridCollectionFactory->method('create')->willReturn($collection);
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(1);
        $this->config->method('getLateOrderQueryMaxPagination')->willReturn(100);
        $this->config->method('getLateOrderQueryDefaultPagination')->willReturn(50);

        $since = '2025-09-30T12:00:00Z';
        $until = '2025-10-01T12:00:00Z';
        $sinceDt = new \DateTimeImmutable($since);
        $untilDt = new \DateTimeImmutable($until);
        $this->windowResolverService->method('resolveAndCapWindow')
            ->willReturn(new \Fedex\LateOrdersGraphQl\Model\Data\TimeWindowDTO($sinceDt, $untilDt));

        $expectedFromDt = $sinceDt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $collection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['created_at', ['gteq' => $expectedFromDt]],
            )
            ->willReturnSelf();

        $params = new LateOrderQueryParamsDTO($since, null, 'new', true, 1, 10);
        $result = $this->repository->getList($params);
        $this->assertInstanceOf(LateOrderSearchResultDTOInterface::class, $result);
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertInstanceOf(PageInfoDTO::class, $result->getPageInfo());
        $items = $result->getItems();
        $this->assertNotEmpty($items);
        $this->assertInstanceOf(LateOrderSummaryDTO::class, $items[0]);
        $this->assertEquals('1000001', $items[0]->getOrderId());
        $this->assertEquals('2025-10-01 12:00:00', $items[0]->getCreatedAt());
        $this->assertEquals('new', $items[0]->getStatus());
        $this->assertTrue($items[0]->getIs1p());
    }

    public function testGetByIdThrowsIfNotFound()
    {
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);
        $this->orderRepository->method('getList')->willReturn(new class {
            public function getItems() { return []; }
        });
        $this->expectException(LocalizedException::class);
        $this->repository->getById('notfound');
    }

    public function testGetByIdReturnsOrderDetails()
    {
        $this->searchCriteriaBuilder->method('addFilter')
            ->with('increment_id', '1000001')
            ->willReturnSelf();
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->orderRepository->method('getList')->willReturn(new class($order) {
            private $order;
            public function __construct($order) { $this->order = $order; }
            public function getItems() { return [$this->order]; }
        });
        $details = $this->createMock(OrderDetailsDTOInterface::class);
        $this->orderDetailsAssembler->method('assemble')->willReturn($details);
        $result = $this->repository->getById('1000001');
        $this->assertInstanceOf(OrderDetailsDTOInterface::class, $result);
    }
}
