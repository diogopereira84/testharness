<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Plugin\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Checkout\Controller\Cart\CouponPost as Subject;
use Magento\Checkout\Model\CartFactory;
use Psr\Log\LoggerInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Fedex\Cart\Plugin\Controller\CouponPostPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;

class CouponPostPluginTest extends TestCase
{
    /**
     * @var CouponPostPlugin
     */
    protected $cart;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var RequestInterface
     */
    protected $request;

    protected const CHECKOUT_CART_PATH = 'Checkout/cart/';
    protected const CHECKOUT_CART = 'checkout/cart/';
    protected const GET_PARAM = 'getParam';
    protected const CREATE = 'create';
    protected const SET_URL = 'setUrl';
    protected const GET_URL = 'getUrl';
    protected const GET_REFERER_URL = 'getRefererUrl';
    protected const SET_PATH = 'setPath';
    protected const SET_COUPON_CODE = 'setCouponCode';
    protected const GET_COUPON_CODE = 'getCouponCode';
    protected const GET_FXO_RATE = 'getFXORate';
    protected const GET_BACK_URL = 'getBackUrl';
    protected const GET_TOGGLE_CONFIG_VALUE = 'getToggleConfigValue';
    protected const ALERTS = 'alerts';
    protected const COUPONS_CODE_INVALID = 'COUPONS.CODE.INVALID';
    protected const VERSION = 'version';
    protected const POSTERS = 'Posters';
    protected const PRICEABLE = 'priceable';
    protected const INSTANCE_ID = 'instanceId';
    protected const MINIMUM_PURCHASE_REQUIRED = 'MINIMUM.PURCHASE.REQUIRED';
    protected const GET_QUOTE = 'getQuote';
    protected const ADD_SCUCCESS_MESSAGE = 'addSuccessMessage';

    /** @var CouponPostPlugin */
    private $plugin;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CartFactory */
    private $cartFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FXORateQuote */
    private $fxoRateQuote;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ToggleConfig */
    private $toggleConfig;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Session */
    private $checkoutSession;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Subject */
    private $subject;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Redirect */
    private $result;

    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods([self::CREATE])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cart = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAllVisibleItems', 'save', self::SET_COUPON_CODE, self::GET_COUPON_CODE, 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRateQuote = $this->createMock(FXORateQuote::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->setMethods(['setAccountDiscountExist', 'getCouponDiscountExist', 'unsCouponDiscountExist'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods([self::GET_PARAM, 'remove'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subject = $this->getMockBuilder(Subject::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->result = $this->createMock(Redirect::class);
        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            CouponPostPlugin::class,
            [
                'cartFactory' => $this->cartFactory,
                'fxoRateQuote' => $this->fxoRateQuote,
                'toggleConfig' => $this->toggleConfig,
                'checkoutSession' => $this->checkoutSession,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Tests the behavior of the afterExecute method when a coupon code is present.
     * @return void
     */
    public function testAfterExecuteWithCouponCode()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        $this->subject->method('getRequest')
            ->willReturn($this->getMockRequest('TESTCOUPON'));
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);

        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->with('TESTCOUPON')->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('4111 1111 1111 1111 ');

        $this->checkoutSession->expects($this->any())
            ->method('setAccountDiscountExist')
            ->with(true);

        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->quote);

        $result = $this->plugin->afterExecute($this->subject, $this->result);

        $this->assertSame($result, $this->result);
    }

    /**
     * Tests the behavior of the afterExecute method when no coupon code is present.
     * @return void
     */
    public function testAfterExecuteWithoutCouponCode()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);
        $this->subject->method('getRequest')
            ->willReturn($this->getMockRequest());
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);

        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('');

        $this->checkoutSession->expects($this->any())->method('unsCouponDiscountExist')->willReturnSelf();

        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->quote);

        $result = $this->plugin->afterExecute($this->subject, $this->result);

        $this->assertSame($result, $this->result);
    }

    /**
     * Creates a mock request object with the specified coupon code.
     *
     * @param string $coupon
     * @return \PHPUnit\Framework\MockObject\MockObject|RequestInterface
     */
    private function getMockRequest($coupon = '')
    {
        $this->request->expects($this->any())->method('getParam')
            ->willReturn($coupon);
        return $this->request;
    }

    /**
     * Tests the behavior of the afterExecute method when removing a coupon code.
     * @return void
     */
    public function testAfterExecuteRemovesCouponDiscountWhenCouponDiscountExist()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturn(true);

        $this->subject->method('getRequest')
            ->willReturn($this->getMockRequest());

        $this->cartFactory->method(self::CREATE)
            ->willReturn($this->cart);
        $this->cart->method(self::GET_QUOTE)
            ->willReturn($this->quote);

        $this->quote->method(self::SET_COUPON_CODE)
            ->with('')
            ->willReturnSelf();
        $this->quote->method('getData')
            ->with('fedex_account_number')
            ->willReturn('');

        $this->checkoutSession->expects($this->once())
            ->method('getCouponDiscountExist')
            ->willReturn(true);

        $this->checkoutSession->expects($this->once())
            ->method('unsCouponDiscountExist');

        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->quote);

        $result = $this->plugin->afterExecute($this->subject, $this->result);
        $this->assertSame($this->result, $result);
    }
}
