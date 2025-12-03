<?php
/**
 * @category    Fedex
 * @package     Fedex_FujitsuReceipt
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Bhairav Singh <bhairav.singh.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FujitsuReceipt\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FujitsuReceipt\Model\FujitsuReceipt;
use Fedex\FujitsuReceipt\Model\TransactionReceiptApi\FujitsuTransactionReceiptApiHandler;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class FujitsuReceiptTest extends TestCase
{
    protected $apiHandlerMock;
    protected $toggleConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $fujitsuReceiptMock;
    protected $transactionId = "ADSKD2B3645A20F607X";

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

    protected $fujitsuReceiptResponseData = '{
        "transactionId":"9e7e652a-b399-4141-91e8-780e8866698a",
        "output":{
            "transactionReceipt":{
                "retailTransactionId":"ADSKD2B3645A20F607X"
            }
        }
    }';

    protected $orderData = [
        "retail_transaction_id" => "ADSKD2B3645A20F607X",
        "customer_first_name" => "Brajmohan",
        "customer_last_name" => "Rajput",
        "customer_email" => "brajmohan.rajput.osv@fedex.com"
    ];

    protected $notFoundResponse = '{
        "transactionId":"ef0ce145-0bfc-4f59-909d-3fdd5ec575a0",
        "errors":[{
            "code":"DATA.NOT.FOUND",
            "message":"Data Not Found for the transaction."
        }]
    }';

    /**
     * Main set up method
     */
    public function setUp() : void
    {
        $this->apiHandlerMock = $this->createMock(FujitsuTransactionReceiptApiHandler::class);

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->fujitsuReceiptMock = $this->objectManager->getObject(
            FujitsuReceipt::class,
            [
                'apiHandler' => $this->apiHandlerMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * Test case for sendFujitsuReceiptConfirmationEmail
     * @return void
     */
    public function testSendFujitsuReceiptConfirmationEmail():void
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->apiHandlerMock->expects($this->any())->method('prepareFujitsuReceiptApiRequestData')
        ->willReturn(json_encode($this->fujitsuReceiptRequestData));

        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
        ->willReturn(json_decode($this->fujitsuReceiptResponseData, true));

        $this->assertNotNull($this->fujitsuReceiptMock->sendFujitsuReceiptConfirmationEmail($this->orderData));
    }

    /**
     * Test case for sendFujitsuReceiptConfirmationEmail
     * @return void
     */
    public function testSendFujitsuReceiptConfirmationEmailWithFalse():void
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->apiHandlerMock->expects($this->any())->method('prepareFujitsuReceiptApiRequestData')
        ->willReturn(json_encode($this->fujitsuReceiptRequestData));

        $this->apiHandlerMock->expects($this->any())->method('callCurlPost')
        ->willReturn(json_decode($this->notFoundResponse, true));

        $this->assertFalse($this->fujitsuReceiptMock->sendFujitsuReceiptConfirmationEmail($this->orderData));
    }
}
