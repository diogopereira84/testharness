<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Model\Resolver\AddOrUpdateFedexAccountNumber;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
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
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Exception;
use Fedex\Cart\Helper\Data as CartDataHelper;

class AddOrUpdateFedexAccountNumberTest extends TestCase
{
    /**
     * @var FxoRateQuote
     * Holds the instance of the FxoRateQuote used for rate quoting operations in tests.
     */
    private $fxoRateQuote;

    /**
     * @var mixed
     * Stores the configuration settings related to in-store operations.
     */
    private $instoreConfig;

    /**
     * @var SetFedexAccountNumber
     * Mock or instance used for testing the addition or update of FedEx account numbers.
     */
    private $setFedexAccountNumber;

    /**
     * @var CartModel
     * Represents the cart model instance used for testing purposes.
     */
    private $cartModel;

    /**
     * @var RequestCommandFactory
     * Mock or instance used for testing request command creation.
     */
    private $requestCommandFactory;

    /**
     * @var ValidationComposite
     * Mock or instance used for validating FedEx account numbers in tests.
     */
    private $validationComposite;

    /**
     * @var BatchResponseFactory
     * Mock or instance used for creating batch response objects in tests.
     */
    private $batchResponseFactory;

    /**
     * @var LoggerHelper
     * Instance of the LoggerHelper used for logging within the test class.
     */
    private $loggerHelper;

    /**
     * @var mixed
     * Stores the New Relic headers used for monitoring or tracking purposes.
     */
    private $newRelicHeaders;

    /**
     * @var AddOrUpdateFedexAccountNumber
     * Instance of the AddOrUpdateFedexAccountNumber resolver used for testing purposes.
     */
    private $addOrUpdateFedexAccountNumber;

    /**
     * @var mixed
     * Stores the response from a batch operation, typically used for testing purposes.
     */
    private $batchResponse;

    /**
     * @var mixed
     * $context Context object used for testing purposes.
     */
    private $context;

    /**
     * @var Field
     * Mock or instance of the Field class used for testing GraphQL field resolution.
     */
    private $field;

    /**
     * @var CartDataHelper
     */
    private $cartHelper;

    /**
     * Sets up the test environment by initializing mocks and the resolver instance.
     */
    protected function setUp(): void
    {
        $this->fxoRateQuote = $this
            ->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instoreConfig = $this
            ->getMockBuilder(InstoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setFedexAccountNumber = $this
            ->getMockBuilder(SetFedexAccountNumber::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartModel = $this
            ->getMockBuilder(CartModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestCommandFactory = $this
            ->getMockBuilder(GraphQlBatchRequestCommandFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validationComposite = $this
            ->getMockBuilder(ValidationBatchComposite::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchResponseFactory = $this
            ->getMockBuilder(BatchResponseFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerHelper = $this
            ->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->newRelicHeaders = $this
            ->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchResponse = $this
            ->getMockBuilder(BatchResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this
            ->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->field = $this
            ->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartHelper = $this
            ->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addOrUpdateFedexAccountNumber = new AddOrUpdateFedexAccountNumber(
            $this->fxoRateQuote,
            $this->instoreConfig,
            $this->setFedexAccountNumber,
            $this->cartModel,
            $this->cartHelper,
            $this->requestCommandFactory,
            $this->validationComposite,
            $this->batchResponseFactory,
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    /**
     * Tests the behavior of the resolver when the FedEx account feature is disabled.
     *
     * @return void
     */
    public function testProceedFeatureDisabled(): void
    {
        $headerArray = ['header' => 'value'];
        $input = [
            'input' => [
                'cart_id' => 'cart123',
                'fedex_account_number' => '123456',
                'fedex_ship_account_number' => '654321'
            ]
        ];
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($input);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $this->instoreConfig->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(false);
        $this->batchResponseFactory->expects($this->once())->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())->method('addResponse')->with($request, []);

        $result = $this->addOrUpdateFedexAccountNumber->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the proceed logic when both account numbers are provided.
     *
     * @return void
     */
    public function testProceedWithBothAccountNumbers(): void
    {
        $headerArray = ['header' => 'value'];
        $input = [
            'input' => [
                'cart_id' => 'cart123',
                'fedex_account_number' => '123456',
                'fedex_ship_account_number' => '654321'
            ]
        ];
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($input);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getShippingAddress',
                'save'
            ])
            ->addMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo',
                'getGtn'
            ])
            ->getMock();

        $cart->method('save')->willReturnSelf();
        $this->instoreConfig->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);
        $this->cartModel->expects($this->once())
            ->method('getCart')
            ->with('cart123', $this->context)
            ->willReturn($cart);
        $this->setFedexAccountNumber
            ->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with('123456', '654321', $cart);
        $this->fxoRateQuote
            ->expects($this->once())
            ->method('getFXORateQuote')
            ->with($cart);
        $this->loggerHelper
            ->expects($this->atLeastOnce())
            ->method('info');
        $this->batchResponseFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())
            ->method('addResponse');

        $result = $this->addOrUpdateFedexAccountNumber->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the proceed functionality when only the FedEx account number is provided.
     *
     * @return void
     */
    public function testProceedWithOnlyFedexAccountNumber(): void
    {
        $headerArray = ['header' => 'value'];
        $input = [
            'input' => [
                'cart_id' => 'cart123',
                'fedex_account_number' => '123456'
            ]
        ];
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($input);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getShippingAddress',
                'save'
            ])
            ->addMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo',
                'getGtn'
            ])
            ->getMock();

        $cart->method('save')->willReturnSelf();

        $cart->method('getCustomerFirstname')->willReturn('John');
        $cart->method('getCustomerLastname')->willReturn('Doe');
        $cart->method('getCustomerEmail')->willReturn('john@doe.com');
        $cart->method('getCustomerTelephone')->willReturn('1234567890');
        $cart->method('getExtNo')->willReturn('123');
        $cart->method('getGtn')->willReturn('gtn123');

        $shippingAddress = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getFirstname',
                'getLastname',
                'getEmail',
                'getTelephone',
                'getCompany'
            ])
            ->addMethods(['getExtNo'])
            ->getMock();
        $shippingAddress->method('getFirstname')->willReturn('Jane');
        $shippingAddress->method('getLastname')->willReturn('Smith');
        $shippingAddress->method('getEmail')->willReturn('jane@smith.com');
        $shippingAddress->method('getTelephone')->willReturn('0987654321');
        $shippingAddress->method('getExtNo')->willReturn('321');
        $shippingAddress->method('getCompany')->willReturn('Acme Inc');

        $cart->method('getShippingAddress')->willReturn($shippingAddress);
        $this->instoreConfig
            ->method('isAddOrUpdateFedexAccountNumberEnabled')
            ->willReturn(true);
        $this->cartModel
            ->expects($this->once())
            ->method('getCart')
            ->with('cart123', $this->context)
            ->willReturn($cart);
        $this->setFedexAccountNumber
            ->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with('123456', null, $cart);
        $this->fxoRateQuote->expects($this->once())->method('getFXORateQuote')->with($cart);
        $this->loggerHelper->expects($this->atLeastOnce())->method('info');
        $this->batchResponseFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->batchResponse);
        $this->batchResponse
            ->expects($this->once())
            ->method('addResponse');

        $result = $this->addOrUpdateFedexAccountNumber->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the behavior of the resolver when no FedEx account numbers are provided.
     *
     * @return void
     */
    public function testProceedWithNoAccountNumbers(): void
    {
        $headerArray = ['header' => 'value'];
        $input = [
            'input' => [
                'cart_id' => 'cart123'
            ]
        ];
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($input);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getShippingAddress',
                'save'
            ])
            ->addMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo',
                'getGtn'
            ])
            ->getMock();

        $cart->method('save')->willReturnSelf();
        $cart->method('getShippingAddress')->willReturn(null);
        $this->instoreConfig
            ->method('isAddOrUpdateFedexAccountNumberEnabled')
            ->willReturn(true);
        $this->cartModel
            ->expects($this->once())
            ->method('getCart')
            ->with('cart123', $this->context)
            ->willReturn($cart);
        $this->setFedexAccountNumber
            ->expects($this->never())
            ->method('setFedexAccountNumber');
        $this->fxoRateQuote
            ->expects($this->never())
            ->method('getFXORateQuote');
        $this->loggerHelper
            ->expects($this->atLeastOnce())
            ->method('info');
        $this->batchResponseFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->never())->method('addResponse');

        $result = $this->addOrUpdateFedexAccountNumber->proceed($this->context, $this->field, $requests, $headerArray);
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
        $input = [
            'input' => [
                'cart_id' => 'cart123',
                'fedex_account_number' => '123456'
            ]
        ];
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($input);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getShippingAddress',
                'save'
            ])
            ->addMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo',
                'getGtn'
            ])
            ->getMock();

        $cart->method('save')->willReturnSelf();
        $cart->method('getCustomerFirstname')->willReturn('John');
        $cart->method('getCustomerLastname')->willReturn('Doe');
        $cart->method('getCustomerEmail')->willReturn('john@doe.com');
        $cart->method('getCustomerTelephone')->willReturn('1234567890');
        $cart->method('getExtNo')->willReturn('123');
        $cart->method('getGtn')->willReturn('gtn123');

        $shippingAddress = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getFirstname',
                'getLastname',
                'getEmail',
                'getTelephone',
                'getCompany'
            ])
            ->addMethods(['getExtNo'])
            ->getMock();
        $shippingAddress->method('getFirstname')->willReturn('Jane');
        $shippingAddress->method('getLastname')->willReturn('Smith');
        $shippingAddress->method('getEmail')->willReturn('jane@smith.com');
        $shippingAddress->method('getTelephone')->willReturn('0987654321');
        $shippingAddress->method('getExtNo')->willReturn('321');
        $shippingAddress->method('getCompany')->willReturn('Acme Inc');

        $cart->method('getShippingAddress')->willReturn($shippingAddress);
        $this->instoreConfig
            ->method('isAddOrUpdateFedexAccountNumberEnabled')
            ->willReturn(true);
        $this->cartModel
            ->expects($this->once())
            ->method('getCart')
            ->with('cart123', $this->context)
            ->willReturn($cart);
        $this->setFedexAccountNumber
            ->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with('123456', null, $cart);
        $this->fxoRateQuote
            ->expects($this->once())
            ->method('getFXORateQuote')
            ->with($cart)
            ->willThrowException(new GraphQlFujitsuResponseException(__('FXO error')));
        $this->loggerHelper
            ->expects($this->atLeastOnce())
            ->method('error');
        $this->batchResponseFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->batchResponse);
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('FXO error');
        $this->addOrUpdateFedexAccountNumber->proceed($this->context, $this->field, $requests, $headerArray);
    }

    /**
     * Tests that the proceed method throws a generic exception.
     *
     * @return void
     */
    public function testProceedThrowsGenericException(): void
    {
        $headerArray = ['header' => 'value'];
        $input = [
            'input' => [
                'cart_id' => 'cart123',
                'fedex_account_number' => '123456'
            ]
        ];
        $resolveInfo = $this->createMock(ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($input);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $cart = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getShippingAddress',
                'save'
            ])
            ->addMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo',
                'getGtn'
            ])
            ->getMock();

        $cart->method('save')->willReturnSelf();
        $cart->method('getCustomerFirstname')->willReturn('John');
        $cart->method('getCustomerLastname')->willReturn('Doe');
        $cart->method('getCustomerEmail')->willReturn('john@doe.com');
        $cart->method('getCustomerTelephone')->willReturn('1234567890');
        $cart->method('getExtNo')->willReturn('123');
        $cart->method('getGtn')->willReturn('gtn123');

        $shippingAddress = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getFirstname',
                'getLastname',
                'getEmail',
                'getTelephone',
                'getCompany'
            ])
            ->addMethods(['getExtNo'])
            ->getMock();
        $shippingAddress->method('getFirstname')->willReturn('Jane');
        $shippingAddress->method('getLastname')->willReturn('Smith');
        $shippingAddress->method('getEmail')->willReturn('jane@smith.com');
        $shippingAddress->method('getTelephone')->willReturn('0987654321');
        $shippingAddress->method('getExtNo')->willReturn('321');
        $shippingAddress->method('getCompany')->willReturn('Acme Inc');

        $cart->method('getShippingAddress')->willReturn($shippingAddress);
        $this->instoreConfig
            ->method('isAddOrUpdateFedexAccountNumberEnabled')
            ->willReturn(true);
        $this->cartModel
            ->expects($this->once())
            ->method('getCart')
            ->with('cart123', $this->context)
            ->willReturn($cart);
        $this->setFedexAccountNumber
            ->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with('123456', null, $cart);
        $this->fxoRateQuote
            ->expects($this->once())
            ->method('getFXORateQuote')
            ->with($cart)
            ->willThrowException(new Exception('Generic error'));
        $this->loggerHelper
            ->expects($this->atLeastOnce())
            ->method('error');
        $this->batchResponseFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->batchResponse);
        $this->expectException(GraphQlInputException::class);
        $this->addOrUpdateFedexAccountNumber->proceed($this->context, $this->field, $requests, $headerArray);
    }

}
