<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Helper;

use Fedex\CatalogMvp\Helper\Download;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Psr\Log\LoggerInterface;

class DownloadTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $download;
    public const EXTERNAL_PROD = '{"productionContentAssociations":[],"userProductName":"FedEx_Express_logo43234","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1700473886837,"proofRequired":false,"isOutSourced":false,"minDPI":"150.0","features":[{"id":"1464882763509","name":"Product Type","choice":{"id":"1464884397179","name":"Canvas Prints","properties":[{"id":"1494365340946","name":"PREVIEW_TYPE","value":"STATIC"},{"id":"1514365340957","name":"VISUALIZATION_TYPE","value":"3D"}]}},{"id":"1448981549741","name":"Paper Type","choice":{"id":"1448989268401","name":"Canvas Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"ROL05"}]}},{"id":"1448981549109","name":"Size","choice":{"id":"1449002054022","name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448985622584","name":"Mounting","choice":{"id":"1466532051072","name":"1 1\/2 Wooden Frame","properties":[{"id":"1518627861660","name":"MOUNTING_TYPE","value":"1.5_WOOD_FRAME"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016327","name":"Horizontal","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"LANDSCAPE"}]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"0347d623-878a-11ee-acb2-7b4c2bc264e5","contentReference":"09916d5d-878a-11ee-a1cb-6924b32f57f9","contentType":"IMAGE","fileName":"FedEx_Express_logo.png","contentReqId":"1455709847200","name":"Poster","desc":null,"purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,"pageGroups":[{"start":1,"end":1,"width":36,"height":24,"orientation":"LANDSCAPE"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"50"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID","value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636","name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION TEAM MEMBER: Use the following instructions to produce this order. DO NOT use the Production Instructions listed above. Specifications: 24 in. x 36 in. Canvas Print Package, SKU 2337, ROL05 Canvas Matte."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"36"}]}';

    /**
     * @var CatalogDocumentRefranceApi|Mock
     */
    protected $catalogDocumentMock;

    /**
     * @var ScopeConfigInterface|Mock
     */
    protected $scopeConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogDocumentMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->setMethods(['curlCall'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->download = $objectManagerHelper->getObject(
            Download::class,
            [
                'catalogDocumentRefranceApi' => $this->catalogDocumentMock,
                'scopeConfigInterface' => $this->scopeConfigMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * @test testGetCreateZipApiUrl
     */
    public function testGetCreateZipApiUrl()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturnSelf();

        $this->assertNotNull($this->download->getCreateZipApiUrl());
    }

    /**
     * @test testGetCreateZipApiUrl
     */
    public function testGetDownloadFileUrl()
    {
        $zipApiUrl = "https://www.test.com";
        $externalProd = static::EXTERNAL_PROD;
        
        $this->assertNull($this->download->getDownloadFileUrl($zipApiUrl, $externalProd));
    }

    /**
     * @test testGetCreateZipApiUrl
     */
    public function testPrepareRequestForZipApi()
    {
        $productName = "Test";
        $externalProd = static::EXTERNAL_PROD;
        
        $this->assertIsArray($this->download->prepareRequestForZipApi($productName, $externalProd));
    }

    /**
     * @test testGetCreateZipApiUrl
     */
    public function testCallCreateZipApi()
    {
        $productName = "Test";
        $prepareRequest = '{
              "zipDocumentName": "test.zip",
              "expiration": {
                "units": "SECONDS",
                "value": 36000
              },
              "sourceDocumentsPathEntries": [
                {
                  "documentId": "82517cc6-892d-11ee-875c-03cd9de4e5d5",
                  "pathEntry": "abc"
                },
                {
                  "documentId": "7831a0c5-8874-11ee-a1cb-6924b32f57f9",
                  "pathEntry": "abc/def"
                }
              ]
            }';

        $responseData = '{
            "output": {
                "document": {
                    "documentURL": "https://fxo-document-download-cxs-staging.app.paas.fedex.com/document/fedexoffice/v2/documents/8ec94a3f-8a7f-11ee-b9e5-63d37a0ca34d/content"
                }
            }
        }';

        $this->catalogDocumentMock->expects($this->once())
            ->method('curlCall')
            ->willReturn(json_decode($responseData, true));

        $this->assertNotNull($this->download->callCreateZipApi($productName, json_encode($prepareRequest)), 'POST');
    }

    /**
     * @test testGetCreateZipApiUrl with exception
     */
    public function testCallCreateZipApiWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $productName = "Test";
        $prepareRequest = '{
              "zipDocumentName": "test.zip",
              "expiration": {
                "units": "SECONDS",
                "value": 36000
              },
              "sourceDocumentsPathEntries": [
                {
                  "documentId": "82517cc6-892d-11ee-875c-03cd9de4e5d5",
                  "pathEntry": "abc"
                },
                {
                  "documentId": "7831a0c5-8874-11ee-a1cb-6924b32f57f9",
                  "pathEntry": "abc/def"
                }
              ]
            }';

        $responseData = '{
            "output": {
                "document": {
                    "documentURL": "https://fxo-document-download-cxs-staging.app.paas.fedex.com/document/fedexoffice/v2/documents/8ec94a3f-8a7f-11ee-b9e5-63d37a0ca34d/content"
                }
            }
        }';

        $this->catalogDocumentMock->expects($this->once())
            ->method('curlCall')
            ->willThrowException($exception);

        $this->assertNull($this->download->callCreateZipApi($productName, json_encode($prepareRequest)), 'POST');
    }
}
