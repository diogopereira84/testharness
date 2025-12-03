<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Controller\Newdocapi;

use Magento\Framework\App\Action\Context;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOCMConfigurator\Controller\Newdocapi\Index;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class IndexTest extends TestCase
{

    protected $jsonFactoryMock;
    protected $resultJson;
    protected $catalogDocumentRefranceApiMock;
    protected $request;
    protected $indexController;
    protected $context;
    protected $jsonFactory;
    protected $toggleConfigMock;
    protected $fileDriverMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();

        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

      $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->setMethods(['curlCallForPreviewApi', 'getPreviewImageUrl'])
            ->disableOriginalConstructor()
            ->getMock();

      $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();      

        $context = $objectManager->getObject(Context::class);

        $this->fileDriverMock = $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->setMethods(['fileGetContents'])
            ->getMock();

      $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->indexController = $objectManager->getObject(
            Index::class,
            [
                'jsonFactory' => $this->jsonFactoryMock,
                'catalogDocumentRefranceApi' => $this->catalogDocumentRefranceApiMock,
                '_request' => $this->request,
                'fileDriver' => $this->fileDriverMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * 
     */
    public function testExecute()
    {

        $imageId = '16780645845007665842800280399200186540913';
        $previewImageUrl = 'http://example.com/image.jpg';
        $imageData = 'dummy_image_data';
        $expectedResponse = [
            'successful' => true,
            'output' => ['imageByteStream' => base64_encode($imageData)]
        ];
        $this->request->expects($this->any())->method('getParam')->willReturn($imageId);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->catalogDocumentRefranceApiMock->expects($this->any())->method('getPreviewImageUrl')->willReturn($previewImageUrl);
        $this->fileDriverMock->expects($this->any())->method('fileGetContents')->willReturn($imageData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->with($expectedResponse)->willReturnSelf();

        $result = $this->indexController->execute();
        $this->assertInstanceOf(ResultJson::class, $result);
    }

    public function testExecuteWithToggleOff()
    {
        $imageId = '16780645845007665842800280399200186540913';
        $imageData = 'curl_response_data';
        $expectedResponse = [
            'successful' => true,
            'output' => ['imageByteStream' => base64_encode($imageData)]
        ];

        $this->request->expects($this->any())->method('getParam')->willReturn($imageId);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->catalogDocumentRefranceApiMock->expects($this->any())->method('curlCallForPreviewApi')->willReturn($imageData);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->any())->method('setData')->with($expectedResponse)->willReturnSelf();

        $result = $this->indexController->execute();
        $this->assertInstanceOf(ResultJson::class, $result);
    }
    
}
