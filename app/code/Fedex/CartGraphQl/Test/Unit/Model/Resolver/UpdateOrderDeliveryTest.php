<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataHandler;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataProvider;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Fedex\GraphQl\Model\NewRelicHeaders;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use ReflectionClass;

class UpdateOrderDeliveryTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var (\Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestCommandFactoryMock;

    /**
     * @var (\Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $batchResponseFactoryMock;

    /**
     * @var (\Fedex\CartGraphQl\Helper\LoggerHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerHelperMock;

    /**
     * @var (\Fedex\GraphQl\Model\Validation\ValidationBatchComposite & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $validationCompositeMock;

    /**
     * Mock of the DataProvider used for UpdateOrderDelivery resolver.
     *
     * @var (\Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataProvider
     *       & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataProviderMock;

    /**
     * @var (\Fedex\InStoreConfigurations\Api\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $instoreConfigMock;

    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuoteMock;

    /**
     *  Mock object for the data handler that processes order delivery information
     *
     * @var (\Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataHandler
     *      & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataHandlerMock;

    /**
     * @var (\Fedex\CartGraphQl\Model\Checkout\Cart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $graphqlCartMock;

    /**
     * @var UpdateOrderDelivery
     */
    protected $updateOrderDelivery;

    /**
     * @var BatchResponse|MockObject
     */
    private BatchResponse|MockObject $batchResponseMock;

    /**
     * @var ReflectionClass
     */
    private $reflectionClass;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->batchResponseFactoryMock = $this->getMockBuilder(BatchResponseFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerHelperMock = $this->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validationCompositeMock = $this->getMockBuilder(ValidationBatchComposite::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProviderMock = $this->getMockBuilder(DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instoreConfigMock = $this->getMockBuilder(InstoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRateQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHandlerMock = $this->getMockBuilder(DataHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->graphqlCartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->batchResponseMock = $this->getMockBuilder(BatchResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateOrderDelivery = new UpdateOrderDelivery(
            $this->requestCommandFactoryMock,
            $this->batchResponseFactoryMock,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders,
            $this->dataProviderMock,
            $this->instoreConfigMock,
            $this->fxoRateQuoteMock,
            $this->dataHandlerMock,
            $this->graphqlCartMock
        );

        $this->reflectionClass = new ReflectionClass(UpdateOrderDelivery::class);
    }

    /**
     * Test case for successful execution of the UpdateOrderDelivery resolver.
     *
     * @return void
     */
    public function testProceedSuccess(): void
    {
        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);
        $requestMock = $this->createMock(ResolveRequest::class);

        $requestArgs = [
            'input' => [
                'cart_id' => 'test_cart_id'
            ]
        ];

        $cartMock = $this->createMock(Quote::class);
        $requestMock->method('getArgs')->willReturn($requestArgs);
        $requests = [$requestMock];
        $headerArray = [];

        $this->graphqlCartMock->expects($this->any())
            ->method('getCart')
            ->with('test_cart_id', $contextMock)
            ->willReturn($cartMock);

        $this->dataHandlerMock->expects($this->any())
            ->method('execute')
            ->with($cartMock, $requestArgs['input']);

        $rateQuoteResponse = ['rate_quote_data'];

        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->with($cartMock)
            ->willReturn($rateQuoteResponse);

        $this->dataProviderMock->expects($this->any())
            ->method('getFormattedData')
            ->with($cartMock, $rateQuoteResponse)
            ->willReturn(['formatted_data']);

        $this->batchResponseFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);

        $this->batchResponseMock->expects($this->any())
            ->method('addResponse')
            ->with($requestMock, ['formatted_data']);

        $result = $this->updateOrderDelivery->proceed($contextMock, $fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Tests that the UpdateOrderDelivery resolver properly throws GraphQlFujitsuResponseException
     * when an error condition is encountered during processing.
     *
     * @test
     * @return void
     */
    public function testProceedThrowsGraphQlFujitsuResponseException(): void
    {
        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);
        $requestMock = $this->createMock(ResolveRequest::class);

        $requestArgs = [
            'input' => [
                'cart_id' => 'test_cart_id'
            ]
        ];

        $requestMock->method('getArgs')->willReturn($requestArgs);
        $requests = [$requestMock];
        $headerArray = [];

        $cartMock = $this->createMock(Quote::class);

        $this->graphqlCartMock->expects($this->any())
            ->method('getCart')
            ->with('test_cart_id', $contextMock)
            ->willReturn($cartMock);

        $this->dataHandlerMock->expects($this->any())
            ->method('execute')
            ->with($cartMock, $requestArgs['input'])
            ->willThrowException(new GraphQlFujitsuResponseException(__('Test error')));

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->expectExceptionMessage((string) __('Test error'));

        $this->updateOrderDelivery->proceed($contextMock, $fieldMock, $requests, $headerArray);
    }

    /**
     * Test that logRequestedPickupLocalTime is called when delivery dates fields are enabled
     */
    public function testDeliveryDatesFieldsEnabledWithArrayResponse(): void
    {
        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestArgs = [
            'input' => [
                'cart_id' => 'test_cart_id',
                'shipping_data' => [
                    'due_date' => '2025-07-15'
                ]
            ]
        ];

        $requestMock->method('getArgs')->willReturn($requestArgs);
        $requests = [$requestMock];
        $headerArray = ['request_id' => 'test123'];

        $this->graphqlCartMock->method('getCart')
            ->with('test_cart_id', $contextMock)
            ->willReturn($cartMock);

        $this->graphqlCartMock->method('checkIfQuoteIsEmpty')
            ->with($cartMock)
            ->willReturn(false);

        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            UpdateOrderDelivery::REQUESTED_PICKUP_LOCAL_TIME => '2025-07-15'
                        ]
                    ]
                ]
            ]
        ];

        $this->fxoRateQuoteMock->method('getFXORateQuote')
            ->with($cartMock)
            ->willReturn($rateQuoteResponse);

        $this->instoreConfigMock->expects($this->once())
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(true);

        $this->loggerHelperMock->expects($this->any())
            ->method('info')
            ->willReturnCallback(function ($message, $context) {
                if (strpos($message, 'DUE_DATE is different than REQUESTED_PICKUP_LOCAL_TIME') !== false) {
                    $this->fail('Logger should not have logged DUE_DATE message when dates match');
                }
                return null;
            });

        $this->batchResponseFactoryMock->method('create')
            ->willReturn($this->batchResponseMock);

        $this->batchResponseMock->method('addResponse')
            ->with($requestMock, $this->anything());

        $this->dataProviderMock->method('getFormattedData')
            ->with($cartMock, $rateQuoteResponse)
            ->willReturn(['formatted_data']);

        $result = $this->updateOrderDelivery->proceed($contextMock, $fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test handling when delivery dates fields are enabled but rate quote response is not an array
     */
    public function testDeliveryDatesFieldsEnabledWithNonArrayResponse(): void
    {
        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestArgs = [
            'input' => [
                'cart_id' => 'test_cart_id',
                'shipping_data' => [
                    'due_date' => '2025-07-15'
                ]
            ]
        ];

        $requestMock->method('getArgs')->willReturn($requestArgs);
        $requests = [$requestMock];
        $headerArray = ['request_id' => 'test456'];

        $this->graphqlCartMock->method('getCart')
            ->with('test_cart_id', $contextMock)
            ->willReturn($cartMock);

        $this->graphqlCartMock->method('checkIfQuoteIsEmpty')
            ->with($cartMock)
            ->willReturn(false);

        $rateQuoteResponse = ['stringValue' => 'not an array'];

        $this->fxoRateQuoteMock->method('getFXORateQuote')
            ->with($cartMock)
            ->willReturn($rateQuoteResponse);

        $this->instoreConfigMock->expects($this->once())
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(true);

        $this->batchResponseFactoryMock->method('create')
            ->willReturn($this->batchResponseMock);

        $this->dataProviderMock->method('getFormattedData')
            ->with($cartMock, $rateQuoteResponse)
            ->willReturn(['formatted_data']);

        $result = $this->updateOrderDelivery->proceed($contextMock, $fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test that logRequestedPickupLocalTime is not called when delivery dates fields are disabled
     */
    public function testDeliveryDatesFieldsDisabled(): void
    {
        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(ResolveRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestArgs = [
            'input' => [
                'cart_id' => 'test_cart_id'
            ]
        ];

        $requestMock->method('getArgs')->willReturn($requestArgs);
        $requests = [$requestMock];
        $headerArray = ['request_id' => 'test789'];

        $this->graphqlCartMock->method('getCart')
            ->with('test_cart_id', $contextMock)
            ->willReturn($cartMock);

        $this->graphqlCartMock->method('checkIfQuoteIsEmpty')
            ->with($cartMock)
            ->willReturn(false);

        $rateQuoteResponse = ['some_data' => 'value'];

        $this->fxoRateQuoteMock->method('getFXORateQuote')
            ->with($cartMock)
            ->willReturn($rateQuoteResponse);

        $this->instoreConfigMock->expects($this->once())
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(false);

        $this->loggerHelperMock->expects($this->any())
            ->method('info')
            ->with(
                $this->logicalNot(
                    $this->stringContains('DUE_DATE is different than REQUESTED_PICKUP_LOCAL_TIME')
                ),
                $this->anything()
            );

        $this->batchResponseFactoryMock->method('create')
            ->willReturn($this->batchResponseMock);

        $this->dataProviderMock->method('getFormattedData')
            ->with($cartMock, $rateQuoteResponse)
            ->willReturn(['formatted_data']);

        $result = $this->updateOrderDelivery->proceed($contextMock, $fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test the logRequestedPickupLocalTime method when due date and pickup time match
     */
    public function testLogRequestedPickupLocalTimeWhenDatesMatch(): void
    {
        $method = $this->reflectionClass->getMethod('logRequestedPickupLocalTime');
        $method->setAccessible(true);

        $headerArray = ['request_id' => 'test123'];
        $dueDate = '2025-07-15';

        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            UpdateOrderDelivery::REQUESTED_PICKUP_LOCAL_TIME => $dueDate
                        ]
                    ]
                ]
            ]
        ];

        $inputArguments = [
            'shipping_data' => [
                'due_date' => $dueDate
            ]
        ];

        $this->loggerHelperMock->expects($this->never())
            ->method('info');

        $result = $method->invoke($this->updateOrderDelivery, $rateQuoteResponse, $inputArguments, $headerArray);

        $this->assertNull($result);
    }

    /**
     * Test the logRequestedPickupLocalTime method when due date and pickup time are different
     */
    public function testLogRequestedPickupLocalTimeWhenDatesDiffer(): void
    {
        $method = $this->reflectionClass->getMethod('logRequestedPickupLocalTime');
        $method->setAccessible(true);

        $headerArray = ['request_id' => 'test456'];
        $dueDate = '2025-07-15';
        $pickupTime = '2025-07-16';

        $rateQuoteDetails = [
            UpdateOrderDelivery::REQUESTED_PICKUP_LOCAL_TIME => $pickupTime,
            'service' => 'EXPRESS'
        ];

        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        $rateQuoteDetails
                    ]
                ]
            ]
        ];

        $inputArguments = [
            'shipping_data' => [
                'due_date' => $dueDate
            ]
        ];

        $this->loggerHelperMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    $this->stringContains('DUE_DATE is different than REQUESTED_PICKUP_LOCAL_TIME'),
                    $headerArray
                ],
                [
                    $this->stringContains('RAQ response:'),
                    $headerArray
                ]
            );

        $result = $method->invoke($this->updateOrderDelivery, $rateQuoteResponse, $inputArguments, $headerArray);

        $this->assertNull($result);
    }

    /**
     * Test the logRequestedPickupLocalTime method when pickup data is used instead of shipping data
     */
    public function testLogRequestedPickupLocalTimeWithPickupData(): void
    {
        $method = $this->reflectionClass->getMethod('logRequestedPickupLocalTime');
        $method->setAccessible(true);

        $headerArray = ['request_id' => 'test789'];
        $dueDate = '2025-07-15';
        $pickupTime = '2025-07-16';

        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            UpdateOrderDelivery::REQUESTED_PICKUP_LOCAL_TIME => $pickupTime
                        ]
                    ]
                ]
            ]
        ];

        $inputArguments = [
            'pickup_data' => [
                'due_date' => $dueDate
            ]
        ];

        $this->loggerHelperMock->expects($this->exactly(2))
            ->method('info');

        $result = $method->invoke($this->updateOrderDelivery, $rateQuoteResponse, $inputArguments, $headerArray);

        $this->assertNull($result);
    }

    /**
     * Test the logRequestedPickupLocalTime method when due date is missing
     */
    public function testLogRequestedPickupLocalTimeWhenDueDateIsMissing(): void
    {
        $method = $this->reflectionClass->getMethod('logRequestedPickupLocalTime');
        $method->setAccessible(true);

        $headerArray = ['request_id' => 'test321'];
        $pickupTime = '2025-07-16';

        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            UpdateOrderDelivery::REQUESTED_PICKUP_LOCAL_TIME => $pickupTime
                        ]
                    ]
                ]
            ]
        ];

        $inputArguments = [
            'shipping_data' => [
                'some_other_field' => 'value'
            ]
        ];

        $this->loggerHelperMock->expects($this->never())
            ->method('info');

        $result = $method->invoke($this->updateOrderDelivery, $rateQuoteResponse, $inputArguments, $headerArray);

        $this->assertNull($result);
    }

    /**
     * Test the logRequestedPickupLocalTime method when pickup time is missing
     */
    public function testLogRequestedPickupLocalTimeWhenPickupTimeIsMissing(): void
    {
        $method = $this->reflectionClass->getMethod('logRequestedPickupLocalTime');
        $method->setAccessible(true);

        $headerArray = ['request_id' => 'test654'];
        $dueDate = '2025-07-15';

        $rateQuoteResponse = [
            'output' => [
                'rateQuote' => [
                    'rateQuoteDetails' => [
                        [
                            'some_other_field' => 'value'
                        ]
                    ]
                ]
            ]
        ];

        $inputArguments = [
            'shipping_data' => [
                'due_date' => $dueDate
            ]
        ];

        $this->loggerHelperMock->expects($this->never())
            ->method('info');

        $result = $method->invoke($this->updateOrderDelivery, $rateQuoteResponse, $inputArguments, $headerArray);

        $this->assertNull($result);
    }

    /**
     * Test the logRequestedPickupLocalTime method with empty response arrays
     */
    public function testLogRequestedPickupLocalTimeWithEmptyArrays(): void
    {
        $method = $this->reflectionClass->getMethod('logRequestedPickupLocalTime');
        $method->setAccessible(true);

        $headerArray = ['request_id' => 'test987'];

        $rateQuoteResponse = [];
        $inputArguments = [];

        $this->loggerHelperMock->expects($this->never())
            ->method('info');

        $result = $method->invoke($this->updateOrderDelivery, $rateQuoteResponse, $inputArguments, $headerArray);

        $this->assertNull($result);
    }
}
