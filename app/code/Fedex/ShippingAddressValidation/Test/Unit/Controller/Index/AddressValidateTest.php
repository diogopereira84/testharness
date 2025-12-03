<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingAddressValidation
 * @copyright   Copyright (c) 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\ShippingAddressValidation\Test\Unit\Controller\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\ShippingAddressValidation\Controller\Index\AddressValidate;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ShippingAddressValidation\Helper\Data as AddressValidationHelper;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AddressValidateTest extends TestCase
{
    const GENERIC_ERROR_BYPASS = 'fedex/general/shipping_address_api_generic_error_bypass';

    /**
     * @var AddressValidate
     */
    private $controller;
    /**
     * @var LoggerInterface
     */
    private $loggerMock;
    /**
     * @var RequestInterface
     */
    private $requestMock;
    /**
     * @var ToggleConfig
     */
    private $toggleConfigMock;
    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactoryMock;
    /**
     * @var AddressValidationHelper
     */
    private $addressValidationHelperMock;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
        ->setMethods(['info'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue', 'json_decode', 'json_encode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->createMock(RequestInterface::class);
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressValidationHelperMock = $this->getMockBuilder(AddressValidationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->controller = $this->objectManager->getObject(
            AddressValidate::class,
            [
                'logger' => $this->loggerMock,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleConfigMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'addressValidationHelper' => $this->addressValidationHelperMock,
                'scopeConfig' => $this->scopeConfigMock,
            ]
        );
    }

    /**
     * @test execute with valid data and toggle enabled
     */
    public function testExecuteWithValidDataAndToggleEnabled()
    {
        $postData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'zipcode' => '75024',
            'city' => 'Plano',
            'phoneNumber' => '9642245896',
            'streetLines' => ['7900 Legacy Drive','demo']
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->will($this->onConsecutiveCalls(true,false));
        $expectedData = $this->getResponseData();
        $this->addressValidationHelperMock->method('callAddressValidationApi')->willReturn($expectedData);
        $expectedResult = $this->createPartialMock(Json::class, ['setData']);
        $expectedResult->expects($this->once())
                       ->method('setData')
                       ->with($expectedData);
        $this->resultJsonFactoryMock->method('create')->willReturn($expectedResult);
        $result = $this->controller->execute();
        $this->assertNotEquals($result,$expectedResult);
    }

    /**
     * @test execute with valid data and toggle disabled
     */
    public function testExecuteWithValidDataAndToggleDisabled()
    {
        $postData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'zipcode' => '12345',
            'city' => 'City',
            'phoneNumber' => '1234567890',
            'streetLines' => ['Street 1', 'Street 2']
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $expectedData = $this->getResponseData();
        $this->addressValidationHelperMock->method('callAddressValidationApi')->willReturn($expectedData);

        $expectedResult = $this->createPartialMock(Json::class, ['setData']);
        $expectedResult->expects($this->once())
                       ->method('setData')
                       ->with();

        $this->resultJsonFactoryMock->method('create')->willReturn($expectedResult);

        $result = $this->controller->execute();

        $this->assertNull($result);
    }

    /**
     * @test execute with invalid data
     */
    public function testExecuteWithInvalidData()
    {
        $postData = [];
        $this->requestMock->method('getPostValue')->willReturn($postData);

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        $expectedResult = $this->createPartialMock(Json::class, ['setData']);
        $expectedResult->expects($this->once())
                       ->method('setData')
                       ->with();

        $this->addressValidationHelperMock
            ->method('getShippingAddressErrorCode')
            ->willReturn('test;error');
        $this->resultJsonFactoryMock->method('create')->willReturn($expectedResult);

        $result = $this->controller->execute();

        $this->assertNull($result);
    }

    /**
     * @test execute with exception
     */
    public function testExecuteWithExceptionThrown()
    {
        $postData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'zipcode' => '12345',
            'city' => 'City',
            'phoneNumber' => '1234567890',
            'streetLines' => ['Street 1', 'Street 2']
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);
        $this->toggleConfigMock->method('getToggleConfigValue')->will($this->onConsecutiveCalls(true,false));
        $this->addressValidationHelperMock->method('callAddressValidationApi')->willThrowException(new \Exception('API Exception'));

        $this->loggerMock->expects($this->once())
                         ->method('critical');
        $result1 = ['error_msg' => 'Error found no data from AddressValidationAPI.API Exception'];
        $expectedResult = $this->createPartialMock(Json::class, ['setData']);
        $expectedResult->expects($this->once())
                       ->method('setData')
                       ->with($result1);

        $this->resultJsonFactoryMock->method('create')->willReturn($expectedResult);
        $result = $this->controller->execute();
        $this->assertNull($result);
    }

    /**
     * Response Data Array
     * @return array
     */
    public function getResponseData()
    {
        $jsonParsedArray = [
            "transactionId" => "bf6d0b63-69ff-47f7-ac8c-7f9e59985052",
            "output" => [
                "resolvedAddresses" => [
                    [
                        "streetLinesToken" => [
                            "7900 LEGACY DR"
                        ],
                        "cityToken" => [
                            [
                                "changed" => false,
                                "value" => "PLANO"
                            ]
                        ],
                        "stateOrProvinceCode" => "TX",
                        "stateOrProvinceCodeToken" => [
                            "changed" => false,
                            "value" => "TX"
                        ],
                        "postalCodeToken" => [
                            "changed" => true,
                            "value" => "75024-4089"
                        ],
                        "parsedPostalCode" => [
                            "base" => "75024",
                            "addOn" => "4089",
                            "deliveryPoint" => "00"
                        ],
                        "countryCode" => "US",
                        "classification" => "BUSINESS",
                        "ruralRouteHighwayContract" => false,
                        "generalDelivery" => false,
                        "customerMessages" => [
                        ],
                        "normalizedStatusNameDPV" => true,
                        "standardizedStatusNameMatchSource" => "Postal",
                        "resolutionMethodName" => "USPS_VALIDATE",
                        "contact" => [
                            "personName" => "personName",
                            "phoneNumber" => "9642245896"
                        ],
                        "attributes" => [
                            "POBox" => "false",
                            "POBoxOnlyZIP" => "false",
                            "SplitZIP" => "false",
                            "SuiteRequiredButMissing" => "false",
                            "InvalidSuiteNumber" => "false",
                            "ResolutionInput" => "RAW_ADDRESS",
                            "DPV" => "true",
                            "ResolutionMethod" => "USPS_VALIDATE",
                            "DataVintage" => "August 2023",
                            "MatchSource" => "Postal",
                            "CountrySupported" => "true",
                            "ValidlyFormed" => "true",
                            "Matched" => "true",
                            "Resolved" => "true",
                            "Inserted" => "false",
                            "MultiUnitBase" => "false",
                            "ZIP11Match" => "true",
                            "ZIP4Match" => "true",
                            "UniqueZIP" => "false",
                            "StreetAddress" => "true",
                            "RRConversion" => "false",
                            "ValidMultiUnit" => "false",
                            "AddressType" => "STANDARDIZED",
                            "AddressPrecision" => "STREET_ADDRESS",
                            "MultipleMatches" => "false"
                        ]
                    ]
                ]
            ]
        ];
        return $jsonParsedArray;
    }

    /**
     * @test execute with valid data and toggle disabled with bypass
     */
    public function testExecuteWithValidDataAndToggleDisabledWithBypass()
    {
        $postData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'zipcode' => '12345',
            'city' => 'City',
            'phoneNumber' => '1234567890',
            'streetLines' => ['Street 1', 'Street 2']
        ];
        $this->requestMock->method('getPostValue')->willReturn($postData);

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::GENERIC_ERROR_BYPASS)
            ->willReturn(true);
        $response = [
            'errors' => [
                ['message' => 'GENERIC.ERROR'],
                ['message' => 'OTHER.ERROR']
            ]
        ];
        $this->addressValidationHelperMock->method('callAddressValidationApi')->willReturn($response);

        $expectedResult = $this->createPartialMock(Json::class, ['setData']);
        $expectedResult->expects($this->once())
            ->method('setData')
            ->with();

        $this->resultJsonFactoryMock->method('create')->willReturn($expectedResult);

        $result = $this->controller->execute();

        $this->assertNull($result);
    }
    public function testIsGenericErrorInsideResponseReturnsTrueWhenGenericErrorPresent()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::GENERIC_ERROR_BYPASS)
            ->willReturn(true);

        $response = [
            'errors' => [
                ['message' => 'GENERIC.ERROR'],
                ['message' => 'OTHER.ERROR']
            ]
        ];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isGenericErrorInsideResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $response);
        $this->assertTrue($result);
    }

    public function testIsGenericErrorInsideResponseReturnsFalseWhenNoGenericError()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::GENERIC_ERROR_BYPASS)
            ->willReturn(true);

        $response = [
            'errors' => [
                ['message' => 'SOME.ERROR'],
                ['message' => 'OTHER.ERROR']
            ]
        ];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isGenericErrorInsideResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $response);
        $this->assertFalse($result);
    }

    public function testIsGenericErrorInsideResponseReturnsFalseWhenToggleDisabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(self::GENERIC_ERROR_BYPASS)
            ->willReturn(false);

        $response = [
            'errors' => [
                ['message' => 'GENERIC.ERROR']
            ]
        ];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isGenericErrorInsideResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $response);
        $this->assertFalse($result);
    }

    public function testIsToggleForGenericErrorBypassEnabledReturnsTrue()
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::GENERIC_ERROR_BYPASS)
            ->willReturn(true);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isToggleForGenericErrorBypassEnabled');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);
        $this->assertTrue($result);
    }

    public function testIsToggleForGenericErrorBypassEnabledReturnsFalse()
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::GENERIC_ERROR_BYPASS)
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isToggleForGenericErrorBypassEnabled');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);
        $this->assertFalse($result);
    }
}
