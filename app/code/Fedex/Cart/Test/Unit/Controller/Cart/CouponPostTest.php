<?php
namespace Fedex\Cart\Test\Unit\Controller\Cart\CouponPost;

use Fedex\Cart\Controller\Cart\CouponPost;
use Magento\Checkout\Model\Cart;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CouponPostTest extends \PHPUnit\Framework\TestCase
{
    protected $_redirect;
    protected $resultFactoryMock;
    protected $context;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfig;
    protected $checkoutSession;
    /**
     * @var (\Magento\Store\Model\StoreManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeManager;
    /**
     * @var (\Magento\Framework\Data\Form\FormKey\Validator & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $formKeyValidator;
    protected $cart;
    /**
     * @var (\Magento\SalesRule\Model\CouponFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $couponFactory;
    /**
     * @var (\Magento\Quote\Api\CartRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteRepository;
    protected $quote;
    protected $resultRedirectFactory;
    protected $fxoRateHelper;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaper;
    protected $_url;
    protected $session;
    protected $toggleConfig;
    /**
     * @var CouponPost
     */
    private $model;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;
    private $request;
    private $customerSession;
    private $cartFactory;
    private $messageManager;
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

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods([self::GET_PARAM, 'remove'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods([self::CREATE])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartFactory = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
            ->setMethods([self::CREATE])
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_redirect = $this->getMockBuilder(Redirect::class)
            ->setMethods([self::GET_REFERER_URL,self::SET_PATH,self::SET_URL,self::GET_URL])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->setMethods([self::GET_REFERER_URL,self::CREATE])
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->setMethods([
                self::GET_REFERER_URL,
                self::SET_PATH,
                'getResultFactory',
                'getRequest',
                'getRedirect',
                self::GET_URL
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context->expects($this->any())
            ->method('getRedirect')
            ->willReturn($this->_redirect);
        $this->context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->setMethods(['setAccountDiscountExist', 'getCouponDiscountExist', 'unsCouponDiscountExist'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formKeyValidator = $this->getMockBuilder(\Magento\Framework\Data\Form\FormKey\Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cart = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->couponFactory = $this->getMockBuilder(\Magento\SalesRule\Model\CouponFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAllVisibleItems', 'save', self::SET_COUPON_CODE,self::GET_COUPON_CODE,'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods([self::CREATE])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRateHelper = $this->getMockBuilder(FXORate::class)
            ->setMethods([self::GET_FXO_RATE])
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_url = $this->getMockBuilder(UrlInterface::class)
            ->setMethods([self::GET_URL])
            ->getMockForAbstractClass();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_BACK_URL,'getId'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_TOGGLE_CONFIG_VALUE])
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CouponPost::class,
            [
                'context' => $this->context,
                'scopeConfig' => $this->scopeConfig,
                'checkoutSession' => $this->checkoutSession,
                'storeManager' => $this->storeManager,
                'formKeyValidator' => $this->formKeyValidator,
                'cart' => $this->cart,
                'couponFactory' => $this->couponFactory,
                'quoteRepository' => $this->quoteRepository,
                'logger' => $this->logger,
                'request' => $this->request,
                'cartFactory' => $this->cartFactory,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'redirect' => $this->_redirect,
                'fxoRateHelper' => $this->fxoRateHelper,
                'escaper' => $this->escaper,
                '_url' => $this->_url,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * @test
     *
     * @return null
     */
    public function testExecute()
    {
        $fxoRates = [
            self::ALERTS => [[
                'code' => self::COUPONS_CODE_INVALID,
                'id' => 1466693799380,
                self::VERSION => 2,
                'name' => self::POSTERS,
                'qty' => 1,
                self::PRICEABLE => 1,
                self::INSTANCE_ID => 1632939962051
            ]]
        ];
        $fxoRate = [
            self::ALERTS => [[
                'code' => self::MINIMUM_PURCHASE_REQUIRED,
                'id' => 1466693799380,
                self::VERSION => 2,
                'name' => self::POSTERS,
                'qty' => 1,
                self::PRICEABLE => 1,
                self::INSTANCE_ID => 1632939962051
            ]]
        ];
        $this->request->expects($this->any())->method(self::GET_PARAM)->willReturn('AK234');
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')
        ->willReturn('4111 1111 1111 1111 ');
        $this->fxoRateHelper->expects($this->never())->method(self::GET_FXO_RATE)
        ->will($this->onConsecutiveCalls($fxoRates, $fxoRate, [$fxoRate]));

        $this->resultRedirectFactory->expects($this->any())->method(self::CREATE)->willReturn($this->_redirect);
        $this->_redirect->expects($this->any())->method(self::SET_PATH)->with(self::CHECKOUT_CART_PATH)
        ->willReturnSelf();
        $this->_redirect->expects($this->any())->method(self::GET_REFERER_URL)->willReturn(self::CHECKOUT_CART_PATH);
        $this->_redirect->expects($this->any())->method(self::SET_URL)->with(self::CHECKOUT_CART_PATH)
        ->willReturnSelf();
        $this->customerSession->expects($this->any())->method(self::CREATE)->willReturn($this->session);
        $this->session->expects($this->any())->method(self::GET_BACK_URL)->with(self::CHECKOUT_CART)
        ->willReturn(self::CHECKOUT_CART_PATH);
        $this->_url->expects($this->any())->method(self::GET_URL)->willReturn(self::CHECKOUT_CART_PATH);
        $this->quote->expects($this->any())->method(self::GET_COUPON_CODE)->willReturn('MGT001');
        $this->toggleConfig->expects($this->any())->method(self::GET_TOGGLE_CONFIG_VALUE)->willReturn(0);
        $this->messageManager->expects($this->any())->method(self::ADD_SCUCCESS_MESSAGE)->willReturnSelf();
        $this->model->execute();
        $this->model->execute();
        $this->model->execute();
    }
       /**
        * @test
        *
        * @return null
        */
    public function testExecuteWithToggleOn()
    {
        $this->toggleConfig->expects($this->any())->method(self::GET_TOGGLE_CONFIG_VALUE)->willReturn(1);
        $this->request->expects($this->any())->method(self::GET_PARAM)->willReturn('AK234');
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')
        ->willReturn('4111 1111 1111 1111 ');
        $this->resultRedirectFactory->expects($this->any())->method(self::CREATE)->willReturn($this->_redirect);
        $this->_redirect->expects($this->any())->method(self::SET_PATH)->with(self::CHECKOUT_CART_PATH)
        ->willReturnSelf();
        $this->_redirect->expects($this->any())->method(self::GET_REFERER_URL)->willReturn(self::CHECKOUT_CART_PATH);
        $this->_redirect->expects($this->any())->method(self::SET_URL)->with(self::CHECKOUT_CART_PATH)
        ->willReturnSelf();
        $this->customerSession->expects($this->any())->method(self::CREATE)->willReturn($this->session);
        $this->session->expects($this->any())->method(self::GET_BACK_URL)->with(self::CHECKOUT_CART)
        ->willReturn(self::CHECKOUT_CART_PATH);
        $this->_url->expects($this->any())->method(self::GET_URL)->willReturn(self::CHECKOUT_CART_PATH);
        $this->quote->expects($this->any())->method(self::GET_COUPON_CODE)->willReturn('MGT001');
        $this->messageManager->expects($this->any())->method(self::ADD_SCUCCESS_MESSAGE)->willReturnSelf();
        $this->model->execute();
        $this->model->execute();
        $this->model->execute();
    }

    /**
     * For coupon remove
     */
    public function testExecuteWithToggleOnForRemoveCouponCode()
    {
        $this->toggleConfig->expects($this->any())->method(self::GET_TOGGLE_CONFIG_VALUE)->willReturn(1);
        $this->request->expects($this->any())->method('remove')->willReturn(1);
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')
        ->willReturn('');
        $this->checkoutSession->expects($this->any())->method('setAccountDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('getCouponDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('unsCouponDiscountExist')->willReturnSelf();
        
        $this->model->execute();
    }

    /**
     * Test to verify that unsCouponDiscountExist is called when getCouponDiscountExist returns true.
     */
    public function testUnsCouponDiscountExistCalledWhenCouponDiscountExists()
    {
        $this->toggleConfig->expects($this->any())->method(self::GET_TOGGLE_CONFIG_VALUE)->willReturn(0);
        $this->request->expects($this->any())->method(self::GET_PARAM)->willReturn('AK234');
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')->willReturn('');

        $this->checkoutSession->expects($this->any())->method('getCouponDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('unsCouponDiscountExist')->willReturnSelf();

        $this->model->execute();
    }

    /**
     * Test to verify that unsCouponDiscountExist is not called when getCouponDiscountExist returns false.
     */
    public function testUnsCouponDiscountExistNotCalledWhenCouponDiscountDoesNotExist()
    {
        $this->toggleConfig->expects($this->any())->method(self::GET_TOGGLE_CONFIG_VALUE)->willReturn(0);
        $this->request->expects($this->any())->method(self::GET_PARAM)->willReturn('AK234');
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->quote->expects($this->any())->method('getData')->with('fedex_account_number')->willReturn('');

        $this->checkoutSession->expects($this->any())->method('getCouponDiscountExist')->willReturn(false);
        $this->checkoutSession->expects($this->never())->method('unsCouponDiscountExist');

        $this->model->execute();
    }
}
