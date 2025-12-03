<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Block\Cart;

use Fedex\Cart\Block\Cart\Account;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Model\Company;
use Fedex\EnhancedProfile\Helper\Account as HelperAccount;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderSidebarConfigProvider;
use Magento\Checkout\Model\Session;

class AccountTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;

    /**
     * @var (\Magento\Checkout\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $checkoutSessionMock;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSessionMock;

    /**
     * @var (\Magento\Quote\Model\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteMock;
    /**
     * @var (\Magento\Checkout\Block\Cart\AbstractCart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractCartMock;

    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelperMock;

    /**
     * @var (\Magento\Company\Model\Company & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyInterfaceMock;

    /**
     * @var (\Fedex\Cart\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartHelperMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var (\Fedex\Cart\Block\Cart\Account)
     */
    protected $accountMock;
    protected const GET_QUOTE = 'getQuote';
    protected const GET_DATA = 'getData';

    /**
     * @var (\Fedex\EnhancedProfile\Helper\Account & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helperAccountMock;

    /**
     * @var (\Fedex\SubmitOrderSidebar\Model\SubmitOrderSidebarConfigProvider)
     */
    protected $submitOrderSidebarConfigProvider;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this
            ->getMockBuilder(CheckoutSession::class)
            ->setMethods([self::GET_QUOTE, self::GET_DATA])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this
            ->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->setMethods([self::GET_DATA])
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractCartMock = $this
            ->getMockBuilder(AbstractCart::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_QUOTE, self::GET_DATA])
            ->getMockForAbstractClass();

        $this->companyHelperMock = $this
            ->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFedexAccountNumber', 'getCustomerCompany'])
            ->getMockForAbstractClass();

        $this->companyInterfaceMock = $this
            ->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->addMethods(
                [
                    'getFedexAccountNumber',
                    'getDiscountAccountNumber',
                    'getFxoAccountNumberEditable',
                    'getDiscountAccountNumberEditable'
                ]
            )
            ->getMock();

        $this->cartHelperMock = $this
            ->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['decryptData'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this
            ->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->helperAccountMock = $this
            ->getMockBuilder(HelperAccount::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAccountNumberType','getCompanyLoginType'])
            ->getMock();

        $this->submitOrderSidebarConfigProvider = $this
            ->getMockBuilder(SubmitOrderSidebarConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->accountMock = $this->objectManager->getObject(
            Account::class,
            [
                'context' => $this->contextMock,
                'customerSession' => $this->customerSessionMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'cartDataHelper' => $this->cartHelperMock,
                'companyHelper' => $this->companyHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'helperAccount' => $this->helperAccountMock,
                'submitOrderSidebarConfigProvider' => $this->submitOrderSidebarConfigProvider
            ]
        );
    }

    /**
     * @test testGetFedexAccountLast4
     */
    public function testGetFedexAccountLast4()
    {
        $this->checkoutSessionMock->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method(self::GET_DATA)->willReturn(1111);
        $this->accountMock->getFedexAccountLast4();
    }

    /**
     * @test testGetFedexAccountWithoutLast4
     */
    public function testGetFedexAccountWithoutLast4()
    {
        $this->checkoutSessionMock->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method(self::GET_DATA)->willReturn(0);
        $this->accountMock->getFedexAccountLast4();
    }

    /**
     * @test testcanShowFedexAccountRemoveBtnWithFedexAccount
     */
    public function testcanShowFedexAccountRemoveBtnWithFedexAccount()
    {
        $this->checkoutSessionMock->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method(self::GET_DATA)->willReturn(1111);
        $this->cartHelperMock->expects($this->any())->method('decryptData')->willReturn(1111);
        $this->companyInterfaceMock->expects($this->any())->method('getFedexAccountNumber')->willReturn(1111);
        $this->companyInterfaceMock->expects($this->any())->method('getDiscountAccountNumber')->willReturn(1111);
        $this->companyInterfaceMock->expects($this->any())->method('getFxoAccountNumberEditable')->willReturn(null);
        $this->companyHelperMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn($this->companyInterfaceMock);

        $this->companyHelperMock->expects($this->any())->method('getFedexAccountNumber')->willReturn(1111);
        $this->assertEquals(false, $this->accountMock->canShowFedexAccountRemoveBtn());
    }

    /**
     * @test testcanShowFedexAccountRemoveBtnWithoutFedexAccount
     */
    public function testcanShowFedexAccountRemoveBtnWithoutFedexAccount()
    {
        $this->checkoutSessionMock->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method(self::GET_DATA)->willReturn('');
        $this->assertEquals(true, $this->accountMock->canShowFedexAccountRemoveBtn());
    }

    /**
     * Test getAppliedFedexAccount
     *
     * @return void
     */
    public function testGetAppliedFedexAccount()
    {
        $this->checkoutSessionMock->expects($this->any())->method(self::GET_QUOTE)->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method(self::GET_DATA)->willReturn(1111);
        $this->accountMock->getAppliedFedexAccount();

        $this->assertEquals(null, $this->accountMock->getMissedUseCaseFedexAccountToggle());
    }

    public function testFedexAccountIsNullForDiscountAccountType()
    {
        $this->checkoutSessionMock->expects($this->any())
            ->method(self::GET_QUOTE)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
            ->method(self::GET_DATA)
            ->with('fedex_account_number')
            ->willReturn('1234567890');

        $this->cartHelperMock->expects($this->any())
            ->method('decryptData')
            ->with('1234567890')
            ->willReturn('1234567890');

        $this->helperAccountMock->expects($this->any())
            ->method('getCompanyLoginType')
            ->willReturn('FCL');

        $this->helperAccountMock->expects($this->any())
            ->method('getAccountNumberType')
            ->with('1234567890')
            ->willReturn('DISCOUNT');

        $fedExAccountNumber = $this->accountMock->getAppliedFedexAccount();

        $this->assertNull($fedExAccountNumber, 'FedEx account number should be null for DISCOUNT account type');
    }

    /**
     * @test testGetFedexAccountLast4WithToggleConfigFalse
     */
    public function testGetFedexAccountLast4WithToggleConfigFalse()
    {
        // Mock the necessary methods
        $this->checkoutSessionMock->expects($this->any())
            ->method(self::GET_QUOTE)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
            ->method(self::GET_DATA)
            ->willReturn('1234567890');

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(Account::TOGGLE_CART_FXO_ACCOUNT_NUMBER_FIX)
            ->willReturn(false);  // toggle is disabled

        $this->cartHelperMock->expects($this->any())
            ->method('decryptData')
            ->willReturn('1234567890');

        $result = $this->accountMock->getFedexAccountLast4();

        $this->assertEquals('ending in *7890', $result);
    }

    /**
     * @test testGetFedexAccountLast4WithToggleConfigTrue
     */
    public function testGetFedexAccountLast4WithToggleConfigTrue()
    {
        $this->checkoutSessionMock->expects($this->any())
            ->method(self::GET_QUOTE)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
            ->method(self::GET_DATA)
            ->willReturn('1234567890');

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(Account::TOGGLE_CART_FXO_ACCOUNT_NUMBER_FIX)
            ->willReturn(true);

        $this->cartHelperMock->expects($this->any())
            ->method('decryptData')
            ->willReturn('1234567890');

        $result = $this->accountMock->getFedexAccountLast4();

        // Check that the last 4 digits are returned with "ending in" prefix
        $this->assertEquals('ending in *7890', $result);
    }

    /**
     * @test testGetFedexAccountLast4WithToggleConfigTrueAndEmptyAccount
     */
    public function testGetFedexAccountLast4WithToggleConfigTrueAndEmptyAccount()
    {
        $this->checkoutSessionMock->expects($this->any())
            ->method(self::GET_QUOTE)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
            ->method(self::GET_DATA)
            ->with('fedex_account_number')
            ->willReturn(null);

        // Toggle is ON
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(Account::TOGGLE_CART_FXO_ACCOUNT_NUMBER_FIX)
            ->willReturn(true);

        $result = $this->accountMock->getFedexAccountLast4();
        $this->assertNull($result);
    }

    /**
     * @test testCanShowFedexAccountRemoveBtnWithDiscountAccountMatch
     */
    public function testCanShowFedexAccountRemoveBtnWithDiscountAccountMatch()
    {
        $this->checkoutSessionMock->expects($this->any())
            ->method(self::GET_QUOTE)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
            ->method(self::GET_DATA)
            ->with('fedex_account_number')
            ->willReturn('encryptedValue');

        $this->cartHelperMock->expects($this->any())
            ->method('decryptData')
            ->with('encryptedValue')
            ->willReturn('DISCOUNT123');

        $this->companyHelperMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn($this->companyInterfaceMock);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('SOMETHINGELSE');

        $this->companyInterfaceMock->expects($this->any())
            ->method('getDiscountAccountNumber')
            ->willReturn('DISCOUNT123');

        $this->companyInterfaceMock->expects($this->any())
            ->method('getDiscountAccountNumberEditable')
            ->willReturn(true);

        $this->assertEquals(true, $this->accountMock->canShowFedexAccountRemoveBtn());
    }

    /**
     * @test testCanShowFedexAccountRemoveBtnHandlesException
     */
    public function testCanShowFedexAccountRemoveBtnHandlesException()
    {
        $this->checkoutSessionMock->expects($this->any())
            ->method(self::GET_QUOTE)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
            ->method(self::GET_DATA)
            ->with('fedex_account_number')
            ->willReturn('encryptedValue');

        $this->cartHelperMock->expects($this->any())
            ->method('decryptData')
            ->with('encryptedValue')
            ->willReturn('DECRYPTED_VALUE');

        $this->companyHelperMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willThrowException(new \Exception('Simulated exception'));

        $logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $logger->expects($this->any())
            ->method('error')
            ->with($this->stringContains('Simulated exception'));

        // Inject the mock logger into the protected _logger property using reflection
        $reflection = new \ReflectionClass($this->accountMock);
        $loggerProperty = $reflection->getProperty('_logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue($this->accountMock, $logger);

        $this->assertTrue($this->accountMock->canShowFedexAccountRemoveBtn());
    }
}
