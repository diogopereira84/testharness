<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Resolver;

use Fedex\LateOrdersGraphQl\Api\ConfigInterface;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderQueryParamsDTO;
use Fedex\LateOrdersGraphQl\Api\Data\LateOrderQueryParamsDTOInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Fedex\LateOrdersGraphQl\Api\LateOrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class LateOrders implements ResolverInterface
{
    const QUERY_TYPE = 'LateOrders';
    const BAD_REQUEST = 'BAD_REQUEST';
    const INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * @param LateOrderRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ConfigInterface $config
     */
    public function __construct(
        protected LateOrderRepositoryInterface $repository,
        protected LoggerInterface              $logger,
        protected readonly RequestInterface    $request,
        protected readonly ConfigInterface     $config,
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $startTime = microtime(true);
        $filter = $args['filter'] ?? [];
        $resultCount = 0;
        $error = null;
        try {
            $lateOrderQueryParams = $this->retrieveLateOrderQueryParams($filter, $args);
            $result = $this->repository->getList($lateOrderQueryParams);
            $resultCount = $result->getTotalCount();
            $pageInfo = $result->getPageInfo();
            return [
                'items'       => $result->getItems(),
                'totalCount' => $resultCount,
                'pageInfo'   => [
                    'currentPage' => $pageInfo->getCurrentPage(),
                    'pageSize'    => $pageInfo->getPageSize(),
                    'totalPages'  => $pageInfo->getTotalPages(),
                    'hasNextPage' => $pageInfo->getHasNextPage(),
                ],
            ];
        } catch (GraphQlInputException $e) {
            $error = [
                'code' => self::BAD_REQUEST,
                'message' => $e->getMessage()
            ];
            throw new GraphQlInputException(__($e->getMessage()), null, ['code' => self::BAD_REQUEST]);
        } catch (\Exception $e) {
            $error = [
                'code' => self::INTERNAL_ERROR,
                'message' => $e->getMessage()
            ];
            throw new GraphQlInputException(__($e->getMessage()), null, ['code' => self::INTERNAL_ERROR]);
        } finally {
            $latency = round((microtime(true) - $startTime) * 1000);
            $this->logger->info('[LateOrdersGraphQl] API call', [
                'caller' => $this->extractBearerToken() ?? 'unknown',
                'queryType' => self::QUERY_TYPE,
                'filter' => $filter,
                'resultCount' => $resultCount,
                'latency_ms' => $latency,
                'error' => $error,
            ]);
        }
    }

    /**
     * Extracts the bearer token from the request header
     *
     * @return string|null
     */
    protected function extractBearerToken(): ?string
    {
        $auth = $this->request->getHeader('authorization');
        if ($auth && is_string($auth)) {
            $authParts = explode('Bearer ', $auth);
            return $authParts[1] ?? null;
        }

        return null;
    }

    /**
     * Retrieves and validates the query parameters for late orders
     *
     * @param array $filter
     * @param array $args
     * @return LateOrderQueryParamsDTOInterface
     */
    protected function retrieveLateOrderQueryParams(array $filter, array $args): LateOrderQueryParamsDTOInterface
    {
        $defaultPageSize = (int)$this->config->getLateOrderQueryDefaultPagination();
        $maxPageSize = (int)$this->config->getLateOrderQueryMaxPagination();

        $pageSize = (int)($args['pageSize'] ?? $defaultPageSize);
        if ($pageSize <= 0) {
            $pageSize = $defaultPageSize;
        }
        if ($maxPageSize > 0 && $pageSize > $maxPageSize) {
            throw new GraphQlInputException(__('Page size cannot exceed %1', $maxPageSize));
        }
        return new LateOrderQueryParamsDTO(
            $filter['since'] ?? null,
            $filter['until'] ?? null,
            $filter['status'] ?? 'NEW',
            array_key_exists('is_1p', $filter) ? (bool)$filter['is_1p'] : true,
            (int)($args['currentPage'] ?? 1),
            $pageSize
        );
    }
}
