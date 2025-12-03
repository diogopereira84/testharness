<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Model\Resolver\AddOrUpdateDueDate;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\Stdlib\DateTime;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Model\Checkout\Cart as CartModel;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Exception;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class AddOrUpdateDueDateTest extends TestCase
{
    /**
     * @var \Fedex\CartIntegration\Api\CartIntegrationRepositoryInterface
     * Mocked instance of the cart integration repository.
     * Used for testing interactions with the cart integration layer.
     */
    private $cartIntegrationRepository;

    /**
     * @var mixed $fxoRateQuote Holds the FXO rate quote instance used for testing purposes.
     */
    private $fxoRateQuote;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     * DateTime instance used for handling date and time operations in tests.
     */
    private $dateTime;

    /**
     * @var mixed Stores the configuration settings related to in-store operations.
     */
    private $instoreConfig;

    /**
     * @var CartModel The cart model instance used for testing add or update due date functionality.
     */
    private $cartModel;

    /**
     * @var RequestCommandFactory Mock or instance of the RequestCommandFactory used for testing purposes.
     */
    private $requestCommandFactory;

    /**
     * @var mixed $validationComposite
     * Holds the composite validation object used for validating due date operations in tests.
     */
    private $validationComposite;

    /**
     * @var BatchResponseFactory Mock or instance of the BatchResponseFactory used for testing purposes.
     */
    private $batchResponseFactory;

    /**
     * @var LoggerHelper Mocked instance of the logger helper used for testing purposes.
     */
    private $loggerHelper;

    /**
     * @var mixed Stores the New Relic headers used for monitoring or tracking purposes.
     */
    private $newRelicHeaders;

    /**
     * @var AddOrUpdateDueDate
     * Instance of the AddOrUpdateDueDate resolver used for testing add or update due date functionality.
     */
    private $addOrUpdateDueDate;

    /**
     * @var mixed
     *
     * Stores the response data for a batch operation, used in unit tests to mock or verify batch processing results.
     */
    private $batchResponse;

    /**
     * @var mixed $context Context object used for testing purposes.
     */
    private $context;

    /**
     * @var mixed $field
     * Holds the value for the field being tested in the AddOrUpdateDueDateTest class.
     */
    private $field;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializerMock;

    /**
     * Sets up the test environment by initializing mocks and the AddOrUpdateDueDate instance.
     */
    protected function setUp(): void
    {
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->fxoRateQuote = $this->createMock(FXORateQuote::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->cartModel = $this->createMock(CartModel::class);
        $this->requestCommandFactory = $this->createMock(GraphQlBatchRequestCommandFactory::class);
        $this->validationComposite = $this->createMock(ValidationBatchComposite::class);
        $this->batchResponseFactory = $this->createMock(BatchResponseFactory::class);
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->batchResponse = $this->createMock(BatchResponse::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->field = $this->createMock(Field::class);
        $this->jsonSerializerMock = $this->createMock(JsonSerializer::class);

        $this->addOrUpdateDueDate = new AddOrUpdateDueDate(
            $this->cartIntegrationRepository,
            $this->fxoRateQuote,
            $this->dateTime,
            $this->instoreConfig,
            $this->cartModel,
            $this->jsonSerializerMock,
            $this->requestCommandFactory,
            $this->validationComposite,
            $this->batchResponseFactory,
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    /**
     * Tests the proceed method when both due date and delivery fields are enabled.
     *
     * @return void
     */
    public function testProceedSuccessWithDueDateAndDeliveryFieldsEnabled(): void
    {
        $headerArray = ['header' => 'value'];
        $cartId = 'cart123';
        $dueDate = '2025-07-10 10:00:00';
        $shipByDate = '2025-12-10T10:00:00';
        $input = [
            'input' => [
                'cart_id' => $cartId,
                'due_date' => $dueDate,
                'shipping_estimated_delivery_local_time' => $shipByDate
            ]
        ];
        $args = $input;
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'getId'])
            ->addMethods(['getGtn'])
            ->getMock();
        $cart->setData('entity_id', 42);
        $cart->setData('gtn', 'gtn123');
        $cart->method('getId')->willReturn(42);
        $cart->method('getGtn')->willReturn('gtn123');
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getPickupLocationDate')->willReturn('2025-07-10');

        $this->instoreConfig->method('isAddOrUpdateDueDateEnabled')->willReturn(true);
        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $this->cartModel->expects($this->once())->method('getCart')->with($cartId, $this->context)->willReturn($cart);
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')->with(42)->willReturn($integration);
        $cart->expects($this->atLeastOnce())->method('setData');
        $this->fxoRateQuote->expects($this->once())->method('getFXORateQuote')->with($cart);
        $this->dateTime->expects($this->once())->method('formatDate')->with($dueDate, true)->willReturn('2025-07-10');
        $integration->expects($this->once())->method('setPickupLocationDate')->with('2025-07-10');
        $this->cartIntegrationRepository->expects($this->once())->method('save')->with($integration);
        $this->batchResponseFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())
            ->method('addResponse')->with($request, $this->callback(function ($data) {
                return isset($data['cart']['model']) && isset($data['gtn']) && isset($data['due_date']);
            }));
        $this->loggerHelper->expects($this->atLeastOnce())->method('info');

        $result = $this->addOrUpdateDueDate->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the proceed method when both due date and delivery fields are disabled.
     *
     * @return void
     */
    public function testProceedSuccessWithDueDateAndDeliveryFieldsDisabled(): void
    {
        $headerArray = ['header' => 'value'];
        $cartId = 'cart123';
        $dueDate = '2025-07-10 10:00:00';
        $input = [
            'input' => [
                'cart_id' => $cartId,
                'due_date' => $dueDate
            ]
        ];
        $args = $input;
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'getId'])
            ->addMethods(['getGtn'])
            ->getMock();
        $cart->setData('entity_id', 42);
        $cart->setData('gtn', 'gtn123');
        $cart->method('getId')->willReturn(42);
        $cart->method('getGtn')->willReturn('gtn123');
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getPickupLocationDate')->willReturn('2025-07-10');

        $this->instoreConfig->method('isAddOrUpdateDueDateEnabled')->willReturn(true);
        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(false);
        $this->cartModel->expects($this->once())->method('getCart')->with($cartId, $this->context)->willReturn($cart);
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')->with(42)->willReturn($integration);
        $cart->expects($this->atLeastOnce())->method('setData');
        $this->fxoRateQuote->expects($this->once())->method('getFXORateQuote')->with($cart);
        $this->dateTime->expects($this->once())->method('formatDate')->with($dueDate, true)->willReturn('2025-07-10');
        $integration->expects($this->once())->method('setPickupLocationDate')->with('2025-07-10');
        $this->cartIntegrationRepository->expects($this->once())->method('save')->with($integration);
        $this->batchResponseFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())
            ->method('addResponse')->with($request, $this->callback(function ($data) {
                return isset($data['cart']['model']) && isset($data['gtn']) && isset($data['due_date']);
            }));
        $this->loggerHelper->expects($this->atLeastOnce())->method('info');

        $result = $this->addOrUpdateDueDate->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the proceed method for successful execution when no due date is provided.
     *
     * @return void
     */
    public function testProceedSuccessWithNoDueDate(): void
    {
        $headerArray = ['header' => 'value'];
        $cartId = 'cart123';
        $input = [
            'input' => [
                'cart_id' => $cartId
            ]
        ];
        $args = $input;
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'getId'])
            ->addMethods(['getGtn'])
            ->getMock();
        $cart->setData('entity_id', 42);
        $cart->setData('gtn', 'gtn123');
        $cart->method('getId')->willReturn(42);
        $cart->method('getGtn')->willReturn('gtn123');
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getPickupLocationDate')->willReturn(null);

        $this->instoreConfig->method('isAddOrUpdateDueDateEnabled')->willReturn(true);
        $this->cartModel->expects($this->once())->method('getCart')->with($cartId, $this->context)->willReturn($cart);
        $this->cartIntegrationRepository
            ->expects($this->once())
            ->method('getByQuoteId')
            ->with(42)
            ->willReturn($integration);
        $cart->expects($this->atLeastOnce())->method('setData');
        $this->fxoRateQuote->expects($this->once())->method('getFXORateQuote')->with($cart);
        $this->dateTime->expects($this->never())->method('formatDate');
        $integration->expects($this->never())->method('setPickupLocationDate');
        $this->cartIntegrationRepository->expects($this->never())->method('save');
        $this->batchResponseFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())
            ->method('addResponse')
            ->with($request, $this->callback(function ($data) {
                return isset($data['cart']['model']) && isset($data['gtn']) && array_key_exists('due_date', $data);
            }));
        $this->loggerHelper->expects($this->atLeastOnce())->method('info');

        $result = $this->addOrUpdateDueDate->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the behavior of the proceed method when the feature is disabled.
     *
     * @return void
     */
    public function testProceedFeatureDisabled(): void
    {
        $headerArray = ['header' => 'value'];
        $input = [
            'input' => [
                'cart_id' => 'cart123',
                'due_date' => '2025-07-10 10:00:00'
            ]
        ];
        $args = $input;
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $this->instoreConfig->method('isAddOrUpdateDueDateEnabled')->willReturn(false);
        $this->batchResponseFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this
            ->once())
            ->method('addResponse')
            ->with($request, $this->callback(function ($data) {
                return $data === null || $data === [];
            }));

        $result = $this->addOrUpdateDueDate->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests that the proceed method throws a GraphQlFujitsuResponseException.
     *
     * @return void
     */
    public function testProceedThrowsGraphQlFujitsuResponseException(): void
    {
        $headerArray = ['header' => 'value'];
        $cartId = 'cart123';
        $dueDate = '2025-07-10 10:00:00';
        $input = [
            'input' => [
                'cart_id' => $cartId,
                'due_date' => $dueDate
            ]
        ];
        $args = $input;
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'getId'])
            ->addMethods(['getGtn'])
            ->getMock();
        $cart->setData('entity_id', 42);
        $cart->setData('gtn', 'gtn123');
        $cart->method('getId')->willReturn(42);
        $cart->method('getGtn')->willReturn('gtn123');
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getPickupLocationDate')->willReturn('2025-07-10');

        $this->instoreConfig->method('isAddOrUpdateDueDateEnabled')->willReturn(true);
        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $this->cartModel->expects($this->once())->method('getCart')->with($cartId, $this->context)->willReturn($cart);
        $this->cartIntegrationRepository
            ->expects($this->once())
            ->method('getByQuoteId')
            ->with(42)
            ->willReturn($integration);
        $cart->expects($this->atLeastOnce())->method('setData');
        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->with($cart)
            ->willThrowException(new GraphQlFujitsuResponseException(__('FXO error')));
        $this->loggerHelper->expects($this->atLeastOnce())->method('error');
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('FXO error');
        $this->addOrUpdateDueDate->proceed($this->context, $this->field, $requests, $headerArray);
    }

    /**
     * Tests that the proceed method throws a generic exception.
     *
     * @return void
     */
    public function testProceedThrowsGenericException(): void
    {
        $headerArray = ['header' => 'value'];
        $cartId = 'cart123';
        $dueDate = '2025-07-10 10:00:00';
        $input = [
            'input' => [
                'cart_id' => $cartId,
                'due_date' => $dueDate
            ]
        ];
        $args = $input;
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'getId'])
            ->addMethods(['getGtn'])
            ->getMock();
        $cart->setData('entity_id', 42);
        $cart->setData('gtn', 'gtn123');
        $cart->method('getId')->willReturn(42);
        $cart->method('getGtn')->willReturn('gtn123');
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getPickupLocationDate')->willReturn('2025-07-10');

        $this->instoreConfig->method('isAddOrUpdateDueDateEnabled')->willReturn(true);
        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $this->cartModel->expects($this->once())->method('getCart')->with($cartId, $this->context)->willReturn($cart);
        $this->cartIntegrationRepository
            ->expects($this->once())
            ->method('getByQuoteId')
            ->with(42)
            ->willReturn($integration);
        $cart->expects($this->atLeastOnce())->method('setData');
        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->with($cart)
            ->willThrowException(new Exception('Generic error'));
        $this->loggerHelper->expects($this->atLeastOnce())->method('error');
        $this->expectException(GraphQlInputException::class);
        $this->addOrUpdateDueDate->proceed($this->context, $this->field, $requests, $headerArray);
    }
}
