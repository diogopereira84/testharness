<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\Cart\Test\Unit\Observer\Frontend\page;

use Fedex\Cart\Helper\Data;
use Fedex\Cart\Observer\Frontend\Cart\CartLimitValidation;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class CartLimitValidationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Data
     */
    protected $cartDataHelperMock;

    /**
     * @var Cart
     */
    protected $checkoutCartHelperMock;

    /**
     * @var Http
     */
    protected $responseMock;

    /**
     * @var HttpRequest
     */
    protected $requestHttpMock;

    /**
     * @var RedirectInterface
     */
    protected $redirectMock;

    /**
     * @var Observer
     */
    protected $observerMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var CartLimitValidation
     */
    protected $cartLimitValidation;

    /**
     * @var DeliveryHelper
     */
    protected $deliveryHelper;

    /**
     * Constants for method names
     */
    protected const FULL_ACTION_NAME = 'catalog_category_view';
    protected const GET_MAX_CART_LIMIT_VALUE = 'getMaxCartLimitValue';
    protected const GET_ITEMS_COUNT = 'getItemsCount';
    protected const GET_CART_URL = 'getCartUrl';
    protected const GET_FULL_ACTION_NAME = 'getFullActionName';
    protected const IS_COMMERCIAL_CUSTOMER = 'isCommercialCustomer';
    protected const GET_CONTROLLER_ACTION = 'getControllerAction';
    protected const GET_RESPONSE = 'getResponse';
    protected const REDIRECT = 'redirect';

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->cartDataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_MAX_CART_LIMIT_VALUE])
            ->getMock();
        $this->checkoutCartHelperMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_ITEMS_COUNT, self::GET_CART_URL])
            ->getMock();
        $this->responseMock = $this->getMockBuilder(\Magento\Framework\App\Response\Http::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_FULL_ACTION_NAME])
            ->getMock();
        $this->requestHttpMock = $this->createMock(HttpRequest::class);
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([self::IS_COMMERCIAL_CUSTOMER])
            ->getMock();
        $this->redirectMock = $this->getMockForAbstractClass(RedirectInterface::class);
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_CONTROLLER_ACTION, self::GET_RESPONSE])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->cartLimitValidation = $this->objectManager->getObject(
            CartLimitValidation::class,
            [
                'cartDataHelper' => $this->cartDataHelperMock,
                'checkoutCartHelper' => $this->checkoutCartHelperMock,
                'request' => $this->requestHttpMock,
                'deliveryHelper' => $this->deliveryHelper,
                self::REDIRECT => $this->redirectMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testToggleActiveFirstExecution(): void
    {
        $ref = new \ReflectionClass(CartLimitValidation::class);
        $prop = $ref->getProperty('hasExecuted');
        $prop->setAccessible(true);
        $prop->setValue(null);

        $toggleMock = $this->createMock(AddToCartPerformanceOptimizationToggle::class);
        $toggleMock->method('isActive')->willReturn(true);

        $this->requestHttpMock
            ->method('getParam')
            ->with('edit')
            ->willReturn('');

        $this->cartDataHelperMock
            ->method('getMaxCartLimitValue')
            ->willReturn(['maxCartItemLimit' => 5]);
        $this->checkoutCartHelperMock
            ->method('getItemsCount')
            ->willReturn(5);

        $url = 'http://example.com/cart';
        $this->checkoutCartHelperMock
            ->method('getCartUrl')
            ->willReturn($url);

        $controller = new class($this->responseMock) {
            /**
             * @var \Magento\Framework\App\Response\Http
             */
            private $resp;

            public function __construct($r)
            {
                $this->resp = $r;
            }
            public function getResponse()
            {
                return $this->resp;
            }
        };
        $this->observerMock
            ->method('getControllerAction')
            ->willReturn($controller);

        $this->redirectMock
            ->expects($this->once())
            ->method('redirect')
            ->with($this->responseMock, $url);

        $subject = new CartLimitValidation(
            $this->cartDataHelperMock,
            $this->checkoutCartHelperMock,
            $this->requestHttpMock,
            $this->deliveryHelper,
            $this->redirectMock,
            $toggleMock
        );

        $subject->execute($this->observerMock);

        $this->assertTrue(
            $prop->getValue(),
            'hasExecuted should be true after first execution'
        );
    }

    /**
     * @return void
     */
    public function testToggleActiveSubsequentDoesNothing(): void
    {
        $ref = new \ReflectionClass(CartLimitValidation::class);
        $prop = $ref->getProperty('hasExecuted');
        $prop->setAccessible(true);
        $prop->setValue(true);

        $toggleMock = $this->createMock(AddToCartPerformanceOptimizationToggle::class);
        $toggleMock->method('isActive')->willReturn(true);

        $this->redirectMock
            ->expects($this->never())
            ->method('redirect');

        $subject = new CartLimitValidation(
            $this->cartDataHelperMock,
            $this->checkoutCartHelperMock,
            $this->requestHttpMock,
            $this->deliveryHelper,
            $this->redirectMock,
            $toggleMock
        );

        $subject->execute($this->observerMock);

        $this->assertTrue(
            $prop->getValue(),
            'hasExecuted should remain true after subsequent execution'
        );
    }

    /**
     * @test for  execute
     *
     * @return null
     */
    public function testExecute()
    {
        $redirectUrl = 'http://magento.com/statefarm/checkout/cart/';
        $cartItems = 40;
        $cartLimitConf = [
            'maxCartItemLimit' => 40,
            'minCartItemThreshold' => 30,
        ];

        $this->deliveryHelper->expects($this->once())
            ->method(self::IS_COMMERCIAL_CUSTOMER)
            ->willReturn(true);
        $this->cartDataHelperMock->expects($this->once())
            ->method(self::GET_MAX_CART_LIMIT_VALUE)
            ->willReturn($cartLimitConf);
        $this->checkoutCartHelperMock->expects($this->once())
            ->method(self::GET_ITEMS_COUNT)
            ->willReturn($cartItems);
        $this->requestHttpMock->expects($this->once())
            ->method(self::GET_FULL_ACTION_NAME)
            ->willReturn(self::FULL_ACTION_NAME);
        $this->checkoutCartHelperMock->expects($this->any())
            ->method(self::GET_CART_URL)
            ->willReturn($redirectUrl);
        $this->observerMock->expects($this->any())
            ->method(self::GET_CONTROLLER_ACTION)
            ->willReturnSelf();
        $this->observerMock->expects($this->any())
            ->method(self::GET_RESPONSE)
            ->willReturn($this->responseMock);
        $this->redirectMock->expects($this->any())
            ->method(self::REDIRECT)
            ->with($this->responseMock, $redirectUrl)
            ->willReturn($redirectUrl);

        $this->assertNull($this->cartLimitValidation->execute($this->observerMock));
    }

    /**
     * @test for  ExecuteWithoutFcl
     *
     * @return null
     */
    public function testExecuteWithoutFcl()
    {
        $redirectUrl = 'http://magento.com/statefarm/checkout/cart/';
        $cartItems = 40;
        $cartLimitConf = [
            'maxCartItemLimit' => 40,
            'minCartItemThreshold' => 30,
        ];

        $this->deliveryHelper->expects($this->once())
            ->method(self::IS_COMMERCIAL_CUSTOMER)
            ->willReturn(false);
        $this->cartDataHelperMock->expects($this->once())
            ->method(self::GET_MAX_CART_LIMIT_VALUE)
            ->willReturn($cartLimitConf);
        $this->checkoutCartHelperMock->expects($this->once())
            ->method(self::GET_ITEMS_COUNT)
            ->willReturn($cartItems);
        $this->requestHttpMock->expects($this->once())
            ->method(self::GET_FULL_ACTION_NAME)
            ->willReturn(self::FULL_ACTION_NAME);
        $this->checkoutCartHelperMock->expects($this->any())
            ->method(self::GET_CART_URL)
            ->willReturn($redirectUrl);
        $this->observerMock->expects($this->any())
            ->method(self::GET_CONTROLLER_ACTION)
            ->willReturnSelf();
        $this->observerMock->expects($this->any())
            ->method(self::GET_RESPONSE)
            ->willReturn($this->responseMock);
        $this->redirectMock->expects($this->any())
            ->method(self::REDIRECT)
            ->with($this->responseMock, $redirectUrl)
            ->willReturn($redirectUrl);

        $this->assertNull($this->cartLimitValidation->execute($this->observerMock));
    }
}
