<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Prakash Kumar <prakash.kumar@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Model\Resolver\UpdateCartItems;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Fedex\InStoreConfigurations\Model\System\Config as InstoreConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\UpdateCartItems as UpdateCartItemsProvider;
use Fedex\Cart\Model\Quote\IntegrationItem\Repository as IntegrationItemRepository;
use Fedex\CartGraphQl\Model\Address\Builder;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;

class UpdateCartItemsTest extends TestCase
{
    /**
     * @var mixed Stores the instance responsible for retrieving the cart for a user.
     */
    private $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Fedex\CartIntegration\Api\CartIntegrationRepositoryInterface
     *
     * Repository instance for cart integration operations.
     */
    private $cartIntegrationRepository;

    /**
     * @var mixed Provides data or functionality for updating cart items in tests.
     */
    private $updateCartItemsProvider;

    /**
     * @var QuoteProductAdd
     *
     * Holds the instance responsible for adding products to the quote in tests.
     */
    private $quoteProductAdd;

    /**
     * @var IntegrationItemRepository
     *
     * Repository instance for managing integration items within the test context.
     */
    private $integrationItemRepository;

    /**
     * @var mixed $builder Instance of the builder used for constructing test objects or data.
     */
    private $builder;

    /**
     * @var mixed $instoreConfig Configuration settings related to in-store functionality.
     */
    private $instoreConfig;

    /**
     * @var RequestCommandFactory Mock or instance of the RequestCommandFactory used for testing purposes.
     */
    private $requestCommandFactory;

    /**
     * @var mixed $validationComposite Composite object responsible for validating cart items.
     */
    private $validationComposite;

    /**
     * @var BatchResponseFactory Mock or instance of the BatchResponseFactory used for testing purposes.
     */
    private $batchResponseFactory;

    /**
     * @var LoggerHelper
     * Helper instance for logging operations within the test class.
     */
    private $loggerHelper;

    /**
     * @var mixed Stores the New Relic headers used for monitoring or tracking purposes.
     */
    private $newRelicHeaders;

    /**
     * @var mixed $updateCartItems Instance of the class responsible for updating cart items in tests.
     */
    private $updateCartItems;

    /**
     * @var Quote
     *
     * Mock or instance of the Quote model used for testing cart-related operations.
     */
    private $cart;

    /**
     * @var ContextInterface
     *
     * Mock or instance of the context interface used for providing context in GraphQL queries.
     */
    private $context;

    /**
     * @var Field
     *
     * Mock or instance of the Field class used for defining GraphQL fields in tests.
     */
    private $field;

    /**
     * @var BatchResponse
     *
     * Mock or instance of the BatchResponse used to handle responses in batch processing.
     */
    private $batchResponse;

    /**
     * Sets up the test environment by initializing mocks and the UpdateCartItems instance.
     */
    protected function setUp(): void
    {
        $this->getCartForUser = $this->createMock(GetCartForUser::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->updateCartItemsProvider = $this->createMock(UpdateCartItemsProvider::class);
        $this->quoteProductAdd = $this->createMock(QuoteProductAdd::class);
        $this->integrationItemRepository = $this->createMock(IntegrationItemRepository::class);
        $this->builder = $this->createMock(Builder::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->requestCommandFactory = $this->createMock(GraphQlBatchRequestCommandFactory::class);
        $this->validationComposite = $this->createMock(ValidationBatchComposite::class);
        $this->batchResponseFactory = $this->createMock(BatchResponseFactory::class);
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->batchResponse = $this->createMock(BatchResponse::class);
        $this->cart = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getGtn'])
            ->onlyMethods(['getShippingAddress', 'getId', 'getItems'])
            ->getMock();
        $this->context = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMock();
        $this->field = $this->createMock(Field::class);

        $this->updateCartItems = new UpdateCartItems(
            $this->getCartForUser,
            $this->cartRepository,
            $this->cartIntegrationRepository,
            $this->updateCartItemsProvider,
            $this->quoteProductAdd,
            $this->integrationItemRepository,
            $this->builder,
            $this->instoreConfig,
            $this->requestCommandFactory,
            $this->validationComposite,
            $this->batchResponseFactory,
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    /**
     * Tests the successful execution of the proceed method when adding new items
     * and updating the quantity of existing items in the cart.
     */
    public function testProceedSuccessWithAddAndQuantityUpdate(): void
    {
        $headerArray = ['header' => 'value'];
        $maskedCartId = 'masked_id';
        $userId = 123;
        $storeId = 1;
        $cartItemDataAdd = [
            'data' => json_encode([
                'fxoProductInstance' => [
                    'productConfig' => [
                        'product' => [
                            'instanceId' => 'inst1'
                        ]
                    ]
                ]
            ])
        ];
        $cartItemDataQty = [
            'quantity' => 2,
            'cart_item_id' => 10
        ];
        $cartItems = [$cartItemDataAdd, $cartItemDataQty];
        $args = [
            'cartId' => $maskedCartId,
            'cartItems' => $cartItems
        ];
        $resolveInfo = $this->createMock(\Magento\Framework\GraphQl\Schema\Type\ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];

        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getStore'])
            ->getMock();
        $store = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $store->method('getId')->willReturn($storeId);
        $extensionAttributes->method('getStore')->willReturn($store);
        $this->context->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->context->method('getUserId')->willReturn($userId);

        $this->getCartForUser->expects($this->once())
            ->method('execute')
            ->with($maskedCartId, $userId, $storeId)
            ->willReturn($this->cart);
        $this->quoteProductAdd->expects($this->once())
            ->method('setCart')->with($this->cart);
        $this->quoteProductAdd->expects($this->once())
            ->method('addItemToCart');
        $this->quoteProductAdd->expects($this->once())
            ->method('findCartItemByInstanceIdExternal')->willReturn(42);
        $this->integrationItemRepository->expects($this->once())
            ->method('saveByQuoteItemId')->with(42, $cartItemDataAdd['data']);
        $this->updateCartItemsProvider->expects($this->once())
            ->method('processCartItems')->with($this->cart, [$cartItemDataQty]);
        $shippingAddress = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCountryId'])
            ->getMock();
        $shippingAddress->method('getCountryId')->willReturn('US');
        $this->cart->method('getShippingAddress')->willReturn($shippingAddress);
        $this->builder->expects($this->once())
            ->method('setShippingData')->with($this->cart, $shippingAddress);
        $this->cartRepository->expects($this->once())
            ->method('save')->with($this->cart);
        $this->cart->method('getId')->willReturn(99);
        $this->cartRepository->expects($this->once())
            ->method('get')->with(99)->willReturn($this->cart);
        $this->cart->method('getItems')->willReturn(['item1']);
        $this->instoreConfig->expects($this->never())
            ->method('isEnableEstimatedSubtotalFix');
        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('info');
        $this->batchResponseFactory->expects($this->once())
            ->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())
            ->method('addResponse')->with($request, $this->callback(function ($data) {
                return isset($data['cart']['model']);
            }));

        $result = $this->updateCartItems->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }

    /**
     * Tests the behavior of the resolver when a NoSuchEntityException is thrown during the update of cart items.
     *
     * @return void
     */
    public function testProceedNoSuchEntityException(): void
    {
        $headerArray = ['header' => 'value'];
        $maskedCartId = 'masked_id';
        $userId = 123;
        $storeId = 1;
        $args = [
            'cartId' => $maskedCartId,
            'cartItems' => []
        ];
        $request = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getArgs'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $requests = [$request];
        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getStore'])
            ->getMock();
        $store = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $store->method('getId')->willReturn($storeId);
        $extensionAttributes->method('getStore')->willReturn($store);
        $this->context->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->context->method('getUserId')->willReturn($userId);
        $this->getCartForUser->expects($this->once())
            ->method('execute')
            ->willThrowException(new NoSuchEntityException(__('Not found')));
        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('error');
        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->updateCartItems->proceed($this->context, $this->field, $requests, $headerArray);
    }

    /**
     * Tests the behavior of the resolver when a LocalizedException is thrown during the update of cart items.
     *
     * @return void
     */
    public function testProceedLocalizedException(): void
    {
        $headerArray = ['header' => 'value'];
        $maskedCartId = 'masked_id';
        $userId = 123;
        $storeId = 1;
        $args = [
            'cartId' => $maskedCartId,
            'cartItems' => []
        ];
        $request = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getArgs'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $requests = [$request];
        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getStore'])
            ->getMock();
        $store = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $store->method('getId')->willReturn($storeId);
        $extensionAttributes->method('getStore')->willReturn($store);
        $this->context->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->context->method('getUserId')->willReturn($userId);
        $this->getCartForUser->expects($this->once())
            ->method('execute')
            ->willThrowException(new LocalizedException(__('Some error')));
        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('info');
        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('error');
        $this->expectException(GraphQlInputException::class);
        $this->updateCartItems->proceed($this->context, $this->field, $requests, $headerArray);
    }

    /**
     * Tests that proceeding with an empty cart triggers the reset of quote integration totals.
     */
    public function testProceedWithEmptyCartTriggersResetQuoteIntegrationTotals(): void
    {
        $headerArray = ['header' => 'value'];
        $maskedCartId = 'masked_id';
        $userId = 123;
        $storeId = 1;
        $args = [
            'cartId' => $maskedCartId,
            'cartItems' => []
        ];
        $resolveInfo = $this->createMock(\Magento\Framework\GraphQl\Schema\Type\ResolveInfo::class);
        $request = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->onlyMethods(['getArgs', 'getInfo', 'getValue'])
            ->getMock();
        $request->method('getArgs')->willReturn($args);
        $request->method('getInfo')->willReturn($resolveInfo);
        $request->method('getValue')->willReturn(null);
        $requests = [$request];
        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getStore'])
            ->getMock();
        $store = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $store->method('getId')->willReturn($storeId);
        $extensionAttributes->method('getStore')->willReturn($store);
        $this->context->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->context->method('getUserId')->willReturn($userId);
        $this->getCartForUser->expects($this->once())
            ->method('execute')
            ->willReturn($this->cart);
        $this->quoteProductAdd->expects($this->once())
            ->method('setCart')->with($this->cart);
        $this->cart->method('getShippingAddress')->willReturn(null);
        $this->cartRepository->expects($this->once())
            ->method('save')->with($this->cart);
        $this->cart->method('getId')->willReturn(99);
        $this->cartRepository->expects($this->once())
            ->method('get')->with(99)->willReturn($this->cart);
        $this->cart->method('getItems')->willReturn([]);
        $this->instoreConfig->expects($this->once())
            ->method('isEnableEstimatedSubtotalFix')->willReturn(true);
        $quoteIntegration = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')->with('99')->willReturn($quoteIntegration);
        $quoteIntegration->expects($this->once())
            ->method('setRaqNetAmount')->with(0);
        $this->cartIntegrationRepository->expects($this->once())
            ->method('save')->with($quoteIntegration);
        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('info');
        $this->batchResponseFactory->expects($this->once())
            ->method('create')->willReturn($this->batchResponse);
        $this->batchResponse->expects($this->once())
            ->method('addResponse')->with($request, $this->callback(function ($data) {
                return isset($data['cart']['model']);
            }));
        $result = $this->updateCartItems->proceed($this->context, $this->field, $requests, $headerArray);
        $this->assertSame($this->batchResponse, $result);
    }
}
