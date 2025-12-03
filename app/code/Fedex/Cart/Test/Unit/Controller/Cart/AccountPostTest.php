<?php
namespace Fedex\Cart\Test\Unit\Controller\Cart\AccountPost;

use Fedex\Cart\Controller\Cart\AccountPost;
use Magento\Checkout\Model\Cart;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\Cart\Helper\Data as CartDataHelper;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AccountPostTest extends \PHPUnit\Framework\TestCase
{
    protected $redirect;
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
    /**
     * @var (\Magento\Framework\Controller\Result\RedirectFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultRedirectFactory;
    protected $fxoRateHelper;
    protected $fxoRateQuoteHelper;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $url;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $session;
    protected $toggleConfig;
    protected $cartDataHelper;
    /**
     * @var CouponPost
     */
    private $model;

    private $request;
    private $customerSession;
    private $cartFactory;
    private $messageManager;
    protected const CHECKOUT_CART_PATH = 'checkout/cart/';
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
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods([self::GET_PARAM, 'remove'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods([
                self::CREATE,
                'setRemoveFedexAccountNumber',
                'setFedexAccountWarning',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartFactory = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
            ->setMethods([self::CREATE])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->setMethods([self::GET_REFERER_URL, self::SET_PATH, self::SET_URL, self::GET_URL])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->setMethods([self::GET_REFERER_URL, self::CREATE])
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

        $this->context->expects($this->any())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->any())->method('getRedirect')->willReturn($this->redirect);
        $this->context->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactoryMock);

        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->setMethods([
                'setAppliedFedexAccNumber',
                'setRemoveFedexAccountNumber',
                'getAccountDiscountExist',
                'setCouponDiscountExist',
                'setAccountDiscountExist',
                'unsAccountDiscountExist',
                'setAccountDiscountWarningFlag',
                'unsAccountDiscountWarningFlag'
            ])->disableOriginalConstructor()
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
            ->setMethods(['getAllVisibleItems', 'save', self::SET_COUPON_CODE,self::GET_COUPON_CODE, 'setData','getData'])
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
        $this->fxoRateQuoteHelper = $this->getMockBuilder(FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->url = $this->getMockBuilder(UrlInterface::class)
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
        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['encryptData'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            AccountPost::class,
            [
                'context' => $this->context,
                'scopeConfig' => $this->scopeConfig,
                'checkoutSession' => $this->checkoutSession,
                'storeManager' => $this->storeManager,
                'formKeyValidator' => $this->formKeyValidator,
                'cart' => $this->cart,
                'couponFactory' => $this->couponFactory,
                'quoteRepository' => $this->quoteRepository,
                'request' => $this->request,
                'cartFactory' => $this->cartFactory,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'redirect' => $this->redirect,
                'fxoRateHelper' => $this->fxoRateHelper,
                '_url' => $this->url,
                'fxoRateQuote' => $this->fxoRateQuoteHelper,
                'cartDataHelper' => $this->cartDataHelper,
                'toggleConfig' => $this-> toggleConfig,
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * Test case for Execute
     */
    public function testExecute()
    {
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
        $this->cartDataHelper->expects($this->any())->method('encryptData')->willReturn('1234');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setAppliedFedexAccNumber')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setAccountDiscountExist')->willReturnSelf();
        $this->fxoRateQuoteHelper->expects($this->any())->method('getFXORateQuote')->willReturn($fxoRate);
        $this->assertNull($this->model->execute());
    }

    /**
     * Test case for Execute With Toggle Off
     */
    public function testExecuteWithToggleOff()
    {
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
        $this->request->expects($this->any())->method(self::GET_PARAM)->willReturn(null);
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->cartDataHelper->expects($this->any())->method('encryptData')->willReturn('1234');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->checkoutSession->expects($this->any())->method('setRemoveFedexAccountNumber')->willReturnSelf();
        $this->fxoRateHelper->expects($this->any())->method(self::GET_FXO_RATE)->willReturn($fxoRate);
        $this->assertNull($this->model->execute());
    }

    /**
     * Test case for Execute With Toggle On for Account Remove
     */
    public function testExecuteWithToggleOnForAccountRemove()
    {
        $this->request->expects($this->any())->method('remove')->willReturn(1);
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('getAccountDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('unsAccountDiscountExist')->willReturnSelf();
        $this->assertNull($this->model->execute());
    }

     /**
     * Test case for Execute with Fedex Account fix toggle on
     */
    public function testExecuteWithFedexAccountFixToggleOn()
    {
        $fxoRate = ['output' => [
            'alerts' => [
                [
                    'code' => 'RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID',
                    'message' => "FedEx account number in rate request is invalid",
                    'alertType' => "WARNING"
                ]
            ],
        ]];
        $this->cartFactory->expects($this->any())->method(self::CREATE)->willReturn($this->cart);
        $this->cart->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quote);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->expects($this->any())->method('setFedexAccountWarning')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setAccountDiscountWarningFlag')->willReturnSelf();
        $this->fxoRateQuoteHelper->expects($this->any())->method('getFXORateQuote')->willReturn($fxoRate);

        $this->assertNull($this->model->execute());
    }

    public function testExecuteWithCouponCode()
    {
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
        $this->quote->expects($this->any())->method('getData')->with('coupon_code')->willReturn('DISCOUNT2025');
        $this->checkoutSession->expects($this->any())->method('setCouponDiscountExist')->with(true)->willReturnSelf();
        $this->quote->expects($this->any())->method(self::SET_COUPON_CODE)->willReturnSelf();
        $this->cartDataHelper->expects($this->any())->method('encryptData')->willReturn('1234');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setAppliedFedexAccNumber')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setAccountDiscountExist')->willReturnSelf();
        $this->fxoRateQuoteHelper->expects($this->any())->method('getFXORateQuote')->willReturn($fxoRate);
        $this->assertNull($this->model->execute());
    }
}
