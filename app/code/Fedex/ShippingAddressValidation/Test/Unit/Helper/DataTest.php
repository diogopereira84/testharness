<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ShippingAddressValidation\Test\Unit\Helper;

use Fedex\MarketplaceRates\Helper\Data as PurpleGatewayToken;
use Fedex\ShippingAddressValidation\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DataTest extends TestCase
{
    /**
     * @var data
     *
     */
    protected $dataHelperMock;

    /**
     * @var PurpleGatewayToken
     */
    protected $purpleGatewayTokenMock;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var Curl
     */
    protected $curlMock;

    /**
     * @var EncryptorInterface
     */
    protected $encryptorMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical', 'error'])
            ->getMockForAbstractClass();
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOptions', 'post', 'getBody', 'get'])
            ->getMock();

        $this->purpleGatewayTokenMock = $this->getMockBuilder(PurpleGatewayToken::class)
            ->setMethods(['getFedexRatesToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHelperMock = new Data(
            $this->createMock(\Magento\Framework\App\Helper\Context::class),
            $this->scopeConfigMock,
            $this->curlMock,
            $this->purpleGatewayTokenMock,
            $this->loggerMock
        );

        $this->dataHelperMock->formData = [
            'stateCode' => 'SD',
            'countryCode' => 'US',
        ];
    }

    /**
     * Test method for callAddressValidationApi
     *
     * @return void
     */
    public function testCallAddressValidationApiWithCurl()
    {
        $result = '{
            "transactionId":"adda8df9-a5cb-4718-a65f-c9e61ef06f09",
            "errors":[
                {"code":"LOGIN.REAUTHENTICATE.ERROR","message":"GENERIC.ERROR"}
                ]
            }';
        $this->purpleGatewayTokenMock->expects($this->any())
            ->method('getFedexRatesToken')
            ->willReturn('l7a8119691017e419fbf1411c982c0c732');
        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();
        $this->curlMock->expects($this->any())
            ->method('post')
            ->willReturnSelf();
        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willReturn($result);
        $this->assertNotNull($this->dataHelperMock->callAddressValidationApi($this->dataHelperMock->formData));
    }

    /**
     * @test callAddressValidationApi Exception
     */
    public function testCallAddressValidationApiWithCurlWithException()
    {
        $exception = new \Exception();
        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willThrowException($exception);
        $this->assertNull($this->dataHelperMock->callAddressValidationApi($this->dataHelperMock->formData));
    }

    /**
     *  test getShippingAddressUrl
     */
    public function testGetUserTokenApiUrl()
    {
        $this->scopeConfigMock->expects($this->any())->method('getValue')
            ->with(
                'fedex/general/shipping_address_api_url',
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn('https://apitest.fedex.com/address/v3/addresses/resolve');
        $this->assertIsString($this->dataHelperMock->getShippingAddressUrl());
    }
    
    /**
     *  test getResponseValue
     */
    public function testGetResponseValue()
    {
        $ouput = ["7900 LEGACY DR"];
        $responseData = $this->getResponseData();
        $this->assertEquals($ouput, $this->dataHelperMock->getResponseValue($responseData, 'streetLinesToken'));
    }

    /**
     *  test getResponseValue with Blank Array
     */
    public function testGetResponseValuewithBlank()
    {
        $ouput = [];
        $responseData = $this->getResponseData();
        $this->assertEquals($ouput, $this->dataHelperMock->getResponseValue($responseData, 'streetLinesTokens'));
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
     * Test method for prepareData
     *
     * @return void
     */
    public function testPrepareData()
    {
        $data = '{
                            "validateAddressControlParameters": {
                                "includeResolutionTokens": true
                            },
                        "addressesToValidate": {
                            "contact": {
                                "personName":Pooja Tiwari,
                                "phoneNumber": 359345040,
                            },
                            "address":{
                                "streetLines": "streetWall",
                                "city": "Texas",
                                "postalCode": 75204,
                                "countryCode":US

                            }
                        }
                }';

        $this->assertNotNull($data, $this->dataHelperMock->prepareData($this->dataHelperMock->formData));
    }
}
