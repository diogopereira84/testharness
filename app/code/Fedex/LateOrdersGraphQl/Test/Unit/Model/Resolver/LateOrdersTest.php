<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Resolver;

use Fedex\LateOrdersGraphQl\Api\ConfigInterface;
use Fedex\LateOrdersGraphQl\Api\Data\LateOrderQueryParamsDTOInterface;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderQueryParamsDTO;
use Fedex\LateOrdersGraphQl\Model\Resolver\LateOrders;
use Fedex\LateOrdersGraphQl\Api\LateOrderRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

class LateOrdersTest extends TestCase
{
    public function testResolveReturnsExpectedArray()
    {
        $repository = $this->createMock(LateOrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getHeader'])
            ->getMockForAbstractClass();
        $config = $this->getMockForAbstractClass(ConfigInterface::class);
        $resolver = new LateOrders($repository, $logger, $request, $config);
        $field = $this->createMock(Field::class);
        $context = null;
        $info = $this->createMock(ResolveInfo::class);
        $args = [
            'filter' => [
                'since' => '2025-10-01T00:00:00Z',
                'until' => '2025-10-02T00:00:00Z',
                'status' => 'processing',
                'is_1p' => false
            ],
            'currentPage' => 2,
            'pageSize' => 5
        ];
        $mockResult = $this->createMock(\Fedex\LateOrdersGraphQl\Api\Data\LateOrderSearchResultDTOInterface::class);
        $mockResult->method('getItems')->willReturn(['item1', 'item2']);
        $mockResult->method('getTotalCount')->willReturn(2);
        $mockPageInfo = $this->createMock(\Fedex\LateOrdersGraphQl\Api\Data\PageInfoDTOInterface::class);
        $mockPageInfo->method('getCurrentPage')->willReturn(2);
        $mockPageInfo->method('getPageSize')->willReturn(5);
        $mockPageInfo->method('getTotalPages')->willReturn(3);
        $mockPageInfo->method('getHasNextPage')->willReturn(true);
        $mockResult->method('getPageInfo')->willReturn($mockPageInfo);
        $paramsDTO = new LateOrderQueryParamsDTO('2025-10-01T00:00:00Z', '2025-10-02T00:00:00Z', 'processing', false, 2, 5);
        $repository->expects($this->once())
            ->method('getList')
            ->with($paramsDTO)
            ->willReturn($mockResult);
        $result = $resolver->resolve($field, $context, $info, null, $args);
        $this->assertEquals([
            'items' => ['item1', 'item2'],
            'totalCount' => 2,
            'pageInfo' => [
                'currentPage' => 2,
                'pageSize' => 5,
                'totalPages' => 3,
                'hasNextPage' => true,
            ],
        ], $result);
    }
}
