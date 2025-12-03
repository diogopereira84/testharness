<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model;

use Fedex\LateOrdersGraphQl\Api\ConfigInterface;
use Fedex\LateOrdersGraphQl\Api\Data\LateOrderSearchResultDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface;
use Fedex\LateOrdersGraphQl\Api\LateOrderRepositoryInterface;
use Fedex\LateOrdersGraphQl\Api\Data\LateOrderQueryParamsDTOInterface;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderQueryParamsDTO;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSearchResultDTO;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSummaryDTO;
use Fedex\LateOrdersGraphQl\Model\Data\PageInfoDTO;
use Fedex\LateOrdersGraphQl\Model\Service\WindowResolverService;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory as OrderGridCollectionFactory;

class LateOrderRepository implements LateOrderRepositoryInterface
{
    const FIRST_PARTY_ONLY = 'operator';

    /**
     * @param OrderGridCollectionFactory $orderGridCollectionFactory
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderDetailsAssembler $orderDetailsAssembler
     * @param WindowResolverService $windowResolverService
     */
    public function __construct(
        protected readonly OrderGridCollectionFactory $orderGridCollectionFactory,
        protected readonly ConfigInterface            $config,
        protected readonly OrderRepositoryInterface   $orderRepository,
        protected readonly SearchCriteriaBuilder      $searchCriteriaBuilder,
        protected readonly OrderDetailsAssembler      $orderDetailsAssembler,
        protected readonly WindowResolverService      $windowResolverService,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getList(
        LateOrderQueryParamsDTOInterface|LateOrderQueryParamsDTO $params
    ): LateOrderSearchResultDTOInterface {
        /** @var OrderGridCollection $collection */
        $collection = $this->orderGridCollectionFactory->create();
        $collection->addFieldToSelect('*');

        $since = $params->getSince();
        $until = $params->getUntil();
        $status = $params->getStatus();
        $is1p = $params->getIs1p();
        $currentPage = $params->getCurrentPage();
        $pageSize = $params->getPageSize();

        $window = $this->windowResolverService->resolveAndCapWindow($since, $until);
        $sinceDt = $window->since;
        $untilDt = $window->until;

        $since = $sinceDt->format('Y-m-d H:i:s');
        $until = $untilDt->format('Y-m-d H:i:s');

        $collection
            ->addFieldToFilter('created_at', ['gteq' => $since])
            ->addFieldToFilter('created_at', ['lt'   => $until])
            ->addFieldToFilter('status', $status);

        if ($is1p) {
            $collection->addFieldToFilter('flag', self::FIRST_PARTY_ONLY);
        }

        $collection->setOrder('created_at', 'ASC');
        $collection->setOrder('entity_id', 'ASC');
        $collection->setCurPage($currentPage);
        $collection->setPageSize($pageSize);

        $orders = [];
        foreach ($collection->getItems() as $order) {
            $orders[] = new LateOrderSummaryDTO(
                $order->getIncrementId(),
                $order->getCreatedAt(),
                $order->getStatus(),
                $is1p
            );
        }
        $totalCount = $collection->getSize();
        $totalPages = (int)ceil($totalCount / $pageSize);
        $hasNextPage = $currentPage < $totalPages;
        $pageInfo = $this->buildPageInfoDTO($currentPage, $pageSize, $totalPages, $hasNextPage);
        return new LateOrderSearchResultDTO(
            $orders,
            $totalCount,
            $pageInfo
        );
    }

    /**
     * @inheritDoc
     */
    public function getById(string $orderIncrementId): OrderDetailsDTOInterface
    {
        $searchCriteria = $this->buildSearchCriteria(['increment_id' => $orderIncrementId]);
        $orderResult = $this->orderRepository->getList($searchCriteria);
        $orderItems = $orderResult->getItems();
        if (empty($orderItems)) {
            throw new GraphQlNoSuchEntityException(__("Order with increment ID '%1' not found.", $orderIncrementId));
        }
        /** @var OrderInterface $order */
        $order = reset($orderItems);
        return $this->orderDetailsAssembler->assemble($order);
    }

    /**
     * @param array $filters
     * @return SearchCriteria
     */
    protected function buildSearchCriteria(array $filters): SearchCriteria
    {
        foreach ($filters as $field => $condition) {
            $this->searchCriteriaBuilder->addFilter($field, $condition);
        }

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * @param int $currentPage
     * @param int $pageSize
     * @param int $totalPages
     * @param bool $hasNextPage
     * @return PageInfoDTO
     */
    private function buildPageInfoDTO(int $currentPage, int $pageSize, int $totalPages, bool $hasNextPage)
    {
        return new PageInfoDTO(
            $currentPage,
            $pageSize,
            $totalPages,
            $hasNextPage
        );
    }
}
