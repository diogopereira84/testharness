<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as GraphQlRequestCommand;
use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateShippingMethod as ValidateShippingMethod;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BatchValidateShippingMethodTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;

    /**
     * @var ValidateShippingMethod
     */
    protected ValidateShippingMethod $validateShippingMethod;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->validateShippingMethod = new ValidateShippingMethod($this->loggerMock);
    }

    /**
     * Test validation when both pickup_data and shipping_data are missing
     *
     * @return void
     */
    public function testValidateWhenBothPickupDataAndShippingDataAreMissing(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Required parameter pickup_data or shipping_data is missing.');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Required parameter pickup_data or shipping_data is missing.'));

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArgs'])
            ->getMock();
        $requestMock->method('getArgs')
            ->willReturn(['input' => []]);

        $requestCommandMock = $this->getMockBuilder(GraphQlBatchRequestCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequests'])
            ->getMock();
        $requestCommandMock->method('getRequests')
            ->willReturn([$requestMock]);

        $this->validateShippingMethod->validate($requestCommandMock);
    }

    /**
     * Test validation when both pickup_data and shipping_data are provided
     *
     * @return void
     */
    public function testValidateWhenBothPickupDataAndShippingDataAreProvided(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You should provide just pickup_data or shipping_data parameter.');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('You should provide just pickup_data or shipping_data parameter.'));

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArgs'])
            ->getMock();
        $requestMock->method('getArgs')
            ->willReturn([
                'input' => [
                    'pickup_data' => ['location_id' => '123'],
                    'shipping_data' => ['address' => ['street' => '123 Main St']]
                ]
            ]);

        $requestCommandMock = $this->getMockBuilder(GraphQlBatchRequestCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequests'])
            ->getMock();
        $requestCommandMock->method('getRequests')
            ->willReturn([$requestMock]);

        $this->validateShippingMethod->validate($requestCommandMock);
    }

    /**
     * Test valid case with only pickup_data provided
     *
     * @return void
     */
    public function testValidateWithOnlyPickupDataProvided(): void
    {
        $this->loggerMock->expects($this->never())->method('error');

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArgs'])
            ->getMock();
        $requestMock->method('getArgs')
            ->willReturn([
                'input' => [
                    'pickup_data' => ['location_id' => '123']
                ]
            ]);

        $requestCommandMock = $this->getMockBuilder(GraphQlBatchRequestCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequests'])
            ->getMock();
        $requestCommandMock->method('getRequests')
            ->willReturn([$requestMock]);

        $this->validateShippingMethod->validate($requestCommandMock);
        $this->addToAssertionCount(1);
    }

    /**
     * Test valid case with only shipping_data provided
     *
     * @return void
     */
    public function testValidateWithOnlyShippingDataProvided(): void
    {
        $this->loggerMock->expects($this->never())->method('error');

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArgs'])
            ->getMock();
        $requestMock->method('getArgs')
            ->willReturn([
                'input' => [
                    'shipping_data' => ['address' => ['street' => '123 Main St']]
                ]
            ]);

        $requestCommandMock = $this->getMockBuilder(GraphQlBatchRequestCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequests'])
            ->getMock();
        $requestCommandMock->method('getRequests')
            ->willReturn([$requestMock]);

        $this->validateShippingMethod->validate($requestCommandMock);
        $this->addToAssertionCount(1);
    }
}
