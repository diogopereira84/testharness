<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Catalog\Test\Unit\Controller\Product;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Catalog\Controller\Product\NewDocumentApi;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class NewDocumentApiTest extends TestCase
{
    protected $resultJsonFactoryMock;
    protected $curlMock;
    protected $requestMock;
    protected $scopeConfigMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $uploaderFactory;
    protected $fileSystemMock;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime\DateTime & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dateTimeMock;
    protected $readInterfaceMock;
    protected $newDocumentApi;
    public const APIURL = 'https://documentapitest.prod.fedex.com/document/fedexoffice/v2/documents';
    public const EXPIRATION_DAYS = 30;
    public const CHECK_PDF = false;
    public const EXTENSIONS = 'png, pdf, jpg, jpeg, gif, bmp , ps, jpe, dib, jp2, jff, tiff, tif, psg, pg, doc, docx, rtf, txt, xls, xlsx, ppt, pptx';
    public const SKU = '029a5a12-5d24-efdb-1ad1-930cbd5297a9';
    public const FILE = [
        'name' => 'D-191865.docx',
        'full_path' => 'D-191865.docx',
        'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'tmp_name' => '/tmp/phpBzCnBb',
        'error' => 0,
        'size' => 991519
    ];
    public const RESULT = [
        "name" => "the-test-fun-for-friends-screenshot_1.png",
        "full_path" => "the-test-fun-for-friends-screenshot.png",
        "type" => "image/png",
        "tmp_name" => "/tmp/phpz2Ju4Z",
        "error" => "0",
        "size" => "20363",
        "path" => "/var/www/html/staging3.office.fedex.com/pub/media/tmp",
        "file" => "the-test-fun-for-friends-screenshot_1.png",
    ];
    public const API_OUTPUT = '{"transactionId":"3baef724-1b5f-45af-919a-fe0f763eeabf","output":{"document":{"documentId":"285c736a-b7d1-11ef-9d1f-d7c6d0e4069c","sourceDocuments":[{"documentId":"285c736a-b7d1-11ef-9d1f-d7c6d0e4069c","documentOperation":"UPLOAD"}],"documentName":"target.txt","sensitiveData":false,"printReady":false,"expirationTime":"2025-01-10T15:04:02.497Z","documentSize":7018,"documentType":"text\/plain","documentURL":"https:\/\/documentapitest.prod.fedex.com\/document\/fedexoffice\/v2\/documents\/285c736a-b7d1-11ef-9d1f-d7c6d0e4069c\/content","pdfForm":false,"currentDateTime":null}}}';

    /**
     * Setup function
     */
    protected function setUp(): void
    {
        $this->resultJsonFactoryMock = $this
            ->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'create'])
            ->getMock();
        $resultJson = $this->createMock(ResultJson::class);
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody', 'setOptions', 'post'])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->uploaderFactory = $this->getMockBuilder(UploaderFactory::class)
            ->setMethods(
                ['create', 'save', 'setAllowedExtensions', 'setAllowRenameFiles']
            )->disableOriginalConstructor()
            ->getMock();
        $this->fileSystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->setMethods(['gmtDate'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->readInterfaceMock = $this->getMockForAbstractClass(ReadInterface::class);
        $objectManagerHelper = new ObjectManager($this);

        $this->newDocumentApi = $objectManagerHelper->getObject(
            NewDocumentApi::class,
            [
                'resultJsonFactory'     => $this->resultJsonFactoryMock,
                'curl'                  => $this->curlMock,
                'request'               => $this->requestMock,
                'scopeConfig'           => $this->scopeConfigMock,
                'logger'                => $this->loggerMock,
                'uploaderFactory'       => $this->uploaderFactory,
                'filesystem'            => $this->fileSystemMock,
            ]
        );
    }

    /**
     * testExecute
     */
    public function testExecute()
    {
        $resultJson = $this->createMock(ResultJson::class);

        $this->requestMock->expects($this->any())
            ->method('getParam')->with('sku')
            ->willReturn(self::SKU);

        $this->requestMock->expects($this->any())
            ->method('getFiles')->with('filepath')
            ->willReturn(self::FILE);

        $this->uploaderFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->fileSystemMock->expects($this->any())->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->readInterfaceMock);

        $this->readInterfaceMock->expects($this->any())
            ->method('getAbsolutePath')->willReturn('pub/media/tmp');
        $this->uploaderFactory->expects($this->any())
            ->method('save')
            ->willReturn(self::RESULT);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->withConsecutive(
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_supported_types'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_api_url'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_expiration'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_check_pdf']
            )
            ->willReturnOnConsecutiveCalls(self::EXTENSIONS, self::APIURL, self::EXPIRATION_DAYS, self::CHECK_PDF);

        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::API_OUTPUT);

        $this->resultJsonFactoryMock->method('create')->willReturn($resultJson);

        $resultJson->expects($this->any())
            ->method('setData')
            ->with(json_decode(self::API_OUTPUT, true));

       // $this->assertNull($this->newDocumentApi->execute());
    }

    /**
     * testExecute with no result
     */
    public function testExecuteNoResult()
    {
        $resultJson = $this->createMock(ResultJson::class);

        $this->requestMock->expects($this->any())
            ->method('getParam')->with('sku')
            ->willReturn(self::SKU);

        $this->requestMock->expects($this->any())
            ->method('getFiles')->with('filepath')
            ->willReturn(self::FILE);

        $this->uploaderFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->fileSystemMock->expects($this->any())->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->readInterfaceMock);

        $this->readInterfaceMock->expects($this->any())
            ->method('getAbsolutePath')->willReturn('pub/media/tmp');
        $this->uploaderFactory->expects($this->any())
            ->method('save')
            ->willReturn(null);
        $result = [
            'errors' => 'Error found while uploading request change files for product sku: 029a5a12-5d24-efdb-1ad1-930cbd5297a9 is: File cannot be saved to path: $1'
        ];
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJson);

        $resultJson->expects($this->once())
            ->method('setData')
            ->with($result);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->withConsecutive(
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_supported_types'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_api_url'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_expiration'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_check_pdf']
            )
            ->willReturnOnConsecutiveCalls(self::EXTENSIONS, self::APIURL, self::EXPIRATION_DAYS, self::CHECK_PDF);
        $this->assertNull($this->newDocumentApi->execute());
    }

    /**
     * Method for callNewDocumentApi
     */
    public function callNewDocumentApi()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->withConsecutive(
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_api_url'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_expiration'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_check_pdf']
            )
            ->willReturnOnConsecutiveCalls(self::APIURL, self::EXPIRATION_DAYS, self::CHECK_PDF);

        $this->curlMock->expects($this->any())
            ->method('setOptions')
            ->willReturnSelf();

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willreturn(self::API_OUTPUT);

        $this->curlMock->method('post')
            ->willReturnSelf();

        return $this->newDocumentApi->callNewDocumentApi(self::RESULT, self::SKU);
    }

    /**
     * Method for callNewDocumentApi
     */
    public function testCallNewDocumentApi()
    {
        $this->assertNotNull($this->callNewDocumentApi());
    }

    /**
     * test method for callNewDocumentApiWithException
     */
    public function testCallNewDocumentApiWithException()
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->withConsecutive(
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_api_url'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_expiration'],
                ['fedex/non_standard_catalog_popup_model_replace_file_config/replace_file_check_pdf']
            )
            ->willReturnOnConsecutiveCalls(self::APIURL, self::EXPIRATION_DAYS, self::CHECK_PDF);

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willThrowException(new \Exception());

        $this->assertNotNull($this->newDocumentApi->callNewDocumentApi(self::RESULT, self::SKU));
    }
}
