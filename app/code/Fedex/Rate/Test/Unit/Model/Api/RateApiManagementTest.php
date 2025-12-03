<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Rate\Test\Unit\Model\Api;

use Fedex\Rate\Model\Api\RateApiManagement;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Rate\Helper\ApiRequest;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Fedex\Catalog\Model\Config;
use Fedex\Company\Helper\Data;
use Fedex\EnhancedProfile\Helper\Account;

class RateApiManagementTest extends TestCase
{
   /**
    * @var ApiRequest
    */
    protected $apiRequestMockup;

    /**
     * @var Request
     */
    protected $requestMockup;

    /**
     * @var Json
     */
    protected $jsonMockup;

    /**
     * @var Account
     */
    protected $accountHelperMock;

    /**
     * @var Data
     */
    protected $companyHelperMock;

    /**
     * @var Config
     */
    protected $catalogConfigMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMockup;

    /**
     * @var RateApiManagement
     */
    protected $rateApiManagementModel;
    /**
     * Setup method to creating mock object
     */
    protected function setUp(): void
    {
        $this->apiRequestMockup = $this->getMockBuilder(ApiRequest::class)
            ->setMethods(['priceProductApi'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMockup = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock();
        $this->jsonMockup = $this->getMockBuilder(Json::class)
            ->setMethods(['serialize','unserialize'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMockup = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

         $this->accountHelperMock = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore', 'getIsSelfRegStore', 'getActivePersonalAccountList'])
            ->getMock();

         $this->companyHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFedexAccountNumber'])
            ->getMock();

         $this->catalogConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCommercialPDPToggle'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->rateApiManagementModel = $objectManagerHelper->getObject(
            RateApiManagement::class,
            [
               'request' => $this->requestMockup,
               'apiRequest' => $this->apiRequestMockup,
               'json' => $this->jsonMockup,
               'logger' => $this->loggerMockup,
               'accountHelper' => $this->accountHelperMock,
               'companyHelper' => $this->companyHelperMock,
               'catalogConfig' => $this->catalogConfigMock,
            ]
        );
    }

    /**
     * @test rateProduct
     * @return void
     */

    public function testRateProduct()
    {
        $originalContent = '{"rateRequest":{"test":"data"}}';
        $expectedFedexAccount = '123456789';
        $modifiedContent = '{"rateRequest":{"test":"data","fedExAccountNumber":"123456789"}}';
        $expectedApiResponse = '{"status":true}';

        $this->catalogConfigMock->expects($this->any())
         ->method('getCommercialPDPToggle')
         ->willReturn(true);

        $this->accountHelperMock->expects($this->any())
         ->method('getIsSdeStore')
         ->willReturn(true);

        $this->companyHelperMock->expects($this->any())
         ->method('getFedexAccountNumber')
         ->willReturn($expectedFedexAccount);

        $this->requestMockup->expects($this->any())
         ->method('getContent')
         ->willReturn($originalContent);

        $this->jsonMockup->expects($this->any())
         ->method('unserialize')
         ->with($originalContent)
         ->willReturn(['rateRequest' => ['test' => 'data']]);

        $this->jsonMockup->expects($this->any())
         ->method('serialize')
         ->with($this->anything())
         ->willReturnCallback(function ($arg) {
               return json_encode($arg);
         });

        $this->apiRequestMockup->expects($this->any())
        ->method('priceProductApi')
        ->with($modifiedContent)
        ->willReturn(['status' => true]);

        $result = $this->rateApiManagementModel->rateProduct();
        $this->assertEquals($expectedApiResponse, $result);
    }

    public function testRateProductWithCommercialToggleAndNoFedexAccount()
    {
        $originalContent = '{"rateRequest":{"test":"data"}}';

        $this->catalogConfigMock->expects($this->any())
         ->method('getCommercialPDPToggle')
         ->willReturn(true);

        $this->accountHelperMock->expects($this->any())
         ->method('getIsSdeStore')
         ->willReturn(true);

        $this->accountHelperMock->expects($this->never())
         ->method('getIsSelfRegStore');

        $this->companyHelperMock->expects($this->any())
         ->method('getFedexAccountNumber')
         ->willReturn(null);

        $this->requestMockup->expects($this->any())
         ->method('getContent')
         ->willReturn($originalContent);

        $this->jsonMockup->expects($this->any())
         ->method('unserialize')
         ->willReturn(['rateRequest' => ['test' => 'data']]);

        $this->accountHelperMock->expects($this->any())
         ->method('getActivePersonalAccountList')
         ->with('payment')
         ->willReturn([]);

        $this->jsonMockup->expects($this->any())
         ->method('serialize')
         ->willReturnCallback(fn($arg) => json_encode($arg));

        $this->apiRequestMockup->expects($this->any())
         ->method('priceProductApi')
         ->with(json_encode(['rateRequest' => ['test' => 'data']]))
         ->willReturn(['status' => true]);

        $result = $this->rateApiManagementModel->rateProduct();
        $this->assertEquals('{"status":true}', $result);
    }

    public function testRateProductWithCommercialToggleOff()
    {
        $originalContent = '{"rateRequest":{"test":"data"}}';

        $this->catalogConfigMock->expects($this->any())
         ->method('getCommercialPDPToggle')
         ->willReturn(false);

        $this->accountHelperMock->expects($this->any())
         ->method('getIsSdeStore')
         ->willReturn(false);
         
        $this->accountHelperMock->expects($this->any())
         ->method('getIsSelfRegStore')
         ->willReturn(false);

        $this->companyHelperMock->expects($this->never())
         ->method('getFedexAccountNumber');

        $this->requestMockup->expects($this->any())
         ->method('getContent')
         ->willReturn($originalContent);

        $this->jsonMockup->expects($this->any())
         ->method('unserialize')
         ->willReturn(['rateRequest' => ['test' => 'data']]);

        $this->jsonMockup->expects($this->any())
         ->method('serialize')
         ->willReturnCallback(fn($arg) => json_encode($arg));

        $this->apiRequestMockup->expects($this->any())
         ->method('priceProductApi')
         ->with(json_encode(['rateRequest' => ['test' => 'data']]))
         ->willReturn(['status' => true]);

        $result = $this->rateApiManagementModel->rateProduct();
        $this->assertEquals('{"status":true}', $result);
    }

    /**
     * @test rateProduct with Exception
     * @return void
     *
     */
    public function testRateProductWithException()
    {
        $exception = new \Exception();
        $this->requestMockup->expects($this->exactly(1))
         ->method('getContent')
         ->willReturn($this->contentData());
        $this->apiRequestMockup->expects($this->exactly(1))
         ->method('priceProductApi')
         ->with($this->contentData())
         ->willThrowException($exception);

        $this->assertEquals(null, $this->rateApiManagementModel->rateProduct());
    }

    /**
     * Content Data
     * @return string
     */
    private function contentData()
    {
        
        return '{
            "rateRequest": {
               "products": [
                  {
                     "productionContentAssociations": [],
                     "userProductName": null,
                     "id": "1463680545590",
                     "version": 1,
                     "name": "Flyer",
                     "qty": 50,
                     "priceable": true,
                     "instanceId": 1671429824374,
                     "proofRequired": false,
                     "isOutSourced": false,
                     "features": [
                        {
                           "id": "1448981549109",
                           "name": "Paper Size",
                           "choice": {
                              "id": "1448986650332",
                              "name": "8.5x11",
                              "properties": [
                                 {
                                    "id": "1449069906033",
                                    "name": "MEDIA_HEIGHT",
                                    "value": "11"
                                 },
                                 {
                                    "id": "1449069908929",
                                    "name": "MEDIA_WIDTH",
                                    "value": "8.5"
                                 },
                                 {
                                    "id": "1571841122054",
                                    "name": "DISPLAY_HEIGHT",
                                    "value": "11"
                                 },
                                 {
                                    "id": "1571841164815",
                                    "name": "DISPLAY_WIDTH",
                                    "value": "8.5"
                                 }
                              ]
                           }
                        },
                        {
                           "id": "1448981549581",
                           "name": "Print Color",
                           "choice": {
                              "id": "1448988600611",
                              "name": "Full Color",
                              "properties": [
                                 {
                                    "id": "1453242778807",
                                    "name": "PRINT_COLOR",
                                    "value": "COLOR"
                                 }
                              ]
                           }
                        },
                        {
                           "id": "1448981549269",
                           "name": "Sides",
                           "choice": {
                              "id": "1448988124560",
                              "name": "Single-Sided",
                              "properties": [
                                 {
                                    "id": "1470166759236",
                                    "name": "SIDE_NAME",
                                    "value": "Single Sided"
                                 },
                                 {
                                    "id": "1461774376168",
                                    "name": "SIDE",
                                    "value": "SINGLE"
                                 }
                              ]
                           }
                        },
                        {
                           "id": "1448984679218",
                           "name": "Orientation",
                           "choice": {
                              "id": "1449000016192",
                              "name": "Vertical",
                              "properties": [
                                 {
                                    "id": "1453260266287",
                                    "name": "PAGE_ORIENTATION",
                                    "value": "PORTRAIT"
                                 }
                              ]
                           }
                        },
                        {
                           "id": "1534920174638",
                           "name": "Envelope",
                           "choice": {
                              "id": "1634129308274",
                              "name": "None",
                              "properties": []
                           }
                        },
                        {
                           "id": "1448981549741",
                           "name": "Paper Type",
                           "choice": {
                              "id": "1448988666879",
                              "name": "Gloss Text",
                              "properties": [
                                 {
                                    "id": "1450324098012",
                                    "name": "MEDIA_TYPE",
                                    "value": "CT"
                                 },
                                 {
                                    "id": "1453234015081",
                                    "name": "PAPER_COLOR",
                                    "value": "#FFFFFF"
                                 },
                                 {
                                    "id": "1470166630346",
                                    "name": "MEDIA_NAME",
                                    "value": "Gloss Text"
                                 },
                                 {
                                    "id": "1471275182312",
                                    "name": "MEDIA_CATEGORY",
                                    "value": "TEXT_GLOSS"
                                 }
                              ]
                           }
                        }
                     ],
                     "pageExceptions": [],
                     "contentAssociations": [
                        {
                           "purpose": "SINGLE_SHEET_FRONT",
                           "printReady": true,
                           "contentReqId": "1455709847200",
                           "pageGroups": [
                              {
                                 "start": 1,
                                 "end": "1",
                                 "height": 11,
                                 "width": 8.5,
                                 "orientation": "PORTRAIT"
                              }
                           ]
                        }
                     ],
                     "properties": [
                        {
                           "id": "1453242488328",
                           "name": "ZOOM_PERCENTAGE",
                           "value": "60"
                        },
                        {
                           "id": "1453243262198",
                           "name": "ENCODE_QUALITY",
                           "value": "100"
                        },
                        {
                           "id": "1453894861756",
                           "name": "LOCK_CONTENT_ORIENTATION",
                           "value": false
                        },
                        {
                           "id": "1453895478444",
                           "name": "MIN_DPI",
                           "value": "150.0"
                        },
                        {
                           "id": "1454950109636",
                           "name": "USER_SPECIAL_INSTRUCTIONS",
                           "value": null
                        },
                        {
                           "id": "1455050109636",
                           "name": "DEFAULT_IMAGE_WIDTH",
                           "value": "8.5"
                        },
                        {
                           "id": "1455050109631",
                           "name": "DEFAULT_IMAGE_HEIGHT",
                           "value": "11"
                        },
                        {
                           "id": "1464709502522",
                           "name": "PRODUCT_QTY_SET",
                           "value": "50"
                        },
                        {
                           "id": "1459784717507",
                           "name": "SKU",
                           "value": "2821"
                        },
                        {
                           "id": "1470151626854",
                           "name": "SYSTEM_SI",
                           "value": "ATTENTION TEAM MEMBER: Use the following instructions to produce this order.DO NOT use the Production Instructions listed above. Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 Gloss Text (CT), Full Page."
                        },
                        {
                           "id": "1494365340946",
                           "name": "PREVIEW_TYPE",
                           "value": "DYNAMIC"
                        },
                        {
                           "id": "1470151737965",
                           "name": "TEMPLATE_AVAILABLE",
                           "value": "YES"
                        },
                        {
                           "id": "1459784776049",
                           "name": "PRICE",
                           "value": null
                        },
                        {
                           "id": "1490292304798",
                           "name": "MIGRATED_PRODUCT",
                           "value": "true"
                        },
                        {
                           "id": "1558382273340",
                           "name": "PNI_TEMPLATE",
                           "value": "NO"
                        },
                        {
                           "id": "1602530744589",
                           "name": "CONTROL_ID",
                           "value": "0"
                        },
                        {
                           "id": "1614715469176",
                           "name": "IMPOSE_TEMPLATE_ID",
                           "value": "0"
                        }
                     ]
                  }
               ],
               "validateContent": false
            }
         }';
    }

    /**
     * Price product api response
     * @return string
     */
    private function priceProductApiResponse()
    {
        return '{"response":{"transactionId":"883aa1ec-a711-4a30-9b57-e8b7257770ca","output":{"rate":{"currency":"USD","rateDetails":[{"productLines":[{"instanceId":"1671429824374","productId":"1463680545590","name":"Flyer","retailPrice":"$0.00","discountAmount":"$0.00","unitQuantity":50,"linePrice":"$0.00","unitOfMeasurement":"EACH","priceable":true,"productLineDetails":[{"detailCode":"40005","description":"Full Pg Clr Flyr 50","detailCategory":"PRINTING","unitQuantity":1,"detailPrice":"$34.99","detailDiscountPrice":"$0.00","detailUnitPrice":"$34.9900","detailDiscountedUnitPrice":"$0.00"}],"productRetailPrice":"$34.99","productDiscountAmount":"$0.00","productLinePrice":"$34.99","editable":false}],"deliveryLines":[],"grossAmount":"$34.99","totalDiscountAmount":"$0.00","netAmount":"$34.99","taxableAmount":"$34.99","taxAmount":"$0.00","totalAmount":"$34.99","estimatedVsActual":"ACTUAL"}]}}},"status":true}';
    }
}
