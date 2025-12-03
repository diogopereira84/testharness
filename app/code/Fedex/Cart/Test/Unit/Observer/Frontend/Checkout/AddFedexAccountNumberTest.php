<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Checkout;

use Fedex\Cart\Helper\Data;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\Quote;
use Fedex\Cart\Observer\Frontend\Checkout\AddFedexAccountNumber;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use \Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;

class AddFedexAccountNumberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Framework\Event\Observer)
     */
    protected $observerMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $cartDataHelperMock;

    /**
     * @var CartFactory
     */
    protected $cartFactoryMock;

    /**
     * @var Cart
     */
    protected $cartMock;

    /**
     * @var Quote
     */
    protected $quoteMock;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CompanyHelper
     */
    protected $companyHelper;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var AddFedexAccountNumber
     */
    protected $addFedexAccountNumber;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Default FedEx account number for testing
     */
    protected const FEDEX_ACCOUNT_NUMBER = '123456';

    /**
     * Array to capture accounts for testing
     *
     * @var array
     */
    public array $capturedAccounts = [];

    /**
     * @var MarketplaceHelper
     */
    public $marketplaceHelper;

    protected function setUp(): void
    {
        $this->cartDataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['encryptData', 'getDefaultFedexAccountNumber', 'applyFedxExAccountInCheckout'])
            ->getMock();
        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber', 'getAppliedFedexAccNumber'])
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setData', 'save'])
            ->getMock();
        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFedexAccountNumber'])
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction', 'getResponse'])
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->addFedexAccountNumber = $this->objectManager->getObject(
            AddFedexAccountNumber::class,
            [
                'cartFactory' => $this->cartFactoryMock,
                'checkoutSession' => $this->checkoutSession,
                'companyHelper' => $this->companyHelper,
                'cartDataHelper' => $this->cartDataHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Tests the functionality related to the company path in the checkout process.
     * @return void
     */
    public function testCompanyPath()
    {
        $observerMock = $this->createMock(Observer::class);
        $cartMock = $this->createMock(Cart::class);
        $quoteMock = $this->createMock(Quote::class);
        $companyMock = $this->createMock(\Magento\Framework\DataObject::class);

        $cartFactory = $this->createMock(CartFactory::class);
        $cartFactory->method('create')->willReturn($cartMock);
        $cartMock->method('getQuote')->willReturn($quoteMock);

        $checkoutSession = $this->createMock(Session::class);
        $customerSession = $this->createMock(CustomerSession::class);
        $customerSession->method('getCustomerId')->willReturn(123);

        $accountHelper = $this->createMock(Account::class);
        $accountHelper->method('getCompanyByCustomerId')->willReturn($companyMock);
        $accountHelper->method('isRetail')->willReturn(false);

        $fxoRateHelper = $this->createMock(FXORate::class);
        $fxoRateHelper->method('isEproCustomer')->willReturn(false);

        $fxoRateQuote = $this->createMock(FXORateQuote::class);
        $fxoRateQuote->expects($this->once())->method('getFXORateQuote')->with($quoteMock);

        $authHelper = $this->createMock(AuthHelper::class);
        $authHelper->method('isLoggedIn')->willReturn(true);

        $companyHelper = $this->createMock(CompanyHelper::class);
        $companyHelper->method('getFedexAccountNumber')->willReturn('123456');

        $cartDataHelper = $this->createMock(CartDataHelper::class);
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $toggleConfig->method('getToggleConfigValue')->with('explorers_fix_rateQuoteCall')->willReturn(true);

        $addToCartToggle = $this->createMock(AddToCartPerformanceOptimizationToggle::class);
        $addToCartToggle->method('isActive')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);

        $enhancedProfileVM = $this->createMock(EnhancedProfile::class);

        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);
        $marketplaceHelper->method('isVendorSpecificCustomerShippingAccountEnabled')->willReturn(false);


        $observer = $this->getMockBuilder(AddFedexAccountNumber::class)
            ->setConstructorArgs([
                $cartFactory,
                $checkoutSession,
                $customerSession,
                $companyHelper,
                $cartDataHelper,
                $enhancedProfileVM,
                $accountHelper,
                $fxoRateHelper,
                $fxoRateQuote,
                $logger,
                $toggleConfig,
                $authHelper,
                $marketplaceHelper,
                $addToCartToggle,
                $addToCartToggle
            ])
            ->onlyMethods([
                'handleCompanyAccounts',
                'applyDiscountAccountForCommercial',
                'applyDiscountAccountForRetail'
            ])
            ->getMock();

        $observer->expects($this->once())->method('handleCompanyAccounts')->with($companyMock);
        $observer->expects($this->once())->method('applyDiscountAccountForCommercial');
        $observer->expects($this->never())->method('applyDiscountAccountForRetail');

        $this->assertNull($observer->execute($observerMock));
    }

    /**
     * Tests the functionality related to the retail path in the checkout process.
     * @return void
     */
    public function testRetailPath()
    {
        $observerMock = $this->createMock(Observer::class);
        $cartMock = $this->createMock(Cart::class);
        $quoteMock = $this->createMock(Quote::class);

        $cartFactory = $this->createMock(CartFactory::class);
        $cartFactory->method('create')->willReturn($cartMock);
        $cartMock->method('getQuote')->willReturn($quoteMock);

        $checkoutSession = $this->createMock(Session::class);
        $customerSession = $this->createMock(CustomerSession::class);
        $customerSession->method('getCustomerId')->willReturn(456);

        $accountHelper = $this->createMock(Account::class);
        $accountHelper->method('getCompanyByCustomerId')->willReturn(null);
        $accountHelper->method('isRetail')->willReturn(true);

        $fxoRateHelper = $this->createMock(FXORate::class);
        $fxoRateHelper->method('isEproCustomer')->willReturn(true);
        $fxoRateHelper->expects($this->once())->method('getFXORate')->with($quoteMock);

        $fxoRateQuote = $this->createMock(FXORateQuote::class);
        $fxoRateQuote->expects($this->never())->method('getFXORateQuote');

        $authHelper = $this->createMock(AuthHelper::class);
        $authHelper->method('isLoggedIn')->willReturn(true);

        $companyHelper = $this->createMock(CompanyHelper::class);
        $companyHelper->method('getFedexAccountNumber')->willReturn('654321');

        $cartDataHelper = $this->createMock(CartDataHelper::class);
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $toggleConfig->method('getToggleConfigValue')->with('explorers_fix_rateQuoteCall')->willReturn(true);

        $addToCartToggle = $this->createMock(AddToCartPerformanceOptimizationToggle::class);
        $addToCartToggle->method('isActive')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $enhancedProfileVM = $this->createMock(EnhancedProfile::class);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);


        $observer = $this->getMockBuilder(AddFedexAccountNumber::class)
            ->setConstructorArgs([
                $cartFactory,
                $checkoutSession,
                $customerSession,
                $companyHelper,
                $cartDataHelper,
                $enhancedProfileVM,
                $accountHelper,
                $fxoRateHelper,
                $fxoRateQuote,
                $logger,
                $toggleConfig,
                $authHelper,
                $marketplaceHelper,
                $addToCartToggle
            ])
            ->onlyMethods([
                'handleCompanyAccounts',
                'applyDiscountAccountForCommercial',
                'applyDiscountAccountForRetail'
            ])
            ->getMock();

        $observer->expects($this->never())->method('handleCompanyAccounts');
        $observer->expects($this->never())->method('applyDiscountAccountForCommercial');
        $observer->expects($this->once())->method('applyDiscountAccountForRetail');

        $this->assertNull($observer->execute($observerMock));
    }

    /**
     * Test that no actions are performed when the user is not logged in.
     *
     * @return void
     */
    public function testNoLogin()
    {
        $observerMock = $this->createMock(Observer::class);
        $cartFactory = $this->createMock(CartFactory::class);
        $cart = $this->createMock(Cart::class);
        $quote = $this->createMock(Quote::class);
        $cartFactory->method('create')->willReturn($cart);
        $cart->method('getQuote')->willReturn($quote);

        $authHelper = $this->createMock(AuthHelper::class);
        $authHelper->method('isLoggedIn')->willReturn(false);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $observer = new AddFedexAccountNumber(
            $cartFactory,
            $this->createMock(Session::class),
            $this->createMock(CustomerSession::class),
            $this->createMock(CompanyHelper::class),
            $this->createMock(CartDataHelper::class),
            $this->createMock(EnhancedProfile::class),
            $this->createMock(Account::class),
            $this->createMock(FXORate::class),
            $this->createMock(FXORateQuote::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ToggleConfig::class),
            $authHelper,
            $marketplaceHelper,
            $this->createMock(AddToCartPerformanceOptimizationToggle::class)
        );

        $this->assertNull($observer->execute($observerMock));
    }

    /**
     * Test that no actions are performed when the user is not logged in.
     *
     * @return void
     */
    public function testExceptionHandling()
    {
        $observerMock = $this->createMock(Observer::class);
        $cartFactory = $this->createMock(CartFactory::class);
        $cart = $this->createMock(Cart::class);
        $quote = $this->createMock(Quote::class);
        $quote->method('getId')->willReturn(111);
        $cartFactory->method('create')->willReturn($cart);
        $cart->method('getQuote')->willReturn($quote);

        $authHelper = $this->createMock(AuthHelper::class);
        $authHelper->method('isLoggedIn')->willReturn(true);

        $accountHelper = $this->createMock(Account::class);
        $accountHelper->method('getCompanyByCustomerId')->willThrowException(new \Exception("Failed"));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug');
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $observer = new AddFedexAccountNumber(
            $cartFactory,
            $this->createMock(Session::class),
            $this->createMock(CustomerSession::class),
            $this->createMock(CompanyHelper::class),
            $this->createMock(CartDataHelper::class),
            $this->createMock(EnhancedProfile::class),
            $accountHelper,
            $this->createMock(FXORate::class),
            $this->createMock(FXORateQuote::class),
            $logger,
            $this->createMock(ToggleConfig::class),
            $authHelper,
            $marketplaceHelper,
            $this->createMock(AddToCartPerformanceOptimizationToggle::class)
        );

        $this->assertNull($observer->execute($observerMock));
    }

    /**
     * Test that the retail account is applied correctly when selected.
     */
    public function testRetailAccountApplied()
    {
        $accountHelper = $this->createMock(Account::class);

        $checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber'])
            ->getMock();

        $personalAccountList = [
            ['account_number' => '12345', 'selected' => 0],
            ['account_number' => '67890', 'selected' => 1],
        ];

        $expectedAccountNumber = '67890';

        $accountHelper->expects($this->once())
            ->method('getActivePersonalAccountList')
            ->willReturn($personalAccountList);

        $checkoutSession->expects($this->once())
            ->method('getRemoveFedexAccountNumber')
            ->willReturn(false);

        $accountHelper->expects($this->once())
            ->method('applyAccountNumberToCheckoutSession')
            ->with($expectedAccountNumber)
            ->willReturn(true);

        $observer = $this->createRetailAccountObserver($accountHelper, $checkoutSession);
        $result = $observer->callApplyDiscountAccountForRetail();

        $this->assertTrue($result);
    }

    /**
     * Test that no retail account is applied when none is selected.
     */
    public function testNoRetailAccountSelected()
    {
        $accountHelper = $this->createMock(Account::class);

        $checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $personalAccountList = [
            ['account_number' => '12345', 'selected' => 0],
            ['account_number' => '67890', 'selected' => 0],
        ];

        $accountHelper->expects($this->once())
            ->method('getActivePersonalAccountList')
            ->willReturn($personalAccountList);

        $accountHelper->expects($this->never())
            ->method('applyAccountNumberToCheckoutSession');

        $observer = $this->createRetailAccountObserver($accountHelper, $checkoutSession);
        $result = $observer->callApplyDiscountAccountForRetail();

        $this->assertFalse($result);
    }

    /**
     * Create test observer for retail account tests
     *
     * @param Account $accountHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @return object
     */
    private function createRetailAccountObserver($accountHelper, $checkoutSession)
    {
        $cartFactory = $this->createMock(\Magento\Checkout\Model\CartFactory::class);
        $customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $companyHelper = $this->createMock(\Fedex\Company\Helper\Data::class);
        $cartDataHelper = $this->createMock(\Fedex\Cart\Helper\Data::class);
        $enhancedProfile = $this->createMock(\Fedex\EnhancedProfile\ViewModel\EnhancedProfile::class);
        $fxoRate = $this->createMock(\Fedex\FXOPricing\Helper\FXORate::class);
        $fxoRateQuote = $this->createMock(\Fedex\FXOPricing\Model\FXORateQuote::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $toggleConfig = $this->createMock(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class);
        $authHelper = $this->createMock(\Fedex\Base\Helper\Auth::class);
        $optimizationToggle = $this->createMock(\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle::class);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        return new class(
            $cartFactory,
            $checkoutSession,
            $customerSession,
            $companyHelper,
            $cartDataHelper,
            $enhancedProfile,
            $accountHelper,
            $fxoRate,
            $fxoRateQuote,
            $logger,
            $toggleConfig,
            $authHelper,
            $marketplaceHelper,
            $optimizationToggle
        ) extends AddFedexAccountNumber {
            public function __construct(
                $cartFactory,
                $checkoutSession,
                $customerSession,
                $companyHelper,
                $cartDataHelper,
                $enhancedProfile,
                $accountHelper,
                $fxoRate,
                $fxoRateQuote,
                $logger,
                $toggleConfig,
                $authHelper,
                $marketplaceHelper,
                $optimizationToggle
            ) {
                parent::__construct(
                    $cartFactory,
                    $checkoutSession,
                    $customerSession,
                    $companyHelper,
                    $cartDataHelper,
                    $enhancedProfile,
                    $accountHelper,
                    $fxoRate,
                    $fxoRateQuote,
                    $logger,
                    $toggleConfig,
                    $authHelper,
                    $marketplaceHelper,
                    $optimizationToggle
                );
            }

            public function callApplyDiscountAccountForRetail()
            {
                $this->applyDiscountAccountForRetail();
                return $this->hasQuote;
            }
        };
    }

    /**
     * Get observer with mocked dependencies for testing
     *
     * @param Account $accountHelper
     * @param Session $checkoutSession
     * @return AddFedexAccountNumber
     */
    private function getObserverWithMocks($accountHelper, $checkoutSession, $marketplaceHelper): AddFedexAccountNumber
    {
        $cartFactory = $this->createMock(\Magento\Checkout\Model\CartFactory::class);
        $customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $companyHelper = $this->createMock(\Fedex\Company\Helper\Data::class);
        $cartDataHelper = $this->createMock(\Fedex\Cart\Helper\Data::class);
        $enhancedProfile = $this->createMock(\Fedex\EnhancedProfile\ViewModel\EnhancedProfile::class);
        $fxoRate = $this->createMock(\Fedex\FXOPricing\Helper\FXORate::class);
        $fxoRateQuote = $this->createMock(\Fedex\FXOPricing\Model\FXORateQuote::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $toggleConfig = $this->createMock(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class);
        $authHelper = $this->createMock(\Fedex\Base\Helper\Auth::class);
        $optimizationToggle = $this->createMock(\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle::class);

        return new class(
            $cartFactory,
            $checkoutSession,
            $customerSession,
            $companyHelper,
            $cartDataHelper,
            $enhancedProfile,
            $accountHelper,
            $fxoRate,
            $fxoRateQuote,
            $logger,
            $toggleConfig,
            $authHelper,
            $marketplaceHelper,
            $optimizationToggle
        ) extends AddFedexAccountNumber {
            public function __construct(
                $cartFactory,
                $checkoutSession,
                $customerSession,
                $companyHelper,
                $cartDataHelper,
                $enhancedProfile,
                $accountHelper,
                $fxoRate,
                $fxoRateQuote,
                $logger,
                $toggleConfig,
                $authHelper,
                $marketplaceHelper,
                $optimizationToggle
            ) {
                parent::__construct(
                    $cartFactory,
                    $checkoutSession,
                    $customerSession,
                    $companyHelper,
                    $cartDataHelper,
                    $enhancedProfile,
                    $accountHelper,
                    $fxoRate,
                    $fxoRateQuote,
                    $logger,
                    $toggleConfig,
                    $authHelper,
                    $marketplaceHelper,
                    $optimizationToggle
                );
            }

            public function call(): bool|string
            {
                $this->applyDiscountAccountForCommercial();
                return $this->hasQuote;
            }
        };
    }

    /**
     * Test that the observer uses the payment account when available.
     */
    public function testUsesPaymentAccount()
    {
        $accountHelper = $this->createMock(Account::class);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber'])
            ->getMock();

        $accountHelper->method('getActiveCompanyAccountList')->willReturnMap([
            ['payment', ['78910' => []]],
            ['discount', []]
        ]);
        $accountHelper->expects($this->once())
            ->method('applyAccountNumberToCheckoutSession')
            ->with('78910')
            ->willReturn('ok');

        $accountHelper->method('getActivePersonalAccountList')->willReturn([]);
        $checkoutSession->method('getRemoveFedexAccountNumber')->willReturn(false);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $obs = $this->getObserverWithMocks($accountHelper, $checkoutSession, $marketplaceHelper);
        $this->assertEquals('ok', $obs->call());
    }

    /**
     * Test that the observer uses the discount account when available.
     */
    public function testUsesDiscountAccount()
    {
        $accountHelper = $this->createMock(Account::class);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber'])
            ->getMock();

        $accountHelper->method('getActiveCompanyAccountList')->willReturnMap([
            ['payment', []],
            ['discount', ['55555' => []]]
        ]);
        $accountHelper->expects($this->once())
            ->method('applyAccountNumberToCheckoutSession')
            ->with('55555')
            ->willReturn(true);

        $accountHelper->method('getActivePersonalAccountList')->willReturn([]);
        $checkoutSession->method('getRemoveFedexAccountNumber')->willReturn(false);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $obs = $this->getObserverWithMocks($accountHelper, $checkoutSession, $marketplaceHelper);
        $this->assertTrue($obs->call());
    }

    /**
     * Test that the observer uses a personal account when available.
     */
    public function testUsesPersonalAccount()
    {
        $accountHelper = $this->createMock(Account::class);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber'])
            ->getMock();

        $accountHelper->method('getActiveCompanyAccountList')->willReturnMap([
            ['payment', []],
            ['discount', []]
        ]);

        $accountHelper->method('getActivePersonalAccountList')->willReturn([
            ['account_number' => '99999', 'selected' => 1]
        ]);

        $accountHelper->expects($this->once())
            ->method('applyAccountNumberToCheckoutSession')
            ->with('99999')
            ->willReturn(true);

        $checkoutSession->method('getRemoveFedexAccountNumber')->willReturn(false);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $obs = $this->getObserverWithMocks($accountHelper, $checkoutSession, $marketplaceHelper);
        $this->assertTrue($obs->call());
    }

    /**
     * Test that no actions are performed when there is no FedEx account number provided or when it has been removed.
     * @return void
     */
    public function testDoesNothingWhenNoAccountOrRemoved()
    {
        $accountHelper = $this->createMock(Account::class);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber'])
            ->getMock();

        $accountHelper->method('getActiveCompanyAccountList')->willReturnMap([
            ['payment', []],
            ['discount', []]
        ]);

        $accountHelper->method('getActivePersonalAccountList')->willReturn([]);
        $checkoutSession->method('getRemoveFedexAccountNumber')->willReturn(true);

        $accountHelper->expects($this->never())->method('applyAccountNumberToCheckoutSession');
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $obs = $this->getObserverWithMocks($accountHelper, $checkoutSession, $marketplaceHelper);
        $this->assertFalse($obs->call());
    }

    /**
     * Create observer for company accounts testing
     *
     * @param EnhancedProfile $profileVM
     * @param CustomerSession $customerSession
     * @param bool $useCached
     * @return AddFedexAccountNumber
     */
    private function createCompanyAccountObserver($profileVM, $customerSession, $useCached = false): AddFedexAccountNumber
    {
        $cartFactory = $this->createMock(\Magento\Checkout\Model\CartFactory::class);
        $checkoutSession = $this->createMock(\Magento\Checkout\Model\Session::class);
        $companyHelper = $this->createMock(\Fedex\Company\Helper\Data::class);
        $cartDataHelper = $this->createMock(\Fedex\Cart\Helper\Data::class);
        $accountHelper = $this->createMock(\Fedex\EnhancedProfile\Helper\Account::class);
        $fxoRate = $this->createMock(\Fedex\FXOPricing\Helper\FXORate::class);
        $fxoRateQuote = $this->createMock(\Fedex\FXOPricing\Model\FXORateQuote::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $toggleConfig = $this->createMock(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class);
        $authHelper = $this->createMock(\Fedex\Base\Helper\Auth::class);
        $optimizationToggle = $this->createMock(\Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle::class);
        $marketplaceHelper = $this->createMock(MarketplaceHelper::class);

        $testInstance = $this;

        return new class(
            $cartFactory,
            $checkoutSession,
            $customerSession,
            $companyHelper,
            $cartDataHelper,
            $profileVM,
            $accountHelper,
            $fxoRate,
            $fxoRateQuote,
            $logger,
            $toggleConfig,
            $authHelper,
            $marketplaceHelper,
            $optimizationToggle,
            $useCached,
            $testInstance
        ) extends AddFedexAccountNumber {
            /**
             * @var bool
             */
            private bool $useCachedAccount;

            /**
             * @var mixed
             */
            private $testInstance;

            /**
             * @var CustomerSession
             */
            protected CustomerSession $customerSession;

            public function __construct(
                $cartFactory,
                $checkoutSession,
                CustomerSession $customerSession,
                $companyHelper,
                $cartDataHelper,
                $profileVM,
                $accountHelper,
                $fxoRate,
                $fxoRateQuote,
                $logger,
                $toggleConfig,
                $authHelper,
                $marketplaceHelper,
                $optimizationToggle,
                bool $useCached,
                $testInstance
            ) {
                $this->useCachedAccount = $useCached;
                $this->testInstance = $testInstance;
                $this->customerSession = $customerSession;

                parent::__construct(
                    $cartFactory,
                    $checkoutSession,
                    $customerSession,
                    $companyHelper,
                    $cartDataHelper,
                    $profileVM,
                    $accountHelper,
                    $fxoRate,
                    $fxoRateQuote,
                    $logger,
                    $toggleConfig,
                    $authHelper,
                    $marketplaceHelper,
                    $optimizationToggle
                );
            }

            protected function checkIfAccountExistsAndIsValid($key, $acc)
            {
                if (empty($acc)) {
                    return false;
                }

                return $this->useCachedAccount
                    ? ['account_number' => $acc, 'account_type' => 'cached']
                    : false;
            }

            public function processCompanyAccounts($company)
            {
                $this->handleCompanyAccounts($company);
                $accounts = $this->customerSession->getCompanyAccountsList();
                $filtered = array_filter(
                    $accounts,
                    fn($v) => !($v === '' || $v === null),
                    ARRAY_FILTER_USE_BOTH
                );
                $this->testInstance->capturedAccounts = $filtered;
            }
        };
    }

    /**
     * Test that all accounts are cached when useCached is true
     */
    public function testAllCached()
    {
        $company = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $company->method('getData')
            ->willReturnCallback(function ($key) {
                $data = [
                    'fedex_account_number' => 'FA1',
                    'shipping_account_number' => 'SA1',
                    'discount_account_number' => 'DA1'
                ];
                return $data[$key] ?? null;
            });

        $session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyAccountsList', 'getCompanyAccountsList'])
            ->getMock();

        $session->method('setCompanyAccountsList')
            ->willReturnCallback(function ($accounts) use ($session) {
                $this->capturedAccounts = $accounts;
                return $session;
            });

        $session->method('getCompanyAccountsList')
            ->willReturnCallback(function () {
                return $this->capturedAccounts;
            });

        $observer = $this->createCompanyAccountObserver(
            $this->createMock(EnhancedProfile::class),
            $session,
            true
        );

        $observer->processCompanyAccounts($company);

        $this->assertSame([
            'fedex_account_number' => ['account_number' => 'FA1', 'account_type' => 'cached'],
            'shipping_account_number' => ['account_number' => 'SA1', 'account_type' => 'cached'],
            'discount_account_number' => ['account_number' => 'DA1', 'account_type' => 'cached'],
        ], $this->capturedAccounts);
    }

    /**
     * Test that account status is verified through profile when useCached is false
     */
    public function testProfileActiveAndInactive()
    {
        $company = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $company->method('getData')
            ->willReturnCallback(function ($key) {
                $data = [
                    'fedex_account_number' => 'ACC1',
                    'shipping_account_number' => 'ACC2',
                    'discount_account_number' => 'ACC3'
                ];
                return $data[$key] ?? null;
            });

        $profile = $this->createMock(EnhancedProfile::class);
        $profile->method('getAccountSummary')
            ->willReturnCallback(function ($accountNumber) {
                $summaries = [
                    'ACC1' => ['account_status' => 'active'],
                    'ACC2' => ['account_status' => 'inactive'],
                    'ACC3' => ['account_status' => 'active']
                ];
                return $summaries[$accountNumber] ?? ['account_status' => 'active'];
            });

        $session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyAccountsList', 'getCompanyAccountsList'])
            ->getMock();

        $capturedAccounts = [];
        $session->method('setCompanyAccountsList')
            ->willReturnCallback(function ($accounts) use (&$capturedAccounts, $session) {
                $capturedAccounts = $accounts;
                return $session;
            });

        $session->method('getCompanyAccountsList')
            ->willReturnCallback(function () use (&$capturedAccounts) {
                return $capturedAccounts;
            });

        $observer = $this->createCompanyAccountObserver($profile, $session, false);
        $observer->processCompanyAccounts($company);

        $this->assertArrayHasKey('fedex_account_number', $capturedAccounts);
        $this->assertSame('ACC1', $capturedAccounts['fedex_account_number']['account_number']);

        $this->assertArrayNotHasKey('shipping_account_number', $capturedAccounts);

        $this->assertSame('ACC3', $capturedAccounts['discount_account_number']['account_number']);
    }

    /**
     * Test that empty account numbers are not added to the accounts list
     */
    public function testAllEmpty()
    {
        $company = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $company->method('getData')
            ->willReturnCallback(function ($key) {
                $data = [
                    'fedex_account_number' => '',
                    'shipping_account_number' => '',
                    'discount_account_number' => ''
                ];
                return $data[$key] ?? null;
            });

        $session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyAccountsList', 'getCompanyAccountsList'])
            ->getMock();

        $session->method('setCompanyAccountsList')
            ->willReturnCallback(function ($accounts) use ($session) {
                $this->capturedAccounts = $accounts;
                return $session;
            });

        $session->method('getCompanyAccountsList')
            ->willReturnCallback(function () {
                return $this->capturedAccounts;
            });

        $observer = $this->createCompanyAccountObserver(
            $this->createMock(EnhancedProfile::class),
            $session,
            false
        );
        $observer->processCompanyAccounts($company);

        $this->assertEmpty($this->capturedAccounts);
    }

    /**
     * Test the checkIfAccountExistsAndIsValid method with various scenarios
     *
     * @dataProvider accountValidationDataProvider
     */
    public function testCheckIfAccountExistsAndIsValid($accountsList, $key, $accountNumber, $expected)
    {
        $session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAccountsList'])
            ->getMock();

        $session->method('getCompanyAccountsList')
            ->willReturn($accountsList);

        $observer = new class($session) extends AddFedexAccountNumber {
            /**
             * @var CustomerSession
             */
            protected CustomerSession $customerSession;

            public function __construct(CustomerSession $customerSession)
            {
                $this->customerSession = $customerSession;
            }

            public function callCheckIfAccountExistsAndIsValid($key, $accountNumber)
            {
                return $this->checkIfAccountExistsAndIsValid($key, $accountNumber);
            }
        };

        $result = $observer->callCheckIfAccountExistsAndIsValid($key, $accountNumber);

        if ($expected === false) {
            $this->assertFalse($result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Data provider for account validation testing
     */
    public function accountValidationDataProvider()
    {
        return [
            'Valid account with matching key and number' => [
                [
                    'fedex_account_number' => [
                        'account_number' => '12345',
                        'account_type' => 'active'
                    ]
                ],
                'fedex_account_number',
                '12345',
                [
                    'account_number' => '12345',
                    'account_type' => 'active'
                ]
            ],
            'Account exists but number does not match' => [
                [
                    'fedex_account_number' => [
                        'account_number' => '12345',
                        'account_type' => 'active'
                    ]
                ],
                'fedex_account_number',
                '67890',
                false
            ],
            'Account exists with empty account type' => [
                [
                    'fedex_account_number' => [
                        'account_number' => '12345',
                        'account_type' => ''
                    ]
                ],
                'fedex_account_number',
                '12345',
                false
            ],
            'Account key does not exist in list' => [
                [
                    'discount_account_number' => [
                        'account_number' => '12345',
                        'account_type' => 'active'
                    ]
                ],
                'fedex_account_number',
                '12345',
                false
            ],
            'Empty accounts list' => [
                [],
                'fedex_account_number',
                '12345',
                false
            ],
            'Null accounts list' => [
                null,
                'fedex_account_number',
                '12345',
                false
            ]
        ];
    }
}
