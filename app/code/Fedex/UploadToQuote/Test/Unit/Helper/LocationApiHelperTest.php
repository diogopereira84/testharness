<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\UploadToQuote\Helper\LocationApiHelper;

/**
 * Test class for LocationApiHelper
 */
class LocationApiHelperTest extends TestCase
{
    protected $locationApiHelperMock;
    public const API_URL = 'https://api.test.office.fedex.com/location/fedexoffice/v2/search';

    /**
     * @var PunchoutHelper $punchoutHelper
     */
    protected $punchoutHelper;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var Curl $curl
     */
    protected $curl;

    /**
     * @var HeaderData $headerData
     */
    protected $headerData;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['getAuthGatewayToken', 'getTazToken'])
        ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions','post'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerData = $this->getMockBuilder(HeaderData::class)
            ->setMethods(['getAuthHeaderValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->locationApiHelperMock = $objectManagerHelper->getObject(
            LocationApiHelper::class,
            [
                'punchoutHelper' => $this->punchoutHelper,
                'logger' => $this->logger,
                'curl' => $this->curl,
                'headerData' => $this->headerData
            ]
        );

        $this->locationApiHelperMock->formData = [
            'stateCode' => 'SD',
            'countryCode' => 'US'
        ];
    }

    /**
     * Test method for getAuthenticationDetails
     *
     * @return void
     */
    public function testGetAuthenticationDetails()
    {
        $gateWayToken = 'gateway-token-string';
        $accessToken = 'access-token-string';

        $this->punchoutHelper->expects($this->once())
            ->method('getAuthGatewayToken')
            ->willReturn($gateWayToken);

        $this->punchoutHelper->expects($this->once())
            ->method('getTazToken')
            ->willReturn($accessToken);

        $result = $this->locationApiHelperMock->getAuthenticationDetails();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gateWayToken', $result);
        $this->assertArrayHasKey('accessToken', $result);
    }

    /**
     * Test method for callLocationSearchApi
     *
     * @return void
     */
    public function testCallLocationSearchApi()
    {
        $setupURL = self::API_URL;
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",

        ];
        $dataString = '{"key": "value"}';

        $this->curl->expects($this->once())
            ->method('setOptions')
            ->with(
                $this->equalTo([
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => ''
                ])
            );
        $this->curl->expects($this->once())
            ->method('post')
            ->with($this->equalTo($setupURL), $this->equalTo($dataString));

        $this->curl->expects($this->once())
            ->method('getBody')
            ->willReturn('{"Status": "Success"}');

        $expectedResult = ['Status' => 'Success'];

        $result = $this->locationApiHelperMock->callLocationSearchApi($setupURL, $headers, $dataString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for prepareData
     *
     * @return void
     */
    public function testPrepareData()
    {
        $data =  '{
            "locationSearchRequest": {
              "address": {
                "streetLines": null,
                "city": null,
                "stateOrProvinceCode": "SD",
                "postalCode": null,
                "countryCode": "US",
                "addressClassification": "BUSINESS"
              },
              "include": {
                "printHubOnly": true
              }
            }
          }';

        $this->assertNotNull($data, $this->locationApiHelperMock->prepareData($this->locationApiHelperMock->formData));
    }

    /**
     * Test method for getHubCenterCodeByState
     *
     * @return void
     */
    public function testGetHubCenterCodeByState()
    {
        $this->punchoutHelper->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type' => 'Cookie',
                    ]
                )
            );

        $this->assertNotNull(
            $this->locationApiHelperMock->getHubCenterCodeByState($this->locationApiHelperMock->formData, self::API_URL)
        );
    }

    /**
     * Test method for getHubCenterCodeByState with empty token
     *
     * @return void
     */
    public function testGetHubCenterCodeByStateWithFalse()
    {
        $this->punchoutHelper->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(false);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type' => 'Cookie',
                    ]
                )
            );

        $this->assertNotNull(
            $this->locationApiHelperMock->getHubCenterCodeByState($this->locationApiHelperMock->formData, self::API_URL)
        );
    }
}
