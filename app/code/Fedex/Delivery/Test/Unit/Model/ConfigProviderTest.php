<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Model;

use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Fedex\CustomerDetails\Helper\Data as CustomerDetailsHelper;
use Fedex\Delivery\Block\CartPickup;
use Fedex\Delivery\Helper\Data;
use Fedex\Delivery\Model\ConfigProvider;
use Fedex\Delivery\Test\Unit\Model\Person;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SubmitOrderSidebar\ViewModel\OrderSuccess;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Fedex\Base\Helper\Auth;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;

/**
 * ConfigProviderTest Model
 */
class ConfigProviderTest extends TestCase
{
    protected $_customerSession;
    protected $accountManagement;
    protected $scopeConfig;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $selfRegHelper;
    protected $isUploadToQuote;
    protected $orderApprovalViewModel;
    protected $getLoggedAsCustomerAdminIdMock;
    public const MEDIA_URL = 'https://magento.com/pub/media/wysiwyg';

    public const PAYMENT_INFO = '1234567890';

    /**
     * @var Data|MockObject
     */
    private $deliveryHelperDataMock;

    /**
     * @var CustomerDetailsHelper|MockObject
     */
    private $customerDetailsHelperMock;

    /**
     * @var CartPickup|MockObject
     */
    private $cartPickupMock;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

    /**
     * @var CheckoutConfig|MockObject
     */
    private $checkoutConfigMock;

    /**
     * @var ConfigProvider|MockObject
     */
    private $configProvider;

    /**
     * @var CompanyHelper|MockObject
     */
    private $companyHelper;

    /**
     * @var SdeHelper|MockObject
     */
    private $sdeHelper;

    /**
     * @var OrderSuccess|MockObject
     */
    private $orderSuccessViewModel;

    /**
     * @var UploadToQuoteViewModel $uploadToQuoteViewModel
     */
    protected $uploadToQuoteViewModel;

    /**
     * @var Region|MockObject
     */
    private $region;

    /**
     * @var RegionFactory|MockObject
     */
    private $regionFactory;

    protected Auth|MockObject $baseAuthMock;

    protected GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->deliveryHelperDataMock = $this->createMock(Data::class);
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->customerDetailsHelperMock = $this->createMock(CustomerDetailsHelper::class);
        $this->cartPickupMock = $this->createMock(CartPickup::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->checkoutConfigMock = $this->getMockBuilder(CheckoutConfig::class)
            ->setMethods(
                ['getDocumentImageUrl','getDocumentOfficeApiUrl','getAccountDiscountWarningFlag',
                'getAppliedFedexAccountNumber','isPromoDiscountEnabled','isAccountDiscountEnabled',
                'isTermsAndConditionsEnabled','getWarningMessage','getDocumentImagePreviewUrl'
                ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(
                ['getDuncResponse', 'getLoginValidationKey', 'getCustomerId', 'getCustomerCompany',
                'getProfileSession'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->accountManagement = $this->createMock(AccountManagementInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->companyHelper = $this->createMock(CompanyHelper::class);
        $this->sdeHelper = $this->createMock(SdeHelper::class);
        $this->orderSuccessViewModel = $this->createMock(OrderSuccess::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->selfRegHelper = $this->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
        ->setMethods(['isSelfRegCustomer', 'isSelfRegCustomerWithFclEnabled'])
        ->disableOriginalConstructor()
        ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)
            ->setMethods(
                [
                    'create',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->region = $this->getMockBuilder(Region::class)
            ->setMethods(
                [
                    'getId',
                    'loadByName'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->isUploadToQuote = $this->createMock(UploadToQuoteViewModel::class);

        $this->orderApprovalViewModel = $this->createMock(OrderApprovalViewModel::class);
        $this->getLoggedAsCustomerAdminIdMock = $this->getMockBuilder(GetLoggedAsCustomerAdminIdInterface::class)
            ->setMethods(
                [
                    'execute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $objectManagerHelper->getObject(
            ConfigProvider::class,
            [
                'helper' => $this->deliveryHelperDataMock,
                'retailHelper' => $this->customerDetailsHelperMock,
                'cartPickup' => $this->cartPickupMock,
                'toggleConfig' => $this->toggleConfigMock,
                'checkoutConfig' => $this->checkoutConfigMock,
                'customerSession' => $this->_customerSession,
                'accountManagement' => $this->accountManagement,
                'sdeHelper' => $this->sdeHelper,
                'scopeConfig' => $this->scopeConfig,
                'companyHelper' => $this->companyHelper,
                'orderSuccessViewModel' => $this->orderSuccessViewModel,
                'logger' => $this->loggerMock,
                'selfregHelper' => $this->selfRegHelper,
                'regionFactory' => $this->regionFactory,
                'isUploadToQuote' => $this->isUploadToQuote,
                'authHelper' => $this->baseAuthMock,
                'orderApprovalViewModel' => $this->orderApprovalViewModel,
                'getLoggedAsCustomerAdminId' => $this->getLoggedAsCustomerAdminIdMock
            ]
        );
    }
    /**
     * Test getConfig
     *
     * @return void
     */
    public function testGetConfig()
    {
        $mediaDir = 'wysiwyg';
        $documentOfficeApiUrl =
            'https://dunc.dmz.fedex.com/document/fedexoffice/v1/
        documents/contentReferenceId/preview?pageNumber=1&zoomFactor=0.2';

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->getLoggedAsCustomerAdminIdMock->expects($this->any())->method('execute')->willReturn(436);
        $this->cartPickupMock->expects($this->any())->method('getMediaUrl')->with($mediaDir)
        ->willReturn(self::MEDIA_URL);
        $this->deliveryHelperDataMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);

        $this->customerDetailsHelperMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('getIsPickup')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('getIsDelivery')->willReturn(true);
        $this->checkoutConfigMock->expects($this->any())->method('getDocumentOfficeApiUrl')
        ->willReturn($documentOfficeApiUrl);
        $this->checkoutConfigMock->expects($this->any())->method('getDocumentImageUrl')
        ->willReturn('test');
        $this->checkoutConfigMock->expects($this->any())->method('getAppliedFedexAccountNumber')
        ->willReturn('test');
        $this->checkoutConfigMock->expects($this->any())->method('isPromoDiscountEnabled')
        ->willReturn('test');
        $this->checkoutConfigMock->expects($this->any())->method('isAccountDiscountEnabled')
        ->willReturn('test');
        $this->checkoutConfigMock->expects($this->any())->method('isTermsAndConditionsEnabled')
        ->willReturn('test');
        $this->checkoutConfigMock->expects($this->any())->method('getWarningMessage')
        ->willReturn('test');
        $this->checkoutConfigMock->expects($this->any())->method('getDocumentImagePreviewUrl')
        ->willReturn('test');
        $this->deliveryHelperDataMock->expects($this->any())->method('getFCLCustomerLoggedInInfo')->willReturn([]);
        $this->_customerSession->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->_customerSession->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->_customerSession->expects($this->any())->method('getProfileSession')->willReturn(1);
        $person = new Person('John Doe', 'j.doe@example.com', '3333333333');
        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn($person);

        $defaultPaymentData = [
            'defaultMethod' => PaymentOptions::FEDEX_ACCOUNT_NUMBER,
            'paymentMethodInfo' => static::PAYMENT_INFO
        ];

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(4);

        $this->companyHelper->expects($this->any())->method('getDefaultPaymentMethod')->willReturn($defaultPaymentData);
        $this->companyHelper->expects($this->any())->method('getNonEditableCompanyCcPaymentMethod')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
        $this->_customerSession->expects($this->any())->method('getDuncResponse')->willReturn([]);
        $this->isUploadToQuote->expects($this->any())->method('isUploadToQuoteEnable')->willReturn(true);
        $this->orderApprovalViewModel->expects($this->any())->method('isOrderApprovalB2bEnabled')->willReturn(true);

        $this->assertNotNull($this->configProvider->getConfig());
    }
    /**
     * Test getConfig without customer id
     *
     * @return void
     */
    public function testGetConfigWithoutCustomerId()
    {
        $mediaDir = 'wysiwyg';
        $documentOfficeApiUrl = 'https://dunc.dmz.fedex.com';

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartPickupMock->expects($this->any())->method('getMediaUrl')->with($mediaDir)
        ->willReturn(self::MEDIA_URL);
        $this->deliveryHelperDataMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('getIsPickup')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('getIsDelivery')->willReturn(true);
        $this->checkoutConfigMock->expects($this->any())->method('getDocumentOfficeApiUrl')
        ->willReturn($documentOfficeApiUrl);
        $this->checkoutConfigMock->expects($this->any())->method('getDocumentImageUrl')
            ->willReturn('test');
        $this->deliveryHelperDataMock->expects($this->any())->method('getFCLCustomerLoggedInInfo')->willReturn([]);
        $this->_customerSession->expects($this->any())->method('getCustomerId')->willReturn(0);
        $person = new Person('John Doe', 'j.doe@example.com', '3333333333');
        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn($person);

        $defaultPaymentData = [
            'defaultMethod' => PaymentOptions::FEDEX_ACCOUNT_NUMBER,
            'paymentMethodInfo' => static::PAYMENT_INFO
        ];

        $this->companyHelper->expects($this->any())->method('getDefaultPaymentMethod')->willReturn($defaultPaymentData);
        $this->companyHelper->expects($this->any())->method('getNonEditableCompanyCcPaymentMethod')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
        $this->_customerSession->expects($this->any())->method('getDuncResponse')->willReturn([]);
        $this->assertNotNull($this->configProvider->getConfig());
    }

    /**
     * Test getConfig without default shipping address
     *
     * @return void
     */
    public function testGetConfigWithoutDefaultShippingAddess()
    {
        $this->_customerSession->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn(0);
        $defaultPaymentData = [
            'defaultMethod' => PaymentOptions::FEDEX_ACCOUNT_NUMBER,
            'paymentMethodInfo' => static::PAYMENT_INFO
        ];
        $this->companyHelper->expects($this->any())->method('getDefaultPaymentMethod')->willReturn($defaultPaymentData);
        $this->companyHelper->expects($this->any())->method('getNonEditableCompanyCcPaymentMethod')->willReturn(true);
        $this->deliveryHelperDataMock->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
        $this->checkoutConfigMock->expects($this->any())->method('getDocumentImageUrl')
            ->willReturn('test');

        $this->assertNotNull($this->configProvider->getConfig());
    }

    /**
     * Get Combined discount messages
     *
     * @return string
     */
    public function testGetCombinedDiscountMessage()
    {
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn('abcd');
        $this->assertEquals('abcd', $this->configProvider->getCombinedDiscountMessage());
    }

    /**
     * Test case for isLoggedIn
     */
    public function testisLoggedIn()
    {
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->assertNotNull($this->configProvider->isLoggedIn(true));
    }

    /**
     * Test case for isFromFCLCustomer
     */
    public function testIsFromFCLCustomer()
    {
        $this->sdeHelper->expects($this->once())->method('getIsRequestFromSdeStoreFclLogin')->willReturn(true);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(true);
        $this->_customerSession->expects($this->once())->method('getCustomerId')->willReturn(1);
        $this->_customerSession->expects($this->once())->method('getCustomerCompany')->willReturn(true);

        $this->assertTrue($this->configProvider->isFromFCLCustomer());
    }

    /**
     * Test case for isFromFCLCustomer
     */
    public function testIsFromFCLCustomerIsSelfRegCustomerWithFclEnabled()
    {
        $this->sdeHelper->expects($this->once())->method('getIsRequestFromSdeStoreFclLogin')->willReturn(false);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(true);
        $this->_customerSession->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->_customerSession->expects($this->once())->method('getCustomerCompany')->willReturn(false);

        $this->assertTrue($this->configProvider->isFromFCLCustomer());
    }

    /**
     * Test case for isFromFCLCustomer
     */
    public function testIsFromFCLCustomerIsSdeCustomerWithFclEnabled()
    {
        $this->sdeHelper->expects($this->once())->method('getIsRequestFromSdeStoreFclLogin')->willReturn(false);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(false);
        $this->_customerSession->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->_customerSession->expects($this->once())->method('getCustomerCompany')->willReturn(false);

        $this->assertTrue($this->configProvider->isFromFCLCustomer());
    }

    /**
     * Test case for isFromSelfRegFcl
     */
    public function testIsFromSelfRegFcl()
    {
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(true);
        $this->assertTrue($this->configProvider->isFromSelfRegFcl());
    }

    /**
     * Test case for isFromSelfRegFcl
     */
    public function testIsFromSelfRegFclWithElse()
    {
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(false);
        $this->assertFalse($this->configProvider->isFromSelfRegFcl());
    }
    public function testGetPaymentMethodDefaultValueWithFedexAccountAndExistingAccountNumber()
    {
        $preselectMethod = 'fedexaccountnumber';
        $paymentInfo = ['fedex_account' => '1234567890'];
        $result = $this->configProvider->getPaymentMethodDefaultValue($preselectMethod, $paymentInfo);
        $this->assertSame(null, $result);
    }

    public function testGetPaymentMethodDefaultValueWithFedexAccountAndNoExistingAccountNumber()
    {
        $preselectMethod = 'fedexaccountnumber';
        $paymentInfo = [];
        $result = $this->configProvider->getPaymentMethodDefaultValue($preselectMethod, $paymentInfo);
        $this->assertSame(null, $result);
    }

    public function testGetPaymentMethodDefaultValueWithCreditCardAndExistingCCNumber()
    {
        $preselectMethod = 'creditcard';
        $paymentInfo = ['ccNumber' => '1234567890123456'];
        $result = $this->configProvider->getPaymentMethodDefaultValue($preselectMethod, $paymentInfo);
        $this->assertSame($paymentInfo, $result);
    }

    public function testGetPaymentMethodDefaultValueWithNoPaymentInfo()
    {
        $preselectMethod = 'creditcard';
        $paymentInfo = null;
        $result = $this->configProvider->getPaymentMethodDefaultValue($preselectMethod, $paymentInfo);
        $this->assertNull($result);
    }

    public function testGetShippingAccountAcknowledgementMessage()
    {
        $expectedMessage = 'Please acknowledge the shipping account terms.';
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn($expectedMessage);
        $result = $this->configProvider->getShippingAccountAcknowledgementMessage();
        $this->assertEquals($expectedMessage, $result);
    }

    public function testGetShippingAccountAcknowledgementErrorMessage()
    {
        $expectedMessage = 'Error acknowledging the shipping account terms.';
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn($expectedMessage);
        $result = $this->configProvider->getShippingAccountAcknowledgementErrorMessage();
        $this->assertEquals($expectedMessage, $result);
    }
}
