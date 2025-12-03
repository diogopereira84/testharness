<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Resolver;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Fedex\LateOrdersGraphQl\Api\LateOrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class OrderDetailsById implements ResolverInterface
{
    const QUERY_TYPE = 'OrderDetailsById';
    const NOT_FOUND = 'NOT_FOUND';
    const INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * @param LateOrderRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        private LateOrderRepositoryInterface $repository,
        private LoggerInterface $logger,
        private readonly RequestInterface $request
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $startTime = microtime(true);
        $orderId = (string)($args['orderId'] ?? '');
        $resultCount = 0;
        $error = null;
        try {
            $result = $this->repository->getById($orderId);
            $resultCount = $result ? 1 : 0;
            return $result;
        } catch (GraphQlNoSuchEntityException $e) {
            $error = [
                'code' => self::NOT_FOUND,
                'message' => $e->getMessage()
            ];
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), null, ['code' => self::NOT_FOUND]);
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
                'orderId' => $orderId,
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
    private function extractBearerToken(): ?string
    {
        $auth = $this->request->getHeader('authorization');
        if ($auth && is_string($auth)) {
            $authParts = explode('Bearer ', $auth);
            return $authParts[1] ?? null;
        }

        return null;
    }
}
