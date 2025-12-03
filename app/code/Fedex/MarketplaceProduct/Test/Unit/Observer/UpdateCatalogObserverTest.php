<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Observer;

use Fedex\MarketplaceProduct\Observer\UpdateCatalogObserver;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\SharedCatalog\Model\Management as SharedCatalogManagement;
use Magento\SharedCatalog\Model\ProductManagement;
use Magento\SharedCatalog\Model\State as SharedCatalogState;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateCatalogObserverTest extends TestCase
{
    private ProductRepository $productRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private FilterBuilder $filterBuilder;
    private ProductManagement $sharedCatalog;
    private SharedCatalogState $sharedCatalogState;
    private SharedCatalogManagement $sharedCatalogManagement;
    private LoggerInterface $logger;
    private MarketplaceCheckoutHelper $marketplaceCheckoutHelper;
    private UpdateCatalogObserver $observer;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);
        $this->sharedCatalog = $this->createMock(ProductManagement::class);
        $this->sharedCatalogState = $this->createMock(SharedCatalogState::class);
        $this->sharedCatalogManagement = $this->createMock(SharedCatalogManagement::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);

        $this->observer = new UpdateCatalogObserver(
            $this->productRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder,
            $this->sharedCatalog,
            $this->sharedCatalogState,
            $this->sharedCatalogManagement,
            $this->logger,
            $this->marketplaceCheckoutHelper
        );
    }

    public function testExecuteWhenToggleDisabled(): void
    {
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->productRepository->expects($this->never())->method('getList');

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBunch'])
            ->getMock();

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();
        $observerMock->method('getEvent')->willReturn($eventMock);

        $this->observer->execute($observerMock);
    }

    public function testExecuteAssignsProductsToSharedCatalog(): void
    {
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $productMock1 = $this->createMock(Product::class);
        $productMock2 = $this->createMock(Product::class);

        $filterMock = new \Magento\Framework\DataObject();
        $this->filterBuilder->method('setField')->willReturnSelf();
        $this->filterBuilder->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->method('setValue')->willReturnSelf();
        $this->filterBuilder->method('create')->willReturn($filterMock);

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteriaMock);

        $this->productRepository->method('getList')->with($searchCriteriaMock)->willReturn(
            new \Magento\Framework\DataObject(['items' => [$productMock1, $productMock2]])
        );

        $sharedCatalogMock = $this->createConfiguredMock(
            \Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class,
            ['getId' => 123]
        );
        $this->sharedCatalogManagement->method('getPublicCatalog')->willReturn($sharedCatalogMock);

        $this->sharedCatalog
            ->expects($this->once())
            ->method('assignProducts')
            ->with(123, [$productMock1, $productMock2]);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBunch'])
            ->getMock();
        $eventMock->method('getBunch')->willReturn([
            ['sku' => 'sku-1'],
            ['sku' => 'sku-2']
        ]);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();
        $observerMock->method('getEvent')->willReturn($eventMock);

        $this->observer->execute($observerMock);
    }
}
