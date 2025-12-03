<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Helper;

use Fedex\CIDPSG\Helper\PegaHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test class for PegaHelper
 */
class PegaHelperTest extends TestCase
{
    protected $punchoutHelperMock;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $timezoneMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $pegaHelperMock;
    public const PEGA_API_REQUEST = 'pega_api_request';
    public const PEGA_API_RESPONSE = 'pega_api_response';
    public const API_URL = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/PSGAPI/createAccount';

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
     * @var TimezoneInterface $timezoneInterface;
     */
    protected $timezoneInterface;

    /**
     * @var AdminConfigHelper $adminConfigHelper;
     */
    protected $adminConfigHelper;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAuthGatewayToken', 'getTazToken'])
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions','post'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->setMethods(['getPegaAccountCreateApiUrl', 'isLogEnabled', 'setValue',
            'prepareData','getPegaApiResponse', 'callPegaAccountCreateApi'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getAuthenticationDetails', 'prepareData', 'callPegaAccountCreateApi',
            'setPegaRequestResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->pegaHelperMock = $objectManagerHelper->getObject(
            PegaHelper::class,
            [
                'punchoutHelper' => $this->punchoutHelperMock,
                'logger' => $this->loggerMock,
                'curl' => $this->curl,
                'timezoneInterface' => $this->timezoneMock,
                'adminConfigHelper' => $this->adminConfigHelper
            ]
        );

        $this->pegaHelperMock->formData = [
            'cid_psg_country' => 'US',
            'legal_company_name' => 'company name',
            'pre_acc_name' => 'pre_acc_name',
            'charge_acc_bill_checkbox_val' => 1,
            'tax_exempt_checkbox_val' => 0,
            'tc_checkbox_val' => 1,
            'corr_add_checkbox_val' => 1,
            'street_add' => 'test',
            'add_line2' => 'test',
            'suite_other' => 'test',
            'city' => 'test',
            'cid_psg_state' => 'test',
            'cid_psg_country' => 'USA',
            'zip' => '23244',
            'contact_fname' => 'test',
            'contact_lname' => 'test',
            'email' => 'test@testmail.com',
            'date_of_incorp' => '02/09/2023',
            'in_buiseness_since' => '01/11/2023',
            'charge_special_requirements' => 1,
            'is_card_required' => 1,
            'tax_exempt_checkbox_val' => 1
        ];
    }

    /**
     * Test method for setPegaRequestResponse
     *
     * @return void
     */
    public function testSetPegaRequestResponse()
    {
        $req = '{"key1":"value1","key2":"value2"}';
        $resp = [
            "Status" => "Success",
            "Message" => "Account Creation case created successfully AR-798372"
        ];

        $this->adminConfigHelper->expects($this->exactly(2))
            ->method('setValue')
            ->withConsecutive(
                [$this->equalTo(self::PEGA_API_REQUEST), $this->equalTo($req)],
                [$this->equalTo(self::PEGA_API_RESPONSE), $this->equalTo(json_encode($resp))]
            );

        $this->adminConfigHelper->expects($this->any())
            ->method('isLogEnabled')
            ->willReturn(true);

        $result = $this->pegaHelperMock->setPegaRequestResponse($req, $resp);

        $this->assertEquals(null, $result);
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

        $this->punchoutHelperMock->expects($this->once())
            ->method('getAuthGatewayToken')
            ->willReturn($gateWayToken);

        $this->punchoutHelperMock->expects($this->once())
            ->method('getTazToken')
            ->willReturn($accessToken);

        $result = $this->pegaHelperMock->getAuthenticationDetails();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gateWayToken', $result);
        $this->assertArrayHasKey('accessToken', $result);
    }

    /**
     * Test method for printLog
     *
     * @return void
     */
    public function testPrintLog()
    {
        $request = '{"key1":"test","key2":"test"}';
        $response = '{"Status": "Success","Message": "Account Creation case created successfully AR-798372"}';

        $this->adminConfigHelper->expects($this->once())
            ->method('getPegaAccountCreateApiUrl')
            ->willReturn(self::API_URL);

        $result = $this->pegaHelperMock->printLog($request, $response);

        $this->assertEquals(null, $result);
    }

    /**
     * Test method for splitPhone
     *
     * @return void
     */
    public function testSplitPhone()
    {
        $inputPhone = '(123)456-7890';
        $expectedResult = [
            'areaCd' => '123',
            'lineNum' => '456-7890'
        ];
        $result = $this->pegaHelperMock->splitPhone($inputPhone);

        $this->assertNotEquals($expectedResult, $result);
    }

    /**
     * Test method for getTaxExemptData
     *
     * @return void
     */
    public function testGetTaxExemptData()
    {
        $certificateNo = '11111';
        $dataArr = [];
        $formData = [
            'state_of_exemption' => ['CA', 'NY'],
            'name_on_certificate' => 'test',
            'no_of_certificate' => $certificateNo,
            'initials' => 'JD'
        ];

        $expectedResult = [
            'taxExempt' => [
                [
                    'nameOnCertificate' => 'test',
                    'certificateNumber' => $certificateNo,
                    'stateOfExemption' => 'CA',
                    'nameInitial' => 'JD'
                ],
                [
                    'nameOnCertificate' => 'test',
                    'certificateNumber' => $certificateNo,
                    'stateOfExemption' => 'NY',
                    'nameInitial' => 'JD'
                ]
            ]
        ];

        $result = $this->pegaHelperMock->getTaxExemptData($dataArr, $formData);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for getTaxExemptData with Empty state of exemption
     *
     * @return void
     */
    public function testGetTaxExemptDataEmptyState()
    {
        $dataArr = [];
        $expectedResult = [];
        $formData = [
            'state_of_exemption' => [],
            'name_on_certificate' => 'Jane Smith',
            'no_of_certificate' => '54321',
            'initials' => 'JS'
        ];

        $result = $this->pegaHelperMock->getTaxExemptData($dataArr, $formData);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for getAuthorizedUserData
     *
     * @return void
     */
    public function testGetAuthorizedUserData()
    {
        $dataArr = [
            'nameOnAccount' => 'John Doe',
            'authorizedUser' => [],
        ];

        $formData = [
            'is_card_required' => true,
        ];

        $expectedResult = [
            'nameOnAccount' => 'John Doe',
            'authorizedUser' => [
                [
                    "cardDisplayName" => '',
                    "emailAddress" => '',
                    "isUserCardRequired" => 'Yes',
                    "isBulkDelivery" => 'No',
                    "isCustomInstruction" => 'No',
                    "isAuthorizedUserCommunicationSuppressed" => 'No',
                ]
            ],
        ];

        $result = $this->pegaHelperMock->getAuthorizedUserData($dataArr, $formData);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for callPegaAccountCreateApi
     *
     * @return void
     */
    public function testCallPegaAccountCreateApi()
    {
        $this->adminConfigHelper = $this->createMock(AdminConfigHelper::class);
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

        $this->adminConfigHelper->expects($this->any())
            ->method('isLogEnabled')
            ->willReturn(true);

        $expectedResult = ['Status' => 'Success'];

        $result = $this->pegaHelperMock->callPegaAccountCreateApi($setupURL, $headers, $dataString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for prepareData
     *
     * @return void
     */
    public function testPrepareData()
    {
        $this->timezoneMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('format')
            ->willReturn("2023-02-01 01:01:01");
        $data =  '{
            "caseTypeID": "FXO-ECAM-Work-AccountCreation",
            "processID": "pyStartCase",
            "content": {
                "AccountName": "",
                "LegalCompanyName": "FEDEX OFFICE UAT",
                "EnablePend": false,
                "CountryCode": "US",
                "PostalCode": "75024",
                "ContactNumber": "4699804760",
                "ApplicationSource": "Mail",
                "IsLocked": true,
                "LockedByUserId": "4469120",
                "LockedByUserName": "Rajesh",
                "IsAutoAssigned": true,
            }
        }';

        $this->assertNotNull($data, $this->pegaHelperMock->prepareData($this->pegaHelperMock->formData));
    }

    /**
     * Test method for getPegaApiResponse
     *
     * @return void
     */
    public function testGetPegaApiResponse()
    {
        $this->timezoneMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('format')
            ->willReturn("2023-02-01 01:01:01");
        $this->punchoutHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())
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
            $this->pegaHelperMock->getPegaApiResponse($this->pegaHelperMock->formData)
        );
    }

    /**
     * Test method for getPegaApiResponse with empty token
     *
     * @return void
     */
    public function testGetPegaApiResponseWithFalse()
    {
        $this->timezoneMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('format')
            ->willReturn("2023-03-01 01:01:01");
        $this->punchoutHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())
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
            $this->pegaHelperMock->getPegaApiResponse($this->pegaHelperMock->formData)
        );
    }

    /**
     * Test method for getInvoicingData
     *
     * @return void
     */
    public function testGetInvoicingData()
    {
        $this->timezoneMock->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneMock->expects($this->any())->method('format')
            ->willReturn("2023-01-01 01:01:01");
        $dataArr = [];
        $formData = [
            'charge_special_requirements' => true,
            'charge_cid_psg_country' => 'US',
            'charge_acc_bill_checkbox_val' => 1,
            'street_add' => 'test',
            'add_line2' => 'test',
            'suite_other' => 'test',
            'city' => 'test',
            'cid_psg_state' => 'test',
            'cid_psg_country' => 'USA',
            'zip' => '23244',
            'contact_fname' => 'test',
            'contact_lname' => 'test',
            'email' => 'test@testmail.com',
            'date_of_incorp' => '01/09/2023',
            'in_buiseness_since' => '01/09/2023'
        ];
        $currDate = '2023-08-10 12:00:00';
        $phoneFaxDefault = ['areaCd' => '', 'lineNum' => ''];
        $physicalPhone = ['areaCd' => '111', 'lineNum' => '1111111'];
        $physicalFax = ['areaCd' => '222', 'lineNum' => '2222222'];
        $expectedResult = [
            'invoicing' => [
                'stateOfIncorporation' => 'Some State',
            ],
            'address' => [
                [
                    'type' => 'Billing',
                    'addressLine1' => '123 Billing Street',

                ]
            ],
            'contact' => [
                [
                    'type' => 'Billing',
                    'firstName' => 'John',
                ]
            ]

        ];
        $result = $this->pegaHelperMock
        ->getInvoicingData($dataArr, $formData, $currDate, $phoneFaxDefault, $physicalPhone, $physicalFax);

        $this->assertNotEquals($expectedResult, $result);
    }
}
