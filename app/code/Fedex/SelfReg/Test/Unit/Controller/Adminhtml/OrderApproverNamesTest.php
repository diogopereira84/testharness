<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Controller\Adminhtml\Customer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Fedex\SelfReg\Model\Source\CustomerNames;
use Fedex\SelfReg\Controller\Adminhtml\Customer\OrderApproverNames;

class OrderApproverNamesTest extends TestCase
{
    private OrderApproverNames $controller;
    private Context|MockObject $contextMock;
    private JsonFactory|MockObject $jsonFactoryMock;
    private CustomerNames|MockObject $customerNamesMock;
    private RequestInterface|MockObject $requestMock;
    private Json|MockObject $jsonResultMock;
    private AuthorizationInterface|MockObject $authorizationMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->customerNamesMock = $this->createMock(CustomerNames::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->jsonResultMock = $this->createMock(Json::class);
        $this->authorizationMock = $this->createMock(AuthorizationInterface::class);

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->contextMock->expects($this->any())
            ->method('getAuthorization')
            ->willReturn($this->authorizationMock);

        // Mock authorization to return true by default
        $this->authorizationMock->expects($this->any())
            ->method('isAllowed')
            ->with('Fedex_SelfReg::order_approver')
            ->willReturn(true);

        $this->controller = new OrderApproverNames(
            $this->contextMock,
            $this->jsonFactoryMock,
            $this->customerNamesMock
        );
    }

    /**
     * Test execute method with default parameters
     */
    public function testExecuteWithDefaultParameters(): void
    {
        $expectedData = [
            'customers' => [
                ['label' => 'John Doe', 'value' => 1],
                ['label' => 'Jane Smith', 'value' => 2]
            ],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 50,
                'total_count' => 2,
                'total_pages' => 1,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(1, 50, '');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 50, '')
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with custom parameters
     */
    public function testExecuteWithCustomParameters(): void
    {
        $expectedData = [
            'customers' => [
                ['label' => 'John Doe', 'value' => 1]
            ],
            'pagination' => [
                'current_page' => 2,
                'page_size' => 25,
                'total_count' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_previous' => true
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(2, 25, 'john');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(2, 25, 'john')
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with empty search term
     */
    public function testExecuteWithEmptySearch(): void
    {
        $expectedData = [
            'customers' => [],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 50,
                'total_count' => 0,
                'total_pages' => 0,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(1, 50, '');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 50, '')
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with large page size (should be capped to MAX_PAGE_SIZE)
     */
    public function testExecuteWithLargePageSize(): void
    {
        $expectedData = [
            'customers' => [],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 500, // Should be capped to MAX_PAGE_SIZE
                'total_count' => 0,
                'total_pages' => 0,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(1, 1000, ''); // Input exceeds MAX_PAGE_SIZE

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 500, '') // Expect capped value
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with special characters in search
     */
    public function testExecuteWithSpecialCharactersInSearch(): void
    {
        $searchTerm = "O'Connor";
        $expectedData = [
            'customers' => [
                ['label' => "John O'Connor", 'value' => 5]
            ],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 50,
                'total_count' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(1, 50, $searchTerm);

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 50, $searchTerm)
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with null parameters converts to defaults
     */
    public function testExecuteWithNullParametersConvertsToDefaults(): void
    {
        $expectedData = [
            'customers' => [],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 50,
                'total_count' => 0,
                'total_pages' => 0,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(null, null, null);

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 50, '')
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with zero page parameter (should be normalized to 1)
     */
    public function testExecuteWithZeroPage(): void
    {
        $expectedData = [
            'customers' => [
                ['label' => 'John Doe', 'value' => 1]
            ],
            'pagination' => [
                'current_page' => 1, // Controller normalizes 0 to 1
                'page_size' => 50,
                'total_count' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(0, 50, '');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 50, '') // Expect normalized value: 1, not 0
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with negative page parameter (should be normalized to 1)
     */
    public function testExecuteWithNegativePage(): void
    {
        $expectedData = [
            'customers' => [
                ['label' => 'John Doe', 'value' => 1]
            ],
            'pagination' => [
                'current_page' => 1, // Controller normalizes negative to 1
                'page_size' => 50,
                'total_count' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(-5, 50, '');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 50, '') // Expect normalized value: 1
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with zero page size (should be normalized to 1)
     */
    public function testExecuteWithZeroPageSize(): void
    {
        $expectedData = [
            'customers' => [
                ['label' => 'John Doe', 'value' => 1]
            ],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 1, // Controller normalizes 0 to 1
                'total_count' => 1,
                'total_pages' => 1,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(1, 0, '');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 1, '') // Expect normalized value: page_size=1
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

    /**
     * Test execute method with excessive page size (should be capped to MAX_PAGE_SIZE)
     */
    public function testExecuteWithExcessivePageSize(): void
    {
        $expectedData = [
            'customers' => [],
            'pagination' => [
                'current_page' => 1,
                'page_size' => 500, // Controller caps to MAX_PAGE_SIZE (500)
                'total_count' => 0,
                'total_pages' => 0,
                'has_next' => false,
                'has_previous' => false
            ]
        ];

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page'],
                ['page_size'],
                ['search']
            )
            ->willReturnOnConsecutiveCalls(1, 1000, '');

        $this->customerNamesMock->expects($this->once())
            ->method('getPaginatedCustomers')
            ->with(1, 500, '') // Expect capped value: page_size=500
            ->willReturn($expectedData);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())
            ->method('setData')
            ->with($expectedData)
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }

/**
     * Test authorization check
     */
    public function testIsAllowedMethod(): void
    {
        // Create a fresh controller instance for this test with specific authorization mock
        $contextMock = $this->createMock(Context::class);
        $authorizationMock = $this->createMock(AuthorizationInterface::class);
        
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
            
        $contextMock->expects($this->any())
            ->method('getAuthorization')
            ->willReturn($authorizationMock);

        // For this specific test, mock authorization to return false
        $authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with('Fedex_SelfReg::order_approver')
            ->willReturn(false);

        $controller = new OrderApproverNames(
            $contextMock,
            $this->jsonFactoryMock,
            $this->customerNamesMock
        );

        // Use reflection to test the protected _isAllowed method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('_isAllowed');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertFalse($result);
    }

    /**
     * Test admin resource constant
     */
    public function testAdminResourceConstant(): void
    {
        $this->assertEquals('Fedex_SelfReg::order_approver', OrderApproverNames::ADMIN_RESOURCE);
    }

    /**
     * Test authorization check returns true when allowed
     */
    public function testIsAllowedMethodReturnsTrue(): void
    {
        // Create a fresh controller instance for this test
        $contextMock = $this->createMock(Context::class);
        $authorizationMock = $this->createMock(AuthorizationInterface::class);
        
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
            
        $contextMock->expects($this->any())
            ->method('getAuthorization')
            ->willReturn($authorizationMock);

        // For this test, mock authorization to return true
        $authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with('Fedex_SelfReg::order_approver')
            ->willReturn(true);

        $controller = new OrderApproverNames(
            $contextMock,
            $this->jsonFactoryMock,
            $this->customerNamesMock
        );

        // Use reflection to test the protected _isAllowed method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('_isAllowed');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertTrue($result);
    }
}