<?php
/**
 * @category    Fedex
 * @package     Fedex_FujitsuReceipt
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Bhairav Singh <bhairav.singh.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FujitsuReceipt\Test\Unit\Model\TransactionReceiptApi;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\FujitsuReceipt\Model\TransactionReceiptApi\FujitsuTransactionReceiptApiHandler;

class FujitsuTransactionReceiptApiHandlerTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $apiHandlerMock;
    public const CONTENT_TYPE = "Content-Type: application/json";
    public const ACCEPT_TYPE = "Accept: application/json";
    public const ACCEPT_LANGUAGE = "Accept-Language: json";
    public const AUTHORIZATION_TYPE = "Authorization: Bearer ";
    public const COOKIE_TYPE = "Cookie: Bearer=";
    public const BEHALF_OF = "X-On-Behalf-Of: 1";
    public const CLIENT_ID = "client_id";

    /**
     * Mock token key
     */
    public const TOKEN_KEY = '4f5e303d-c10f-40bf-a586-16e177faaec3';

    protected $transactionReceiptRequestData = [
        "transactionReceiptRequest" => [
            "transactionId" => "ADSKD2B3645A20F607X",
            "pricingStore" => "9890",
            "transactionReceiptDetails" => [
                "receiptType" => "PRINT",
                "receiptFormat" => "INVOICE_EIGHT_BY_ELEVEN"
            ]
        ]
    ];

    protected $fujitsuReceiptRequestData = [
        "transactionReceiptRequest" => [
            "transactionId" => "ADSKD2B3645A20F607X",
            "pricingStore" => "9890",
            "transactionReceiptDetails" => [
                "receiptType" => "EMAIL",
                "receiptFormat" => "INVOICE_EIGHT_BY_ELEVEN"
            ],
            "contact" => [
                "personName" => [
                    "firstName" => "Brajmohan",
                    "lastName" => "Rajput"
                ],
                "emailDetail" => [
                    "emailAddress" => "brajmohan.rajput.osv@fedex.com"
                ]
            ]
        ]
    ];

    protected $orderData = [
        "retail_transaction_id" => "ADSKD2B3645A20F607X",
        "customer_first_name" => "Brajmohan",
        "customer_last_name" => "Rajput",
        "customer_email" => "brajmohan.rajput.osv@fedex.com"
    ];

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configInterfaceMock;

    /**
     * @var DeliveryHelper|MockObject
     */
    protected $deliveryHelperMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var Curl|MockObject
     */
    protected $curlMock;

    /**
     * @var PunchoutHelper|MockObject
     */
    private $punchoutHelperMock;

    /**
     * @var SubmitOrderHelper|MockObject
     */
    private $submitOrderHelperMock;

    /**
     * Main set up method
     */
    public function setUp() : void
    {
        $this->configInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);
        $this->punchoutHelperMock = $this->createMock(PunchoutHelper::class);
        $this->submitOrderHelperMock = $this->createMock(SubmitOrderHelper::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->curlMock = $this->createMock(Curl::class);

        $this->objectManager = new ObjectManager($this);
        $this->apiHandlerMock = $this->objectManager->getObject(
            FujitsuTransactionReceiptApiHandler::class,
            [
                'configInterface' => $this->configInterfaceMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'submitOrderHelper' => $this->submitOrderHelperMock,
                'logger' => $this->loggerMock,
                'curl' => $this->curlMock
            ]
        );
    }

    /**
     * Test function for getConfigValue
     */
    public function testGetConfigValue():void
    {
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturnSelf();
        $this->assertNotNull($this->apiHandlerMock->getConfigValue('test'));
    }

    /**
     * @return void
     */
    public function testGetHeaders():void
    {
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        $gateWayToken = '';
        $tokenStr = ['token'=> ''];
        $headers = [
            static::CONTENT_TYPE,
            static::ACCEPT_TYPE,
            static::ACCEPT_LANGUAGE,
            static:: CLIENT_ID . $gateWayToken,
            static::COOKIE_TYPE. $tokenStr['token'],
            static::BEHALF_OF
        ];

        $this->submitOrderHelperMock->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn($headers);
        $this->assertEquals($headers, $this->apiHandlerMock->getHeaders($tokenStr));
    }

    /**
     * @return void
     */
    public function testGetHeadersCustomerSessionFalse():void
    {
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        $gateWayToken = '';
        $tokenStr = ['token'=> ''];
        $headers = [
            static::CONTENT_TYPE,
            static::ACCEPT_TYPE,
            static::ACCEPT_LANGUAGE,
            static:: CLIENT_ID . $gateWayToken,
            static::COOKIE_TYPE. $tokenStr['token'],
        ];

        $this->submitOrderHelperMock->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn($headers);
        $this->assertEquals($headers, $this->apiHandlerMock->getHeaders($tokenStr));
    }

    /**
     * @return void
     */
    public function testPrepareFujitsuReceiptApiRequestData():void
    {
        $this->assertEquals(
            json_encode($this->fujitsuReceiptRequestData),
            $this->apiHandlerMock->prepareFujitsuReceiptApiRequestData($this->orderData)
        );
    }

    /**
     * Test case for callCurlPost
     * @return void
     */
    public function testCallCurlPost():void
    {
        $data = '{
            "transactionId":"JMDKN7496461AC6B0CX",
            "pricingStore":"9890",
            "transactionReceiptDetails":{
                "receiptType":"PRINT",
                "receiptFormat":"INVOICE_EIGHT_BY_ELEVEN"
            }
        }}';
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
        ->willReturn('https://api.test.office.fedex.com/transaction/fedexoffice/v1/transactionreceipts');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        $this->curlMock->expects($this->any())->method('getBody')->willreturn($data);
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->assertNull($this->apiHandlerMock->callCurlPost(json_encode($this->transactionReceiptRequestData)));
    }

    /**
     * Test case for callCurlPost error response
     * @return void
     */
    public function testCallCurlPostError():void
    {
        $data = '{
            "transactionId":"a028fbfb-5adf-4432-a17d-84123145acdd",
            "errors":[{
                "code":"DATA.NOT.FOUND",
                "message":"Data Not Found for the transaction."
            }]
        }';
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
        ->willReturn('https://api.test.office.fedex.com/transaction/fedexoffice/v1/transactionreceipts');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        $this->curlMock->expects($this->any())->method('getBody')->willreturn($data);
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->assertNotNull($this->apiHandlerMock->callCurlPost(json_encode($this->transactionReceiptRequestData)));
    }

    /**
     * Test case for callCurlPost with Exception
     * @return void
     */
    public function testCallCurlPostWithException():void
    {
        $exception = new \Exception();
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->curlMock->expects($this->any())->method('post')->willThrowException($exception);

        $this->assertNull($this->apiHandlerMock->callCurlPost(json_encode($this->transactionReceiptRequestData)));
    }
}
