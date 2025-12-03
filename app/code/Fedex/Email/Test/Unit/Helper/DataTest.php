<?php

declare(strict_types=1);

namespace Fedex\Email\Helper\Test\Unit\Helper;

use Fedex\Email\Helper\Data;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Framework\View\LayoutFactory;
use Fedex\Email\Helper\SendEmail;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceCheckout\Model\Email as EmailHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Customer\Model\Customer;

class DataTest extends TestCase
{
    private Data|MockObject $helper;
    private Session|MockObject $sessionMock;
    private CustomerRepositoryInterface|MockObject $customerRepoMock;
    private LayoutFactory|MockObject $layoutFactoryMock;
    private SendEmail|MockObject $mailMock;
    private PunchoutHelper|MockObject $punchoutHelperMock;
    private CompanyRepositoryInterface|MockObject $companyRepoMock;
    private CartRepositoryInterface|MockObject $quoteRepoMock;
    private ToggleConfig|MockObject $toggleConfigMock;
    private LoggerInterface|MockObject $loggerMock;
    private EmailHelper|MockObject $emailHelperMock;
    private ConfigInterface|MockObject $productBundleConfigMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer'])
            ->addMethods(['getCustomerCompany', 'getApiAccessToken', 'getApiAccessType'])
            ->getMock();

        $this->customerRepoMock         = $this->createMock(CustomerRepositoryInterface::class);
        $this->layoutFactoryMock        = $this->createMock(LayoutFactory::class);
        $this->mailMock                 = $this->createMock(SendEmail::class);
        $this->punchoutHelperMock       = $this->createMock(PunchoutHelper::class);
        $this->companyRepoMock          = $this->createMock(CompanyRepositoryInterface::class);
        $this->quoteRepoMock            = $this->createMock(CartRepositoryInterface::class);
        $this->toggleConfigMock         = $this->createMock(ToggleConfig::class);
        $this->loggerMock               = $this->createMock(LoggerInterface::class);
        $this->emailHelperMock          = $this->createMock(EmailHelper::class);
        $this->productBundleConfigMock  = $this->createMock(ConfigInterface::class);

        // Partial‐mock Data, stubbing only its two private helpers:
        $this->helper = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->createMock(Context::class),
                $this->sessionMock,
                $this->customerRepoMock,
                $this->layoutFactoryMock,
                $this->mailMock,
                $this->punchoutHelperMock,
                $this->companyRepoMock,
                $this->quoteRepoMock,
                $this->toggleConfigMock,
                $this->loggerMock,
                $this->emailHelperMock,
                $this->productBundleConfigMock
            ])
            ->onlyMethods(['getIsQuoteNotificationEnable', 'getQuoteTemplateData'])
            ->getMock();
    }

    public function testGetApiToken_WithValues(): void
    {
        $this->sessionMock->method('getApiAccessToken')->willReturn('TOK');
        $this->sessionMock->method('getApiAccessType')->willReturn('TYPE');
        $this->assertSame(['token' => 'TOK', 'type' => 'TYPE'], $this->helper->getApiToken());
    }

    public function testGetApiToken_WithoutValues(): void
    {
        $this->sessionMock->method('getApiAccessToken')->willReturn('');
        $this->sessionMock->method('getApiAccessType')->willReturn('');
        $this->assertSame(['token' => '', 'type' => ''], $this->helper->getApiToken());
    }

    public function testGetGateToken_DelegatesToPunchoutHelper(): void
    {
        $this->punchoutHelperMock->expects($this->once())
            ->method('getGatewayToken')->willReturn('GATE-TOK');
        $this->assertSame('GATE-TOK', $this->helper->getGateToken());
    }

    public function testSendEmailNotification_EproEnabled_Path(): void
    {
        $quoteId = 7;
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer'])
            ->addMethods(['getSubtotal', 'getDiscount', 'getGtn', 'getCustomerEmail'])
            ->getMock();
        $quote->method('getSubtotal')->willReturn(100);
        $quote->method('getDiscount')->willReturn(10);
        $quote->method('getGtn')->willReturn('GTN123');
        $quote->method('getCustomerEmail')->willReturn('q@cust.com');

        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getFirstName', 'getLastName', 'getEmail'])
            ->getMock();
        $customer->method('getId')->willReturn(55);
        $customer->method('getFirstName')->willReturn('John');
        $customer->method('getLastName')->willReturn('Smith');
        $customer->method('getEmail')->willReturn('john@example.com');

        $quote->method('getCustomer')->willReturn($customer);

        $this->quoteRepoMock->expects($this->once())
            ->method('get')->with($quoteId)->willReturn($quote);

        $this->sessionMock->method('getCustomerCompany')->willReturn(1);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturnMap([
            ['explorers_d_184291_fix', true],
            ['enable_epro_email', true],
        ]);

        $this->helper->method('getIsQuoteNotificationEnable')
            ->with($customer)->willReturn(true);
        $this->helper->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_quote_confirmation_template')
            ->willReturn(['template' => '<p>Hi</p>']);

        $this->punchoutHelperMock->method('getTazToken')->willReturn('TAZ');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('AUTH');
        $this->emailHelperMock->method('minifyHtml')->willReturn('Hi');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo');

        $expected = json_encode([
            'messages' => [
                'statement' => addslashes('Hi'),
                'url' => 'https://logo',
                'disclaimer' => ''
            ],
            'order' => ['contact' => ['email' => 'q@cust.com']]
        ], JSON_UNESCAPED_SLASHES);

        $this->mailMock->expects($this->once())
            ->method('sendMail')
            ->with(
                ['name' => 'John Smith', 'email' => 'q@cust.com'],
                'generic_template',
                $expected,
                ['access_token' => 'TAZ', 'auth_token' => 'AUTH'],
                null,
                Data::FEDEX_OFFICE . ' ' . Data::CHANNEL . ' - Quote Confirmation (Order GTN123)'
            )->willReturn('SENT_OK');

        $this->assertSame('SENT_OK', $this->helper->sendEmailNotification($quoteId));
    }

    public function testSendEmailNotification_LegacyTemplate_Path(): void
    {
        $quoteId = 8;
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer'])
            ->addMethods(['getSubtotal', 'getDiscount', 'getGtn', 'getCustomerEmail'])
            ->getMock();
        $quote->method('getSubtotal')->willReturn(50);
        $quote->method('getDiscount')->willReturn(5);
        $quote->method('getGtn')->willReturn('GTN456');
        $quote->method('getCustomerEmail')->willReturn('override@cust.com');

        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getFirstName', 'getLastName', 'getEmail'])
            ->getMock();
        $customer->method('getId')->willReturn(99);
        $customer->method('getFirstName')->willReturn('Alice');
        $customer->method('getLastName')->willReturn('Wong');
        $customer->method('getEmail')->willReturn('alice@x.com');

        $quote->method('getCustomer')->willReturn($customer);
        $this->quoteRepoMock->expects($this->once())
            ->method('get')->with($quoteId)->willReturn($quote);

        $this->sessionMock->method('getCustomerCompany')->willReturn(2);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturnMap([
            ['explorers_d_184291_fix', false],
            ['enable_epro_email', false],
        ]);

        $this->helper->method('getIsQuoteNotificationEnable')
            ->with($customer)->willReturn(true);

        $this->sessionMock->method('getApiAccessToken')->willReturn('T1');
        $this->sessionMock->method('getApiAccessType')->willReturn('TT1');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('AUTH1');
        $this->loggerMock->expects($this->never())->method('critical');

        $expected = [
            'order' => [
                'primaryContact' => ['firstLastName' => 'Alice Wong'],
                'gtn' => 'GTN456',
                'productionCostAmount' => '45'
            ],
            'producingCompany' => [
                'name' => Data::FEDEX_OFFICE,
                'customerRelationsPhone' => Data::CUSTOMER_RELATIONS_PHONE
            ],
            'user' => ['emailaddr' => 'alice@x.com'],
            'channel' => Data::CHANNEL
        ];

        $this->mailMock->expects($this->once())
            ->method('sendMail')
            ->with(
                ['name' => 'Alice Wong', 'email' => 'alice@x.com'],
                'ePro_quote_confirmation',
                json_encode($expected, JSON_UNESCAPED_SLASHES),
                ['access_token' => 'T1', 'auth_token' => 'AUTH1', 'token_type' => 'TT1'],
                null,
                null
            )->willReturn(true);

        $this->assertTrue($this->helper->sendEmailNotification($quoteId));
    }

    public function testSendEmailNotification_LogsErrorWhenTokensNotAvailable(): void
    {
        $quoteId = 9;
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer'])
            ->addMethods(['getSubtotal', 'getDiscount', 'getGtn', 'getCustomerEmail'])
            ->getMock();
        $quote->method('getSubtotal')->willReturn(50);
        $quote->method('getDiscount')->willReturn(5);
        $quote->method('getGtn')->willReturn('GTN789');
        $quote->method('getCustomerEmail')->willReturn('jane@example.com');

        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getFirstName', 'getLastName', 'getEmail'])
            ->getMock();
        $customer->method('getId')->willReturn(100);
        $customer->method('getFirstName')->willReturn('Jane');
        $customer->method('getLastName')->willReturn('Doe');
        $customer->method('getEmail')->willReturn('jane@example.com');

        $quote->method('getCustomer')->willReturn($customer);
        $this->quoteRepoMock->method('get')->with($quoteId)->willReturn($quote);

        $this->sessionMock->method('getCustomerCompany')->willReturn(3);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturnMap([
            ['explorers_d_184291_fix', false],
            ['enable_epro_email', false],
        ]);

        $this->helper->method('getIsQuoteNotificationEnable')
            ->with($customer)->willReturn(true);

        $this->sessionMock->method('getApiAccessToken')->willReturn('');
        $this->sessionMock->method('getApiAccessType')->willReturn('');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error retrieving token data'));

        $this->mailMock->expects($this->once())->method('sendMail')->willReturn(true);
        $this->assertTrue($this->helper->sendEmailNotification($quoteId));
    }

    public function testGetCustomer_FalseWhenNoId(): void
    {
        $custModel = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $custModel->method('getId')->willReturn(null);
        $this->sessionMock->method('getCustomer')->willReturn($custModel);

        $real = $this->createRealHelper();
        $this->assertFalse($real->getCustomer());
    }

    public function testGetCustomer_ReturnsWhenId(): void
    {
        $custModel = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $custModel->method('getId')->willReturn(42);

        $expected = $this->createMock(CustomerInterface::class);
        $this->sessionMock->method('getCustomer')->willReturn($custModel);
        $this->customerRepoMock->expects($this->once())
            ->method('getById')->with(42)->willReturn($expected);

        $real = $this->createRealHelper();
        $this->assertSame($expected, $real->getCustomer());
    }

    public function testGetAssignedCompany_UsesSessionCompany(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $this->sessionMock->method('getCustomerCompany')->willReturn(7);
        $this->companyRepoMock->expects($this->once())
            ->method('get')->with(7)->willReturn($company);
        $real = $this->createRealHelper();
        $this->assertSame($company, $real->getAssignedCompany(null));
    }

    public function testGetAssignedCompany_FallbacksToExtensionAttributes(): void
    {
        $company = $this->createMock(CompanyInterface::class);
        $cust = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(123);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $cust->method('getExtensionAttributes')->willReturn($ext);

        $this->sessionMock->method('getCustomerCompany')->willReturn(0);
        $this->companyRepoMock->expects($this->once())
            ->method('get')->with(123)->willReturn($company);

        $real = $this->createRealHelper();
        $this->assertSame($company, $real->getAssignedCompany($cust));
    }

    public function testGetIsQuoteNotificationEnable(): void
    {
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsQuoteRequest'])
            ->getMockForAbstractClass();
        $company->method('getIsQuoteRequest')->willReturn(true);
        $this->sessionMock->method('getCustomerCompany')->willReturn(5);
        $this->companyRepoMock->method('get')->willReturn($company);
        $real = $this->createRealHelper();
        $this->assertTrue($real->getIsQuoteNotificationEnable((object)[]));
    }

    public function testOrderRejectEmail_AndGetIsOrderRejectEnable(): void
    {
        $quoteId = 11;
        $po = 555;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(22);
        $quote->method('getCustomerFirstname')->willReturn('Sam');
        $quote->method('getCustomerLastname')->willReturn('Lee');
        $quote->method('getCustomerEmail')->willReturn('s@x.com');
        $quote->method('getGtn')->willReturn('GID');
        $quote->method('getGrandTotal')->willReturn(50.0);
        // any shipping‐address call returns a dummy Address
        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getShippingAddress', 'getBillingAddress', 'getShippingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });
        // a dummy Customer model simply to satisfy getCustomer()
        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock
            ->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(22);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock
            ->method('getById')
            ->with(22)
            ->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsOrderReject'])
            ->getMockForAbstractClass();
        $company->method('getIsOrderReject')->willReturn(true);
        $this->companyRepoMock
            ->method('get')
            ->with(22)
            ->willReturn($company);

        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['enable_epro_email', true],
                ['explorers_d_184291_fix', false],
            ]);

        $this->helper
            ->expects($this->once())
            ->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_order_rejection_template', ['po_number' => $po])
            ->willReturn(['template' => '<p>ignored</p>']);

        $this->emailHelperMock->method('minifyHtml')->willReturn('ignored');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo');

        $this->mailMock
            ->expects($this->once())
            ->method('sendMail')
            ->willReturn('R_OK');

        $this->punchoutHelperMock->method('getTazToken')->willReturn('TAZ');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('AUTH');
        $this->sessionMock->method('getApiAccessToken')->willReturn('TOKEN');
        $this->sessionMock->method('getApiAccessType')->willReturn('TYPE');

        $result = $this->helper->orderRejectEmail($quoteId, $po);
        $this->assertSame('R_OK', $result);

        $this->assertTrue($this->helper->getIsOrderRejectNotificationEnable($custInterface));
    }

    public function testOrderRejectEmail_LegacyTemplate_Path(): void
    {
        $quoteId = 42;
        $po = 999;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->onlyMethods(['getCustomer'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(7);
        $quote->method('getCustomerFirstname')->willReturn('Legacy');
        $quote->method('getCustomerLastname')->willReturn('User');
        $quote->method('getCustomerEmail')->willReturn('legacy@ex.com');
        $quote->method('getGtn')->willReturn('LG123');
        $quote->method('getGrandTotal')->willReturn(123.45);

        // always return a dummy customer object from Quote
        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock
            ->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(7);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock
            ->method('getById')
            ->with(7)
            ->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsOrderReject'])
            ->getMockForAbstractClass();
        $company->method('getIsOrderReject')->willReturn(true);
        $this->companyRepoMock
            ->method('get')
            ->with(7)
            ->willReturn($company);

        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['enable_epro_email', false],
                ['explorers_d_184291_fix', false],
            ]);

        $this->punchoutHelperMock->method('getTazToken')->willReturn('T1');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('T2');

        $expectedTemplateD = [
            'order' => [
                'primaryContact' => ['firstLastName' => 'Legacy User'],
                'gtn' => 'LG123',
                'productionCostAmount' => 123.45,
                'rejectionReason' =>
                'Purchase Order # 999 does not match Order 42. Please resubmit a corrected order',
            ],
            'producingCompany' => [
                'name' => Data::FEDEX_OFFICE,
                'customerRelationsPhone' => Data::CUSTOMER_RELATIONS_PHONE,
            ],
            'user' => [
                // the code concatenates MAIL_TO, email, bracket, email
                'emailaddr' => Data::MAIL_TO . 'legacy@ex.com' . ' ] ' . 'legacy@ex.com',
            ],
            'channel' => Data::CHANNEL,
        ];
        $expectedJson = json_encode($expectedTemplateD, JSON_UNESCAPED_SLASHES);

        $this->mailMock
            ->expects($this->once())
            ->method('sendMail')
            ->with(
                ['name' => 'Legacy User', 'email' => 'legacy@ex.com'],
                'ePro_order_rejected',
                $expectedJson,
                ['access_token' => 'T1', 'auth_token' => 'T2'],
                null,
                null
            )
            ->willReturn('LEGACY_OK');

        $result = $this->helper->orderRejectEmail($quoteId, $po);
        $this->assertSame('LEGACY_OK', $result);
    }

    public function testOrderRejectEmail_LogsError_WhenNameOrEmailEmpty(): void
    {
        $quoteId = 11;
        $po = 555;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(22);
        $quote->method('getCustomerFirstname')->willReturn(''); // Empty first name
        $quote->method('getCustomerLastname')->willReturn('');  // Empty last name
        $quote->method('getCustomerEmail')->willReturn(''); //email empty to trigger the error path
        $quote->method('getGtn')->willReturn('GID');
        $quote->method('getGrandTotal')->willReturn(50.0);

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getShippingAddress', 'getBillingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(22);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsOrderReject'])
            ->getMockForAbstractClass();
        $company->method('getIsOrderReject')->willReturn(true);
        $this->companyRepoMock->method('get')->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnMap([
                ['enable_epro_email', true],
                ['explorers_d_184291_fix', false]
            ]);

        $this->helper
            ->expects($this->once())
            ->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_order_rejection_template', ['po_number' => $po])
            ->willReturn(['template' => '<p>ignored</p>']);

        $this->emailHelperMock->method('minifyHtml')->willReturn('ignored');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains(Data::ERR_MSG));

        $this->mailMock->expects($this->never())->method('sendMail');

        $result = $this->helper->orderRejectEmail($quoteId, $po);
        $this->assertNull($result);
    }

    public function testOrderExpiredEmail_AndGetIsExpiredEnable(): void
    {
        $quoteId = 12;
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(33);
        $quote->method('getCustomerFirstname')->willReturn('Pat');
        $quote->method('getCustomerLastname')->willReturn('O');
        $quote->method('getCustomerEmail')->willReturn('pat@x.com');
        $quote->method('getGtn')->willReturn('GEXP');
        $quote->method('getGrandTotal')->willReturn(75.0);

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getAddress', 'getBillingAddress', 'getShippingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $customer = $this->createMock(Customer::class);
        $quote->method('getCustomer')->willReturn($customer);

        $this->quoteRepoMock->method('get')->with($quoteId)->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(33);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->with(33)->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsExpiredOrder'])  // Add this method to the mock
            ->getMockForAbstractClass();
        $company->method('getIsExpiredOrder')->willReturn(true);
        $this->companyRepoMock->method('get')->with(33)->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturnMap([['enable_epro_email', false]]);
        $this->sessionMock->method('getApiAccessToken')->willReturn('A1');
        $this->sessionMock->method('getApiAccessType')->willReturn('T1');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('AT1');
        $this->mailMock->expects($this->once())->method('sendMail')->willReturn('EOK');

        $real = $this->createRealHelper();
        $this->assertSame('EOK', $real->orderExpiredEmail($quoteId));
        $this->assertTrue($real->getIsOrderExpiredNotificationEnable($custInterface));
    }

    public function testOrderExpiredEmail_EnableEproEmail_Path(): void
    {
        $quoteId = 15;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(42);
        $quote->method('getCustomerFirstname')->willReturn('Jane');
        $quote->method('getCustomerLastname')->willReturn('Smith');
        $quote->method('getCustomerEmail')->willReturn('jane@example.com');
        $quote->method('getGtn')->willReturn('GTN-EXP');
        $quote->method('getGrandTotal')->willReturn(75.0);

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getShippingAddress', 'getBillingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(42);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsExpiredOrder'])
            ->getMockForAbstractClass();
        $company->method('getIsExpiredOrder')->willReturn(true);
        $this->companyRepoMock->method('get')->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('enable_epro_email')
            ->willReturn(true);

        $this->helper->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_expired_quote_template')
            ->willReturn(['template' => '<p>Expired notification</p>']);

        $this->emailHelperMock->method('minifyHtml')->willReturn('Expired notification');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo-url');

        $this->punchoutHelperMock->method('getTazToken')->willReturn('TAZ-TOKEN');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('AUTH-TOKEN');

        $expectedTemplateData = json_encode([
            "messages" => [
                "statement" => addslashes('Expired notification'),
                "url" => 'https://logo-url',
                "disclaimer" => ''
            ],
            "order" => [
                "contact" => [
                    "email" => 'jane@example.com'
                ]
            ]
        ], JSON_UNESCAPED_SLASHES);

        $this->mailMock->expects($this->once())
            ->method('sendMail')
            ->with(
                ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
                'generic_template',
                $expectedTemplateData,
                ['access_token' => 'TAZ-TOKEN', 'auth_token' => 'AUTH-TOKEN'],
                null,
                Data::FEDEX_OFFICE . ' ' . Data::CHANNEL . ' - Order Expired Confirmation (Order GTN-EXP)'
            )
            ->willReturn('EXPIRED_OK');

        $result = $this->helper->orderExpiredEmail($quoteId);
        $this->assertSame('EXPIRED_OK', $result);
    }

    public function testOrderExpiredEmail_LogsError_WhenNameOrEmailEmpty(): void
    {
        $quoteId = 16;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(43);
        $quote->method('getCustomerFirstname')->willReturn(''); // Empty first name
        $quote->method('getCustomerLastname')->willReturn('');  // Empty last name
        $quote->method('getCustomerEmail')->willReturn(''); // Empty email
        $quote->method('getGtn')->willReturn('GTN-EMP');
        $quote->method('getGrandTotal')->willReturn(100.0);

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getShippingAddress', 'getBillingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(43);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsExpiredOrder'])
            ->getMockForAbstractClass();
        $company->method('getIsExpiredOrder')->willReturn(true);
        $this->companyRepoMock->method('get')->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnMap([
                ['enable_epro_email', true]
            ]);

        $this->helper->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_expired_quote_template')
            ->willReturn(['template' => '<p>Never used</p>']);

        $this->emailHelperMock->method('minifyHtml')->willReturn('ignored');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo-url');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains(Data::ERR_MSG));

        $this->mailMock->expects($this->never())->method('sendMail');

        $result = $this->helper->orderExpiredEmail($quoteId);
        $this->assertNull($result);
    }

    public function testOrderExpiringEmail_AndGetIsExpiringEnable(): void
    {
        $quoteId = 13;
        $exp = '2025-06-01';
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(44);
        $quote->method('getCustomerFirstname')->willReturn('X');
        $quote->method('getCustomerLastname')->willReturn('Y');
        $quote->method('getCustomerEmail')->willReturn('xy@x.com');
        $quote->method('getGtn')->willReturn('GPEXP');
        $quote->method('getCreatedAt')->willReturn('2025-05-01');
        $quote->method('getGrandTotal')->willReturn(25.0);

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getAddress', 'getBillingAddress', 'getShippingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $customer = $this->createMock(Customer::class);
        $quote->method('getCustomer')->willReturn($customer);

        $this->quoteRepoMock->method('get')->with($quoteId)->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(44);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->with(44)->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsExpiringOrder'])  // Add this method to the mock
            ->getMockForAbstractClass();
        $company->method('getIsExpiringOrder')->willReturn(true);
        $this->companyRepoMock->method('get')->with(44)->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturnMap([['enable_epro_email', false]]);
        $this->mailMock->expects($this->once())->method('sendMail')->willReturn('PEX');

        $real = $this->createRealHelper();
        $this->assertSame('PEX', $real->orderExpiringEmail($quoteId, $exp));
        $this->assertTrue($real->getIsOrderExpiringOrderNotificationEnable($custInterface));
    }

    public function testOrderExpiringEmail_EnableEproEmail_Path(): void
    {
        $quoteId = 17;
        $expiringDate = '2025-06-15';

        // 1) Build a Quote stub with proper values
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', 'getCreatedAt', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(45);
        $quote->method('getCustomerFirstname')->willReturn('Robert');
        $quote->method('getCustomerLastname')->willReturn('Johnson');
        $quote->method('getCustomerEmail')->willReturn('robert@example.com');
        $quote->method('getGtn')->willReturn('GTN-EXP-17');
        $quote->method('getGrandTotal')->willReturn(120.0);
        $quote->method('getCreatedAt')->willReturn('2025-05-15');

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getShippingAddress', 'getBillingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(45);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->willReturn($custInterface);

        //Company settings - make expiring order notifications enabled
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsExpiringOrder'])
            ->getMockForAbstractClass();
        $company->method('getIsExpiringOrder')->willReturn(true);
        $this->companyRepoMock->method('get')->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('enable_epro_email')
            ->willReturn(true);

        $expectedExpireDate = ['expire_date' => 'June 15, 2025'];
        $this->helper->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_expiring_quote_template', $expectedExpireDate)
            ->willReturn(['template' => '<p>Expiring notification</p>']);

        $this->emailHelperMock->method('minifyHtml')->willReturn('Expiring notification');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo-url');

        $this->punchoutHelperMock->method('getTazToken')->willReturn('TAZ-TOKEN');
        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('AUTH-TOKEN');

        $expectedTemplateData = json_encode([
            "messages" => [
                "statement" => addslashes('Expiring notification'),
                "url" => 'https://logo-url',
                "disclaimer" => ''
            ],
            "order" => [
                "contact" => [
                    "email" => 'robert@example.com'
                ]
            ]
        ], JSON_UNESCAPED_SLASHES);

        $this->mailMock->expects($this->once())
            ->method('sendMail')
            ->with(
                ['name' => 'Robert Johnson', 'email' => 'robert@example.com'],
                'generic_template',
                $expectedTemplateData,
                ['access_token' => 'TAZ-TOKEN', 'auth_token' => 'AUTH-TOKEN'],
                null,
                Data::FEDEX_OFFICE . ' ' . Data::CHANNEL . ' - Expiring Quote Notification (Order GTN-EXP-17)'
            )
            ->willReturn('EXPIRING_OK');

        $result = $this->helper->orderExpiringEmail($quoteId, $expiringDate);
        $this->assertSame('EXPIRING_OK', $result);
    }

    public function testOrderExpiringEmail_LogsError_WhenNameOrEmailEmpty(): void
    {
        $quoteId = 18;
        $expiringDate = '2025-07-01';

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', '__call'])
            ->addMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGtn', 'getGrandTotal'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(46);
        $quote->method('getCustomerFirstname')->willReturn(''); // Empty first name
        $quote->method('getCustomerLastname')->willReturn('');  // Empty last name
        $quote->method('getCustomerEmail')->willReturn(''); // Empty email
        $quote->method('getGtn')->willReturn('GTN-EMPTY');
        $quote->method('getGrandTotal')->willReturn(150.0);

        $quote->method('__call')->willReturnCallback(function ($method, $args) {
            if (in_array($method, ['getShippingAddress', 'getBillingAddress'])) {
                return $this->createMock(Address::class);
            }
            return null;
        });

        $quote->method('getCustomer')->willReturn($this->createMock(Customer::class));

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $custInterface = $this->createMock(CustomerInterface::class);
        $ext = $this->createMock(CustomerExtensionInterface::class);
        $attrs = $this->createMock(CompanyCustomerInterface::class);
        $attrs->method('getCompanyId')->willReturn(46);
        $ext->method('getCompanyAttributes')->willReturn($attrs);
        $custInterface->method('getExtensionAttributes')->willReturn($ext);
        $this->customerRepoMock->method('getById')->willReturn($custInterface);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsExpiringOrder'])
            ->getMockForAbstractClass();
        $company->method('getIsExpiringOrder')->willReturn(true);
        $this->companyRepoMock->method('get')->willReturn($company);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->willReturnMap([
                ['enable_epro_email', true]
            ]);

        $expectedExpireDate = ['expire_date' => 'July 01, 2025'];
        $this->helper->method('getQuoteTemplateData')
            ->with($quoteId, 'ePro_expiring_quote_template', $expectedExpireDate)
            ->willReturn(['template' => '<p>Never used</p>']);

        $this->emailHelperMock->method('minifyHtml')->willReturn('ignored');
        $this->emailHelperMock->method('getEmailLogoUrl')->willReturn('https://logo-url');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains(Data::ERR_MSG));

        $this->mailMock->expects($this->never())->method('sendMail');

        $result = $this->helper->orderExpiringEmail($quoteId, $expiringDate);
        $this->assertNull($result);
    }

    public function testQuoteItemHtml(): void
    {
        $layout = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createBlock'])
            ->getMock();

        $block = $this->getMockBuilder(\Fedex\Email\Block\Order\Email\Items::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml', 'setData'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();

        $block->expects($this->once())->method('setName')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->willReturnSelf();
        $block->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('<li>OK</li>');

        $layout->expects($this->once())
            ->method('createBlock')
            ->with('Fedex\Email\Block\Order\Email\Items')
            ->willReturn($block);

        $this->layoutFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($layout);

        $real = $this->createRealHelper();
        $this->assertStringContainsString('<li>OK</li>', $real->quoteItemHtml([], []));
    }

    public function testGetQuoteTemplateData(): void
    {
        $quoteId = 14;
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getItemsCollection', 'getCreatedAt'])
            ->addMethods([
                'getSubtotal',
                'getDiscount',
                'getCustomTaxAmount',
                'getGrandTotal',
                'getAccountDiscount',
                'getVolumeDiscount',
                'getPromoDiscount',
                'getShippingDiscount',
                'getCustomerFirstname',
                'getCustomerLastname'
            ])
            ->getMock();

        $addr = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingMethod', 'getData'])
            ->getMock();
        $addr->method('getShippingMethod')->willReturn('METH');
        $addr->method('getData')->willReturn([
            'shipping_description' => 'S - D',
            'shipping_amount' => 5
        ]);

        $layout = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createBlock'])
            ->getMock();

        $block = $this->getMockBuilder(\Fedex\Email\Block\Order\Email\Items::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml', 'setData'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();

        $block->expects($this->once())->method('setName')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->willReturnSelf();
        $block->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('<li>OK</li>');

        $layout->expects($this->once())
            ->method('createBlock')
            ->with('Fedex\Email\Block\Order\Email\Items')
            ->willReturn($block);

        $this->layoutFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($layout);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getName', 'getQty'])
            ->addMethods(['getRowTotal'])
            ->getMock();
        $item->method('getItemId')->willReturn(77);
        $item->method('getName')->willReturn('Foo');
        $item->method('getQty')->willReturn(2);
        $item->method('getRowTotal')->willReturn(10.5);

        $quote->method('getShippingAddress')->willReturn($addr);
        $quote->method('getItemsCollection')->willReturn([$item]);
        $quote->method('getSubtotal')->willReturn(20.0);
        $quote->method('getDiscount')->willReturn(1.0);
        $quote->method('getCustomTaxAmount')->willReturn(2.0);
        $quote->method('getGrandTotal')->willReturn(21.0);
        $quote->method('getAccountDiscount')->willReturn(0.5);
        $quote->method('getVolumeDiscount')->willReturn(0.6);
        $quote->method('getPromoDiscount')->willReturn(0.7);
        $quote->method('getShippingDiscount')->willReturn(0.8);
        $quote->method('getCustomerFirstname')->willReturn('A');
        $quote->method('getCustomerLastname')->willReturn('B');
        $quote->method('getCreatedAt')->willReturn('2025-04-01');

        $this->quoteRepoMock->method('get')->with($quoteId)->willReturn($quote);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('sgc_enable_expected_delivery_date')
            ->willReturn(true);

        $real = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->createMock(Context::class),
                $this->sessionMock,
                $this->customerRepoMock,
                $this->layoutFactoryMock,
                $this->mailMock,
                $this->punchoutHelperMock,
                $this->companyRepoMock,
                $this->quoteRepoMock,
                $this->toggleConfigMock,
                $this->loggerMock,
                $this->emailHelperMock,
                $this->productBundleConfigMock
            ])
            ->onlyMethods([])
            ->getMock();

        $this->emailHelperMock->method('getEmailHtml')
            ->willReturn(['content' => 'OK']);

        $out = $real->getQuoteTemplateData($quoteId, 't', []);
        $this->assertIsArray($out);
        $this->assertSame(['content' => 'OK'], $out);
    }

    public function testGetQuoteTemplateDataWithProductBundleEnabled(): void
    {
        $this->productBundleConfigMock->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $quoteId = 14;
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getItemsCollection', 'getCreatedAt'])
            ->addMethods([
                'getSubtotal',
                'getDiscount',
                'getCustomTaxAmount',
                'getGrandTotal',
                'getAccountDiscount',
                'getVolumeDiscount',
                'getBundleDiscount',
                'getPromoDiscount',
                'getShippingDiscount',
                'getCustomerFirstname',
                'getCustomerLastname'
            ])
            ->getMock();

        $addr = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingMethod', 'getData'])
            ->getMock();
        $addr->method('getShippingMethod')->willReturn('METH');
        $addr->method('getData')->willReturn([
            'shipping_description' => 'S - D',
            'shipping_amount' => 5
        ]);

        $layout = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createBlock'])
            ->getMock();

        $block = $this->getMockBuilder(\Fedex\Email\Block\Order\Email\Items::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml', 'setData'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();

        $block->expects($this->once())->method('setName')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->willReturnSelf();
        $block->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('<li>OK</li>');

        $layout->expects($this->once())
            ->method('createBlock')
            ->with('Fedex\Email\Block\Order\Email\Items')
            ->willReturn($block);

        $this->layoutFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($layout);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getName', 'getQty'])
            ->addMethods(['getRowTotal'])
            ->getMock();
        $item->method('getItemId')->willReturn(77);
        $item->method('getName')->willReturn('Foo');
        $item->method('getQty')->willReturn(2);
        $item->method('getRowTotal')->willReturn(10.5);

        $quote->method('getShippingAddress')->willReturn($addr);
        $quote->method('getItemsCollection')->willReturn([$item]);
        $quote->method('getSubtotal')->willReturn(20.0);
        $quote->method('getDiscount')->willReturn(1.0);
        $quote->method('getCustomTaxAmount')->willReturn(2.0);
        $quote->method('getGrandTotal')->willReturn(21.0);
        $quote->method('getAccountDiscount')->willReturn(0.5);
        $quote->method('getVolumeDiscount')->willReturn(0.6);
        $quote->method('getBundleDiscount')->willReturn(0.9);
        $quote->method('getPromoDiscount')->willReturn(0.7);
        $quote->method('getShippingDiscount')->willReturn(0.8);
        $quote->method('getCustomerFirstname')->willReturn('A');
        $quote->method('getCustomerLastname')->willReturn('B');
        $quote->method('getCreatedAt')->willReturn('2025-04-01');

        $this->quoteRepoMock->method('get')->with($quoteId)->willReturn($quote);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('sgc_enable_expected_delivery_date')
            ->willReturn(true);

        $real = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->createMock(Context::class),
                $this->sessionMock,
                $this->customerRepoMock,
                $this->layoutFactoryMock,
                $this->mailMock,
                $this->punchoutHelperMock,
                $this->companyRepoMock,
                $this->quoteRepoMock,
                $this->toggleConfigMock,
                $this->loggerMock,
                $this->emailHelperMock,
                $this->productBundleConfigMock
            ])
            ->onlyMethods([])
            ->getMock();

        $this->emailHelperMock->method('getEmailHtml')
            ->willReturn(['content' => 'OK']);

        $out = $real->getQuoteTemplateData($quoteId, 't', []);
        $this->assertIsArray($out);
        $this->assertSame(['content' => 'OK'], $out);
    }

    public function testGetQuoteTemplateData_WithDeliveryDateDisabled(): void
    {
        $quoteId = 20;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getItemsCollection', 'getCreatedAt'])
            ->addMethods([
                'getSubtotal',
                'getDiscount',
                'getCustomTaxAmount',
                'getGrandTotal',
                'getAccountDiscount',
                'getVolumeDiscount',
                'getPromoDiscount',
                'getShippingDiscount',
                'getCustomerFirstname',
                'getCustomerLastname'
            ])
            ->getMock();

        $addr = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingMethod', 'getData'])
            ->getMock();
        $addr->method('getShippingMethod')->willReturn('fedexshipping_STANDARD');
        $addr->method('getData')->willReturn([
            'shipping_description' => 'Standard Shipping - Arrives by Jun 1',
            'shipping_amount' => 10.99
        ]);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getName', 'getQty'])
            ->addMethods(['getRowTotal'])
            ->getMock();
        $item->method('getItemId')->willReturn(123);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getQty')->willReturn(2);
        $item->method('getRowTotal')->willReturn(29.99);

        $quote->method('getShippingAddress')->willReturn($addr);
        $quote->method('getItemsCollection')->willReturn([$item]);
        $quote->method('getCreatedAt')->willReturn('2025-05-25');

        $quote->method('getSubtotal')->willReturn(29.99);
        $quote->method('getDiscount')->willReturn(0);
        $quote->method('getCustomTaxAmount')->willReturn(3.00);
        $quote->method('getGrandTotal')->willReturn(43.98);
        $quote->method('getAccountDiscount')->willReturn(0);
        $quote->method('getVolumeDiscount')->willReturn(0);
        $quote->method('getPromoDiscount')->willReturn(0);
        $quote->method('getShippingDiscount')->willReturn(0);
        $quote->method('getCustomerFirstname')->willReturn('John');
        $quote->method('getCustomerLastname')->willReturn('Doe');

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('sgc_enable_expected_delivery_date')
            ->willReturn(false);

        // Add layout factory setup
        $layout = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createBlock'])
            ->getMock();

        $block = $this->getMockBuilder(\Fedex\Email\Block\Order\Email\Items::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml', 'setData'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();

        $block->expects($this->once())->method('setName')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->willReturnSelf();
        $block->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('<li>Item HTML</li>');

        $layout->expects($this->once())
            ->method('createBlock')
            ->with('Fedex\Email\Block\Order\Email\Items')
            ->willReturn($block);

        $this->layoutFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($layout);

        $expectedShippingLabel = 'Standard Shipping';
        $this->emailHelperMock->method('getEmailHtml')
            ->willReturnCallback(function ($templateId, $data) use ($expectedShippingLabel) {
                // Verification logic...
                return ['content' => 'Email template content'];
            });

        $real = $this->createRealHelper();
        $result = $real->getQuoteTemplateData($quoteId, 'test_template', []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
    }

    public function testGetQuoteTemplateData_WithExpireDateAndPoNumber(): void
    {
        $quoteId = 21;
        $templateId = 'test_template_with_details';

        // Create otherDetails with both expire_date and po_number
        $otherDetails = [
            'expire_date' => 'August 15, 2025',
            'po_number' => 'PO-12345'
        ];

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getItemsCollection', 'getCreatedAt'])
            ->addMethods([
                'getSubtotal',
                'getDiscount',
                'getCustomTaxAmount',
                'getGrandTotal',
                'getAccountDiscount',
                'getVolumeDiscount',
                'getPromoDiscount',
                'getShippingDiscount',
                'getCustomerFirstname',
                'getCustomerLastname'
            ])
            ->getMock();

        $quote->method('getCreatedAt')->willReturn('2025-06-01');
        $quote->method('getCustomerFirstname')->willReturn('Test');
        $quote->method('getCustomerLastname')->willReturn('User');

        $addr = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingMethod', 'getData'])
            ->getMock();
        $addr->method('getShippingMethod')->willReturn('fedexshipping_STANDARD');
        $addr->method('getData')->willReturn([
            'shipping_description' => 'Standard Shipping',
            'shipping_amount' => 5.99
        ]);
        $quote->method('getShippingAddress')->willReturn($addr);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getName', 'getQty'])
            ->addMethods(['getRowTotal'])
            ->getMock();
        $item->method('getItemId')->willReturn(101);
        $item->method('getName')->willReturn('Test Item');
        $item->method('getQty')->willReturn(1);
        $item->method('getRowTotal')->willReturn(19.99);

        $quote->method('getItemsCollection')->willReturn([$item]);

        $quote->method('getSubtotal')->willReturn(19.99);
        $quote->method('getDiscount')->willReturn(0);
        $quote->method('getCustomTaxAmount')->willReturn(1.50);
        $quote->method('getGrandTotal')->willReturn(27.48);
        $quote->method('getAccountDiscount')->willReturn(0);
        $quote->method('getVolumeDiscount')->willReturn(0);
        $quote->method('getPromoDiscount')->willReturn(0);
        $quote->method('getShippingDiscount')->willReturn(0);

        $this->quoteRepoMock->method('get')
            ->with($quoteId)
            ->willReturn($quote);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('sgc_enable_expected_delivery_date')
            ->willReturn(true);

        $layout = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createBlock'])
            ->getMock();

        $block = $this->getMockBuilder(\Fedex\Email\Block\Order\Email\Items::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml', 'setData'])
            ->addMethods(['setName', 'setArea'])
            ->getMock();

        $block->expects($this->once())->method('setName')->willReturnSelf();
        $block->expects($this->once())->method('setArea')->willReturnSelf();
        $block->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $block->expects($this->once())->method('toHtml')->willReturn('<div>Item content</div>');

        $layout->expects($this->once())
            ->method('createBlock')
            ->willReturn($block);

        $this->layoutFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($layout);

        $this->emailHelperMock->expects($this->once())
            ->method('getEmailHtml')
            ->with(
                $this->equalTo($templateId),
                $this->callback(function ($data) use ($otherDetails) {
                    // Verify that both conditional branches were executed
                    return isset($data['quote_expiring_date']) &&
                        $data['quote_expiring_date'] === $otherDetails['expire_date'] &&
                        isset($data['quote_placed_date']) &&
                        isset($data['po_number']) &&
                        $data['po_number'] === $otherDetails['po_number'];
                })
            )
            ->willReturn(['content' => 'Template with expire date and PO number']);

        $this->emailHelperMock->method('getFormattedCstDate')
            ->willReturn('June 01, 2025');

        $real = $this->createRealHelper();
        $result = $real->getQuoteTemplateData($quoteId, $templateId, $otherDetails);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('Template with expire date and PO number', $result['content']);
    }

    private function createRealHelper(): Data
    {
        return (new ObjectManagerHelper($this))
            ->getObject(Data::class, [
                'context'            => $this->createMock(Context::class),
                'customerSession'    => $this->sessionMock,
                'customerRepository' => $this->customerRepoMock,
                'layoutFactory'      => $this->layoutFactoryMock,
                'mail'               => $this->mailMock,
                'punchoutHelper'     => $this->punchoutHelperMock,
                'companyRepository'  => $this->companyRepoMock,
                'quoteRepository'    => $this->quoteRepoMock,
                'toggleConfig'       => $this->toggleConfigMock,
                'logger'             => $this->loggerMock,
                'emailHelper'        => $this->emailHelperMock
            ]);
    }
}
