<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Resolver;

use Fedex\LateOrdersGraphQl\Api\LateOrderRepositoryInterface;
use Fedex\LateOrdersGraphQl\Model\Resolver\OrderDetailsById;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

class OrderDetailsByIdTest extends TestCase
{
    public function testResolveReturnsOrderDetails()
    {
        $repository = $this->createMock(LateOrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getHeader'])
            ->getMockForAbstractClass();
        $resolver = new OrderDetailsById($repository, $logger, $request);
        $field = $this->createMock(Field::class);
        $context = null;
        $info = $this->createMock(ResolveInfo::class);
        $args = ['orderId' => 'OID123'];
        $mockOrderDetails = $this->createMock(\Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface::class);
        $repository->expects($this->once())
            ->method('getById')
            ->with('OID123')
            ->willReturn($mockOrderDetails);
        $result = $resolver->resolve($field, $context, $info, null, $args);
        $this->assertSame($mockOrderDetails, $result);
    }

    public function testResolveCastsOrderIdToString()
    {
        $repository = $this->createMock(LateOrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getHeader'])
            ->getMockForAbstractClass();
        $resolver = new OrderDetailsById($repository, $logger, $request);
        $field = $this->createMock(Field::class);
        $context = null;
        $info = $this->createMock(ResolveInfo::class);
        $args = ['orderId' => 12345];
        $mockOrderDetails = $this->createMock(\Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface::class);
        $repository->expects($this->once())
            ->method('getById')
            ->with('12345')
            ->willReturn($mockOrderDetails);
        $result = $resolver->resolve($field, $context, $info, null, $args);
        $this->assertSame($mockOrderDetails, $result);
    }
}
