<?php

/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 *
 * PHP version 7
 *
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Fedex <info@fedex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html
 * GNU General Public License,version 3 (GPL-3.0)
 * @link      http://fedex.com
 */

declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Helper;

use Exception;
use Fedex\Company\Helper\Data;
use Fedex\Company\Model\Config\Source\CredtiCardOptions;
use Fedex\Company\Model\Config\Source\FedExAccountOptions;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Fedex\Company\Api\Data\AdditionalDataInterface;
use Fedex\Company\Model\AdditionalData;
use ReflectionClass;
use Fedex\Company\Model\Config\Source\PaymentAcceptance;

/**
 * Unit tests for Company Helper Data.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $deliveryHelperMock;
    protected $customerRepositoryMock;
    protected $toggleConfig;
    protected $additionalDataMock;
    protected $companyMock;
    protected $companyExtensionInterfaceMock;
    protected $companyRepositoryInterfaceMock;
    protected $jsonMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime\DateTimeFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dateTimeFactoryMock;
    protected $dateTimeMock;
    protected $resourceConnectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataMock;
    protected $reflectionClass;
    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['getCustomer', 'getAssignedCompany', 'isEproCustomer', 'isSdeCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getPaymentOption'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanyAdditionalData',
                'setCompanyAdditionalData',
                'getIsPromoDiscountEnabled',
                'getIsAccountDiscountEnabled',
                'getOrderNotes',
                'getTermsAndConditions',
                'getIsReorderEnabled',
                'getIsNonEditableCcPaymentMethod'
            ])->getMockForAbstractClass();
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(
                [
                    'getPaymentOption',
                    'getFedexAccountNumber',
                    'getDiscountAccountNumber',
                    'getEnableUploadSection',
                    'getShippingAccountNumber',
                    'getExtensionAttributes',
                    'getStorefrontLoginMethodOption',
                    'getRecipientAddressFromPo'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyExtensionInterfaceMock = $this->getMockBuilder(CompanyExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanyPaymentOptions',
                'setCompanyPaymentOptions',
                'getFedexAccountOptions',
                'getCreditcardOptions',
                'getDefaultPaymentMethod',
                'getCcToken',
                'getCcData',
                'getCcTokenExpiryDateTime',
                ])
            ->addMethods([
                'getIsPromoDiscountEnabled',
                'getIsAccountDiscountEnabled',
                'getOrderNotes',
                'getTermsAndConditions',
                'getIsReorderEnabled',
            ])
            ->getMockForAbstractClass();

        $this->companyRepositoryInterfaceMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($serializedData) {
                    return json_decode($serializedData, true);
                }
            );

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['gmtTimeStamp'])
            ->getMockForAbstractClass();

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->dataMock = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'companyRepository' => $this->companyRepositoryInterfaceMock,
                'json' => $this->jsonMock,
                'dateTime' => $this->dateTimeMock,
                'logger' => $this->loggerMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );

        $this->reflectionClass = new ReflectionClass($this->dataMock);
    }

    /**
     * TestGetCompanyPaymentMethodWithNewPaymentConfiguration
     *
     * @return void
     */
    public function testGetCompanyPaymentMethodWithNewPaymentConfiguration()
    {
        $paymentMethodOutputArray = [
            PaymentOptions::FEDEX_ACCOUNT_NUMBER => 'custom_fedex_account',
            PaymentOptions::CREDIT_CARD => 'new_credit_card'
        ];

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethods();

        $this->assertEquals(
            $paymentMethodOutputArray,
            $this->dataMock->getCompanyPaymentMethod()
        );
    }

    /**
     * TestGetCompanyPaymentMethodWithNewPaymentConfiguration
     *
     * @return array
     */
    // @codingStandardsIgnoreStart
    public function testGetCompanyPaymentMethodWithCreditCardNewPaymentConfiguration()
    {
        //@codingStandardsIgnoreEnd
        $paymentMethodOutputArray = [
            PaymentOptions::FEDEX_ACCOUNT_NUMBER => 'custom_fedex_account',
            PaymentOptions::CREDIT_CARD => CredtiCardOptions::NEW_CREDIT_CARD
        ];

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->testGetAllPaymentMethodsWithNewCreditCard();

        $this->assertEquals(
            $paymentMethodOutputArray,
            $this->dataMock->getCompanyPaymentMethod()
        );
    }

    /**
     * TestGetCompanyPaymentMethodWithNewPaymentConfigurationAndCompanyId
     *
     * @return array
     */
    // @codingStandardsIgnoreStart
    public function testGetCompanyPaymentMethodWithNewPaymentConfigurationAndCompanyId()
    {
        // @codingStandardsIgnoreEnd
        $paymentMethodOutputArray = [
            PaymentOptions::FEDEX_ACCOUNT_NUMBER => 'custom_fedex_account',
            PaymentOptions::CREDIT_CARD => 'new_credit_card'
        ];

        $this->testGetCustomerCompanyWithCompanyId();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethods();

        $this->assertEquals(
            $paymentMethodOutputArray,
            $this->dataMock->getCompanyPaymentMethod(1)
        );
    }

    /**
     * TestGetCompanyPaymentMethodWithOldPaymentConfiguration
     *
     * @return string
     */
    // @codingStandardsIgnoreStart
    public function testGetCompanyPaymentMethodWithOldPaymentConfiguration()
    {
        // @codingStandardsIgnoreEnd
        $paymentOption = 'accountnumbers';

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->companyMock->expects($this->any())
            ->method('getPaymentOption')
            ->willReturn($paymentOption);

        $this->assertNotNull(
            $this->dataMock->getCompanyPaymentMethod()
        );
    }

    /**
     * TestGetCompanyPaymentMethodWithOldPaymentConfigurationAndCompanyId
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function testGetCompanyPaymentMethodWithOldPaymentConfigurationAndCompanyId()
    {
        // @codingStandardsIgnoreEnd
        $paymentOption = 'accountnumbers';
        $this->testGetCustomerCompanyWithCompanyId();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->companyMock->expects($this->any())
            ->method('getPaymentOption')
            ->willReturn($paymentOption);

        $this->assertNotNull(
            $this->dataMock->getCompanyPaymentMethod(1)
        );
    }

    /**
     * TestGetCompanyPaymentMethodWithOldPaymentConfigurationAndException
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function testGetCompanyPaymentMethodWithOldPaymentConfigurationAndException()
    {
        // @codingStandardsIgnoreEnd
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $exception = new Exception('Exception message');

        $this->companyMock->expects($this->any())
            ->method('getPaymentOption')
            ->willThrowException($exception);

        $this->assertNotNull(
            $this->dataMock->getCompanyPaymentMethod()
        );
    }

    /**
     * TestGetCompanyPaymentMethodWithPreferredMethod
     *
     * @return void
     */
    public function testGetCompanyPaymentMethodWithPreferredMethod()
    {
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetPreferredPaymentMethodFedExAccountNumber();

        $this->assertEquals(
            FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT,
            $this->dataMock->getCompanyPaymentMethod(null, true)
        );
    }

    /**
     * TestGetPreferredPaymentMethodFedExAccountNumber
     *
     * @return void
     */
    public function testGetPreferredPaymentMethodFedExAccountNumber()
    {
        $this->testGetCompanyPaymentOptions();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getDefaultPaymentMethod')
            ->willReturn(PaymentOptions::FEDEX_ACCOUNT_NUMBER);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getFedexAccountOptions')
            ->willReturn(FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT);

        $this->assertEquals(
            FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT,
            $this->dataMock
                ->getPreferredPaymentMethod($this->companyExtensionInterfaceMock)
        );
    }

    /**
     * TestGetPreferredPaymentMethodCreditCard
     *
     * @return void
     */
    public function testGetPreferredPaymentMethodCreditCard()
    {
        $this->testGetCompanyPaymentOptions();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getDefaultPaymentMethod')
            ->willReturn(PaymentOptions::CREDIT_CARD);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCreditcardOptions')
            ->willReturn(CredtiCardOptions::NEW_CREDIT_CARD);

        $this->assertEquals(
            CredtiCardOptions::NEW_CREDIT_CARD,
            $this->dataMock
                ->getPreferredPaymentMethod($this->companyExtensionInterfaceMock)
        );
    }

    /**
     * TestGetPreferredPaymentMethodWithOnePaymentMethod
     *
     * @return array
     */
    public function testGetPreferredPaymentMethodWithOnePaymentMethod()
    {
        $this->testGetCompanyPaymentOptionsWithOnePaymentMethod();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getDefaultPaymentMethod')
            ->willReturn(PaymentOptions::FEDEX_ACCOUNT_NUMBER);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getFedexAccountOptions')
            ->willReturn(FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT);

        $this->assertEquals(
            FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT,
            $this->dataMock
                ->getPreferredPaymentMethod(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetPreferredPaymentMethodCreditCard
     *
     * @return array
     */
    public function testGetPreferredPaymentMethodWithNull()
    {
        $this->testGetCompanyPaymentOptionsWithNull();
        $this->assertNull($this->dataMock->getPreferredPaymentMethod($this->companyExtensionInterfaceMock));
    }

    /**
     * TestGetAllPaymentMethods
     *
     * @return array
     */
    public function testGetAllPaymentMethods()
    {
        $fedexAccountOption = 'custom_fedex_account';
        $creditcardOption = 'new_credit_card';
        $paymentMethodOutputArray = [
            PaymentOptions::FEDEX_ACCOUNT_NUMBER => 'custom_fedex_account',
            PaymentOptions::CREDIT_CARD => 'new_credit_card'
        ];

        $this->testGetCompanyPaymentOptions();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getFedexAccountOptions')
            ->willReturn($fedexAccountOption);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCreditcardOptions')
            ->willReturn($creditcardOption);

        $this->assertEquals(
            $paymentMethodOutputArray,
            $this->dataMock
                ->getAllPaymentMethods(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetAllPaymentMethods
     *
     * @return array
     */
    public function testGetAllPaymentMethodsWithNewCreditCard()
    {
        $fedexAccountOption = 'custom_fedex_account';
        $paymentMethodOutputArray = [
            PaymentOptions::FEDEX_ACCOUNT_NUMBER => 'custom_fedex_account',
            PaymentOptions::CREDIT_CARD => CredtiCardOptions::NEW_CREDIT_CARD
        ];

        $this->testGetCompanyPaymentOptions();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getFedexAccountOptions')
            ->willReturn($fedexAccountOption);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCreditcardOptions')
            ->willReturn(CredtiCardOptions::NEW_CREDIT_CARD);

        $this->assertEquals(
            $paymentMethodOutputArray,
            $this->dataMock
                ->getAllPaymentMethods(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetAllPaymentMethodsWithException
     *
     * @return array
     */
    public function testGetAllPaymentMethodsWithException()
    {
        $this->testGetCompanyPaymentOptions();

        $exception = new Exception('Exception message');

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getFedexAccountOptions')
            ->willThrowException($exception);

        $this->assertEquals(
            [],
            $this->dataMock
                ->getAllPaymentMethods(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetCompanyPaymentOptions
     *
     * @return array
     */
    public function testGetCompanyPaymentOptions()
    {
        $paymentMethodsJson = '["fedexaccountnumber","creditcard"]';
        $paymentMethodsArray = ["fedexaccountnumber", "creditcard"];

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyPaymentOptions')
            ->willReturn($paymentMethodsJson);

        $this->assertEquals(
            $paymentMethodsArray,
            $this->dataMock
                ->getCompanyPaymentOptions(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetCompanyPaymentOptionsWithOnePaymentMethod
     *
     * @return array
     */
    public function testGetCompanyPaymentOptionsWithOnePaymentMethod()
    {
        $paymentMethodsJson = '["fedexaccountnumber"]';
        $paymentMethodsArray = ["fedexaccountnumber"];

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyPaymentOptions')
            ->willReturn($paymentMethodsJson);

        $this->assertEquals(
            $paymentMethodsArray,
            $this->dataMock
                ->getCompanyPaymentOptions(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetCompanyPaymentOptionsWithNull
     *
     * @return array
     */
    public function testGetCompanyPaymentOptionsWithNull()
    {
        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyPaymentOptions')
            ->willReturn(null);

        $this->assertEquals(
            [],
            $this->dataMock
                ->getCompanyPaymentOptions(
                    $this->companyExtensionInterfaceMock
                )
        );
    }

    /**
     * TestGetFedexAccountNumberWithNewPaymentConfiguration
     *
     * @return string
     */
    public function testGetFedexAccountNumberWithNewPaymentConfiguration()
    {
        $fedexAccountNumber = '123456';

        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithNewPaymentConfiguration();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn($fedexAccountNumber);

        $this->assertEquals(
            $fedexAccountNumber,
            $this->dataMock->getFedexAccountNumber()
        );
    }


    public function testGetDiscountAccountNumberWithNewPaymentConfiguration()
    {
        $discountAccountNumber = '123456';

        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithNewPaymentConfiguration();

        $this->companyMock->expects($this->any())
            ->method('getDiscountAccountNumber')
            ->willReturn($discountAccountNumber);

        $this->assertEquals(
            $discountAccountNumber,
            $this->dataMock->getDiscountAccountNumber()
        );
    }

    /**
     * TestGetFedexAccountNumberWithOldPaymentConfiguration
     *
     * @return string
     */
    public function testGetFedexAccountNumberWithOldPaymentConfiguration()
    {
        $fedexAccountNumber = '123456';

        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithOldPaymentConfiguration();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn($fedexAccountNumber);

        $this->assertNull(
            $this->dataMock->getFedexAccountNumber()
        );
    }

    /**
     * TestGetFedexAccountNumberWithOldPaymentConfigurationAndException
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function testGetFedexAccountNumberWithOldPaymentConfigurationAndException()
    {
        // @codingStandardsIgnoreEnd
        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithOldPaymentConfigurationAndCompanyId();

        $exception = new Exception('Exception message');

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willThrowException($exception);

        $this->assertNull($this->dataMock->getFedexAccountNumber());
    }

    /**
     * TestGetFedexShippingAccountNumberWithNewPaymentConfiguration
     *
     * @return void
     */
    public function testGetFedexShippingAccountNumberWithNewPaymentConfiguration()
    {
        $shippingAccountNumber = '123456';

        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithNewPaymentConfiguration();

        $this->companyMock->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willReturn($shippingAccountNumber);

        $this->assertEquals(
            $shippingAccountNumber,
            $this->dataMock->getFedexShippingAccountNumber()
        );
    }

    /**
     * TestGetFedexShippingAccountNumberWithOldPaymentConfiguration
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function testGetFedexShippingAccountNumberWithOldPaymentConfiguration()
    {
        // @codingStandardsIgnoreEnd

        $shippingAccountNumber = '123456';

        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithOldPaymentConfiguration();

        $this->companyMock->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willReturn($shippingAccountNumber);

        $this->assertNull($this->dataMock->getFedexShippingAccountNumber());
    }

    /**
     * TestGetFedexShippingAccountNumberWithOldPaymentConfigurationAndException
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function testGetFedexShippingAccountNumberWithOldPaymentConfigurationAndException()
    {
        // @codingStandardsIgnoreEnd
        $this->testGetCustomerCompany();

        $this->testGetCompanyPaymentMethodWithOldPaymentConfigurationAndCompanyId();

        $exception = new Exception('Exception message');

        $this->companyMock->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willThrowException($exception);

        $this->assertNull($this->dataMock->getFedexShippingAccountNumber());
    }

    /**
     * B-1145880 | Get Company Home page settings
     * getCompanyHomePageSetting
     *
     * @return array
     */
    public function testGetCompanyHomePageSetting()
    {
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getEnableUploadSection')
            ->willReturn('1');

        $this->assertEquals(
            [
                'show_upload_section' => 1,
                'show_catalog_section' => 0
            ],
            $this->dataMock->getCompanyHomePageSetting()
        );
    }

    /**
     * B-1145880 | Get Company Home page settings
     * getCompanyHomePageSetting
     *
     * @return array
     */
    public function testGetCompanyHomePageSettingToggleoff()
    {
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getEnableUploadSection')
            ->willReturn(0);

        $this->assertEquals(
            [
                'show_upload_section' => 0,
                'show_catalog_section' => 0
            ],
            $this->dataMock->getCompanyHomePageSetting()
        );
    }

    /**
     * Test Get customer company
     *
     * @return void
     */
    public function testGetCustomerCompany()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($this->customerRepositoryMock));

        $this->deliveryHelperMock->expects($this->any())
            ->method('getAssignedCompany')
            ->with(null)
            ->will($this->returnValue($this->companyMock));

        $this->assertEquals(
            $this->companyMock,
            $this->dataMock->getCustomerCompany()
        );
    }

    /**
     * Test Get customer company with company id
     *
     * @return void
     */
    public function testGetCustomerCompanyWithCompanyId()
    {
        $this->companyRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->companyMock));

        $this->assertEquals(
            $this->companyMock,
            $this->dataMock->getCustomerCompany(1)
        );
    }

    /**
     * Test Get customer company returns null
     *
     * @return bool
     */
    public function testGetCustomerCompanyReturnsNull()
    {
        $this->deliveryHelperMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue(null));

        $this->assertNull($this->dataMock->getCustomerCompany());
    }

    /**
     * TestGetCustomerCompanyWithException
     *
     * @return bool
     */
    public function testGetCustomerCompanyWithException()
    {
        $exception = new Exception('Exception message');

        $this->companyRepositoryInterfaceMock->expects($this->any())
            ->method('get')
            ->willThrowException($exception);

        $this->assertNull($this->dataMock->getCustomerCompany(1));
    }

    /**
     * TestGetDefaultPaymentMethodWithToggleTrue
     *
     * @return array
     */
    public function testGetDefaultPaymentMethodWithToggleTrue()
    {
        $creditCardData = ['token' => "13442323", 'data' => ["test", 'token' => "13442323"]];
        $fedexAccountNumber = '123456';
        $ccDataJson = '["test"]';
        $ccTokenExpiryDate = "2027-01-01 00:00:00";
        $paymentMethod = [
            'defaultMethod' => '',
            'paymentMethodInfo' => [
                PaymentOptions::CREDIT_CARD => $creditCardData['data'],
                PaymentOptions::FEDEX_ACCOUNT_NUMBER => $fedexAccountNumber
            ]
        ];

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethodsWithNewCreditCard();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willReturn($creditCardData['token']);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcData')
            ->willReturn($ccDataJson);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->dateTimeMock->expects($this->any())->method('gmtTimeStamp')
            ->withConsecutive(
                [],
                [],
            )
            ->willReturnOnConsecutiveCalls(
                strtotime($ccTokenExpiryDate),
                strtotime("2022-04-01 01:00:00"),
            );

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethods();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn($fedexAccountNumber);

        $this->assertEquals(
            $paymentMethod,
            $this->dataMock->getDefaultPaymentMethod()
        );
    }

    /**
     * TestGetDefaultPaymentMethodWithToggleTrue
     *
     * @return array
     */
    public function testGetDefaultPaymentMethodWithToggleTrueWithFedexAccount()
    {
        $fedexAccountNumber = '123456';
        $ccTokenExpiryDate = "2022-04-01 01:00:00";
        $paymentMethod = [
            'defaultMethod' => PaymentOptions::FEDEX_ACCOUNT_NUMBER,
            'paymentMethodInfo' => $fedexAccountNumber,
        ];

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethodsWithNewCreditCard();
        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willReturn(null);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcData')
            ->willReturn(null);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcTokenExpiryDateTime')
            ->willReturn(null);

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethods();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn($fedexAccountNumber);

        $this->dateTimeMock->expects($this->any())->method('gmtTimeStamp')
            ->withConsecutive(
                [],
                [],
            )
            ->willReturnOnConsecutiveCalls(
                strtotime($ccTokenExpiryDate),
                strtotime("2022-04-01 01:00:00"),
            );

        $this->assertEquals(
            $paymentMethod,
            $this->dataMock->getDefaultPaymentMethod()
        );
    }

    /**
     * TestGetDefaultPaymentMethodWithToggleTrueWithCreditCard
     *
     * @return array
     */
    public function testGetDefaultPaymentMethodWithToggleTrueWithCreditCard()
    {
        $creditCardData = ['token' => "13442323", 'data' => ["test", 'token' => "13442323"]];
        $ccDataJson = '["test"]';
        $paymentMethod = [
            'defaultMethod' => PaymentOptions::CREDIT_CARD,
            'paymentMethodInfo' => $creditCardData['data'],
        ];
        $ccTokenExpiryDate = "2027-08-01 01:01:01";

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethodsWithNewCreditCard();

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willReturn($creditCardData['token']);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcData')
            ->willReturn($ccDataJson);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetAllPaymentMethods();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn(null);

        $this->dateTimeMock->expects($this->any())->method('gmtTimeStamp')
            ->withConsecutive(
                [],
                [],
            )
            ->willReturnOnConsecutiveCalls(
                strtotime($ccTokenExpiryDate),
                strtotime("2022-04-01 01:00:00"),
            );

        $this->assertEquals(
            $paymentMethod,
            $this->dataMock->getDefaultPaymentMethod()
        );
    }

    /**
     * TestGetDefaultPaymentMethodWithOldPaymentConfiguration
     *
     * @return array
     */
    public function testGetDefaultPaymentMethodWithToggleFalse()
    {
        $paymentMethod = ['defaultMethod' => '', 'paymentMethodInfo' => ''];
        $this->assertEquals(
            $paymentMethod,
            $this->dataMock->getDefaultPaymentMethod()
        );
    }

    /**
     * TestGetCreditCardTokenExpiryDateTime
     *
     * @return dateTime
     */
    public function testGetCreditCardTokenExpiryDateTime()
    {
        $ccTokenExpiryDate = "2027-04-01 00:00:00";
        $creditCardData = ['token' => "13442323", 'data' => ["test"]];

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willReturn($creditCardData['token']);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->assertEquals(
            $ccTokenExpiryDate,
            $this->dataMock->getCreditCardTokenExpiryDateTime()
        );
    }

    /**
     * TestGetCreditCardTokenExpiryDateTimewithException
     *
     * @return string
     */
    public function testGetCreditCardTokenExpiryDateTimeWithException()
    {
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $exception = new Exception('Exception message');

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willThrowException($exception);

        $this->assertEquals('', $this->dataMock->getCreditCardTokenExpiryDateTime());
    }

    /**
     * TestIsValidCreditCardTokenExpiryDate
     *
     * @return bool
     */
    public function testIsValidCreditCardTokenExpiryDate()
    {
        $ccTokenExpiryDate = "2027-04-01 00:00:00";
        $creditCardData = ['token' => "13442323", 'data' => ["test"]];

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willReturn($creditCardData['token']);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->dateTimeMock->expects($this->any())->method('gmtTimeStamp')
            ->withConsecutive(
                [],
                [],
            )
            ->willReturnOnConsecutiveCalls(
                strtotime($ccTokenExpiryDate),
                strtotime("2022-04-01 01:00:00"),
            );

        $this->assertTrue($this->dataMock->isValidCreditCardTokenExpiryDate($ccTokenExpiryDate));
    }

    /**
     * TestIsValidCreditCardTokenExpiryDatewithException
     *
     * @return string
     */
    public function testIsValidCreditCardTokenExpiryDateWithException()
    {
        $ccTokenExpiryDate = "2027-04-01 00:00:00";
        $exception = new Exception('Exception message');
        $this->dateTimeMock->expects($this->any())
            ->method('gmtTimeStamp')
            ->willThrowException($exception);

        $this->assertFalse($this->dataMock->isValidCreditCardTokenExpiryDate($ccTokenExpiryDate));
    }

    /**
     * TestGetCompanyCreditCardData
     *
     * @return array
     */
    public function testGetCompanyCreditCardData()
    {
        $creditCardData = ['token' => "13442323", 'data' => ["test", 'token' => '13442323']];
        $ccDataJson = '["test"]';
        $ccTokenExpiryDate = "2027-04-01 00:00:00";

        $this->testGetCompanyPaymentMethodWithCreditCardNewPaymentConfiguration();

        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willReturn($creditCardData['token']);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcData')
            ->willReturn($ccDataJson);

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcTokenExpiryDateTime')
            ->willReturn($ccTokenExpiryDate);

        $this->dateTimeMock->expects($this->any())->method('gmtTimeStamp')
            ->withConsecutive(
                [],
                [],
            )
            ->willReturnOnConsecutiveCalls(
                strtotime($ccTokenExpiryDate),
                strtotime("2022-04-01 01:00:00"),
            );

        $this->assertEquals(
            $creditCardData,
            $this->dataMock->getCompanyCreditCardData()
        );
    }

    /**
     * TestGetCompanyCreditCardDataWithException
     *
     * @return array
     */
    public function testGetCompanyCreditCardDataWithException()
    {
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

        $this->testGetCompanyPaymentMethodWithCreditCardNewPaymentConfiguration();

        $exception = new Exception('Exception message');

        $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCcToken')
            ->willThrowException($exception);

        $this->assertEquals([], $this->dataMock->getCompanyCreditCardData());
    }

      /**
     * Test for validateCompanyName method.
     *
     * @return void
     */
    public function testvalidateCompanyNameResultZero()
    {
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('company');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        $this->adapterInterfaceMock->method('fetchAll')->willReturn(["1","2"]);
        $this->assertNotNull($this->dataMock->validateCompanyName('Infogain', 1));
    }

    /**
     * Test for validateNewtworkId method.
     *
     * @return void
     */
    public function testvalidateNewtworkId()
    {
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('company');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);

        // B-1385081
        $this->adapterInterfaceMock->method('fetchAll')
            ->withConsecutive([], [], [], [])
            ->willReturnOnConsecutiveCalls(
                [],
                ['key' => 'value'],
                ['key' => 'value'],
                ['key' => 'value']
            );
        $this->assertNotNull($this->dataMock->validateNewtworkId('Infogain', 1));
    }

    /**
     * Test for validateNewtworkId method.
     *
     * @return void
     */
    public function testvalidateNewtworkIdZero()
    {
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('company');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn(["1","2"]);
        $this->assertNotNull($this->dataMock->validateNewtworkId('Infogain', 1));
    }

    /**
     * Get Promo Account Discount Company Config
     *
     * @return void
     */
    public function testGetCompanyLevelConfig()
    {
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();

        $this->additionalDataMock->expects($this->any())
            ->method('getIsPromoDiscountEnabled')
            ->willReturn(true);
        $this->additionalDataMock->expects($this->any())
            ->method('getIsAccountDiscountEnabled')
            ->willReturn(true);
        $this->additionalDataMock->expects($this->any())
            ->method('getOrderNotes')
            ->willReturn('test data');
        $this->additionalDataMock->expects($this->any())
            ->method('getTermsAndConditions')
            ->willReturn(true);
        $this->additionalDataMock->expects($this->any())
            ->method('getIsReorderEnabled')
            ->willReturn(true);

        $this->deliveryHelperMock->expects($this->any())->method('isEproCustomer')->willReturn(false);

        $this->deliveryHelperMock->expects($this->any())
            ->method('isSdeCustomer')
            ->willReturn(false);

        $this->assertIsArray(
            $this->dataMock->getCompanyLevelConfig()
        );
    }

    /**
     * Get Company Id
     *
     * @return void
     */
    public function testGetCompanyId()
    {
        $this->testGetCustomerCompany();
        $this->companyMock->expects($this->any())->method('getId')->willReturn(12);
        $this->assertEquals(12, $this->dataMock->getCompanyId());
    }

    /**
     * Get Company Id With Empty
     *
     * @return void
     */
    public function testGetCompanyIdWithNull()
    {
        $this->testGetCustomerCompany();
        $this->companyMock->expects($this->any())->method('getId')->willReturn('');
        $this->assertEquals('', $this->dataMock->getCompanyId());
    }

    /**
     * Get Non Editable Company Credit Card Payment Method
     *
     * @return void
     */
    public function testGetNonEditableCompanyCcPaymentMethod()
    {
        $this->testGetCustomerCompany();
        $this->companyMock->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('getIsNonEditableCcPaymentMethod')->willReturn(true);

        $this->assertTrue($this->dataMock->getNonEditableCompanyCcPaymentMethod());
    }

    /**
     * TestIsApplicablePaymentMethodCCOnly
     *
     * @return void
     */
    public function testIsApplicablePaymentMethodCCOnly()
    {
        $isCCOnly = false;
        $this->testGetCustomerCompany();
        $this->companyMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);
        $this->testGetPreferredPaymentMethodFedExAccountNumber();
        $this->assertEquals($isCCOnly, $this->dataMock->isApplicablePaymentMethodCCOnly());
    }

    public function testGetRecipientAddressFromPo()
    {
        $this->testGetCustomerCompany();
        $this->companyMock->expects($this->any())
            ->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');
        $this->companyMock->expects($this->any())
            ->method('getRecipientAddressFromPo')
            ->willReturn(0);
        $this->assertEquals(false, $this->dataMock->getRecipientAddressFromPo());
    }

    public function testIsSiteLevelQuoteToggle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertTrue($this->dataMock->isSiteLevelQuoteToggle());
    }

    /**
     * Test getFedexPrintShipAccounts
     *
     * @return array
     */
    public function testGetFedexPrintShipAccounts()
    {
        $fedexPrintShipAccounts = [
            "print_account" => '123',
            "ship_account" => '123'
        ];
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('123');

        $this->companyMock->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willReturn('123');

        $this->assertEquals(
            $fedexPrintShipAccounts,
            $this->dataMock->getFedexPrintShipAccounts()
        );
    }

    /**
     * testGetFedexPrintShipAccountsWithException
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function testGetFedexPrintShipAccountsWithException()
    {
        // @codingStandardsIgnoreEnd
        $this->testGetCustomerCompany();

        $this->companyMock->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('123');

        $exception = new Exception('Exception message');

        $this->companyMock->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willThrowException($exception);

        $this->assertNotNull(
            $this->dataMock->getFedexPrintShipAccounts()
        );
    }
    private function invokePrivateMethod($methodName, $parameters)
    {
        $method = $this->reflectionClass->getMethod($methodName);
        $method->setAccessible(true); // Make the method accessible
        return $method->invoke($this->dataMock, ...$parameters);
    }

    public function testShouldUseFedexShippingAccountNumberWithCreditCardEnabled()
    {
        $payMethods = [PaymentOptions::CREDIT_CARD => 'someValue'];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);

        $result = $this->invokePrivateMethod('shouldUseFedexShippingAccountNumber', [$payMethods]);
        $this->assertTrue($result);
    }

    public function testShouldUseFedexShippingAccountNumberWithCustomFedExAccount()
    {
        $payMethods = [PaymentOptions::FEDEX_ACCOUNT_NUMBER => FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseFedexShippingAccountNumber', [$payMethods]);
        $this->assertTrue($result);
    }

    public function testShouldUseFedexShippingAccountNumberWithLegacyFedExAccount()
    {
        $payMethods = [PaymentOptions::FEDEX_ACCOUNT_NUMBER => FedExAccountOptions::LEGACY_FEDEX_ACCOUNT];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseFedexShippingAccountNumber', [$payMethods]);
        $this->assertFalse($result);
    }

    public function testShouldUseFedexShippingAccountNumberWithNoRelevantOptions()
    {
        $payMethods = []; // Empty array

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseFedexShippingAccountNumber', [$payMethods]);
        $this->assertFalse($result);
    }

    public function testShouldUseDiscountAccountNumberWithCreditCardEnabled()
    {
        $payMethods = [PaymentOptions::CREDIT_CARD => 'someValue'];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);

        $result = $this->invokePrivateMethod('shouldUseDiscountAccountNumber', [$payMethods]);
        $this->assertTrue($result);
    }

    public function testShouldUseDiscountAccountNumberWithCustomFedExAccount()
    {
        $payMethods = [PaymentOptions::FEDEX_ACCOUNT_NUMBER => FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseDiscountAccountNumber', [$payMethods]);
        $this->assertTrue($result);
    }

    public function testShouldUseDiscountAccountNumberWithLegacyFedExAccount()
    {
        $payMethods = [PaymentOptions::FEDEX_ACCOUNT_NUMBER => FedExAccountOptions::LEGACY_FEDEX_ACCOUNT];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseDiscountAccountNumber', [$payMethods]);
        $this->assertFalse($result);
    }

    public function testShouldUseDiscountAccountNumberWithLegacySiteCreditCard()
    {
        $payMethods = [
            PaymentOptions::CREDIT_CARD => PaymentAcceptance::LEGECY_SITE_CREDIT_CARD,
        ];

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseDiscountAccountNumber', [$payMethods]);
        $this->assertTrue($result);
    }

    public function testShouldUseDiscountAccountNumberWithNoRelevantOptions()
    {
        $payMethods = []; // Empty array

        // Mocking the configuration method
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->invokePrivateMethod('shouldUseDiscountAccountNumber', [$payMethods]);
        $this->assertFalse($result);
    }

    public function testTrimAccountNumberWithValidInput()
    {
        $accountNumber = " 123456 ";
        $result = $this->invokePrivateMethod('trimAccountNumber', [$accountNumber]);
        $this->assertEquals("123456", $result);
    }

    public function testTrimAccountNumberWithEmptyInput()
    {
        $accountNumber = "   ";
        $result = $this->invokePrivateMethod('trimAccountNumber', [$accountNumber]);
        $this->assertNull($result);
    }

    public function testTrimAccountNumberWithNullInput()
    {
        $result = $this->invokePrivateMethod('trimAccountNumber', [null]);
        $this->assertNull($result);
    }

}
