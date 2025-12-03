<?php

namespace Fedex\CatalogMvp\Model\Test\Unit;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Model\DocRefSubscriber;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CatalogMvp\Api\DocRefMessageInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class DocRefSubscriberTest extends TestCase
{
    protected $toggleConfig;
    protected $catalogDocumentRefranceApiMock;
    protected $message;
    protected $loggerInterface;
    /**
     * @var (\Magento\Catalog\Model\ProductFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productFactory;
    /**
     * @var (\Magento\Catalog\Model\Product & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $product;
    protected $serializerJson;
    protected $DocRefSubscriber;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
        ->DisableOriginalConstructor()
        ->setMethods(['documentLifeExtendApiCall','curlCall'])
        ->getMock();

        $this->message = $this->getMockBuilder(DocRefMessageInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();
            $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        $this->productFactory = $this->getMockBuilder(ProductFactory::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->DisableOriginalConstructor()
            ->setMethods(['load','setStatus','save'])
            ->getMock();
        $this->serializerJson = $this->getMockBuilder(Json::class)
            ->DisableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->DocRefSubscriber = $objectManagerHelper->getObject(
            DocRefSubscriber::class,
            [
                'serializerJson' => $this->serializerJson,
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'productFactory' => $this->productFactory,
                'catalogDocRefApi' => $this->catalogDocumentRefranceApiMock
            ]
        );
    }

    public function testProcessMessageExtandExpire()
    {
        $arary = [[['produtId'=>2345,'documentId' => 123332211122]], [['produtId'=>2345,'documentId' => 123332211122]]];
        $jsonData = json_encode($arary);
        $jsonArray = json_decode($jsonData, true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->message->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($arary);
            $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('documentLifeExtendApiCall')
            ->willReturn(true);

       $this->assertNull($this->DocRefSubscriber->processMessageExtandExpire($this->message));
    }

    public function testProcessMessageExtandExpireWithSignleItem()
    {
        $arary = [['produtId'=>2345,'documentId' => 123332211122]];

        $jsonData = json_encode($arary);
        $jsonArray = json_decode($jsonData, true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->message->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($arary);
            $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('documentLifeExtendApiCall')
            ->willReturn(true);

       $this->assertNull($this->DocRefSubscriber->processMessageExtandExpire($this->message));
    }

    public function testProcessMessageExtandExpireWithSignleItemToggleOff()
    {
        $arary = [['produtId'=>2345,'documentId' => 123332211122]];

        $jsonData = json_encode($arary);
        $jsonArray = json_decode($jsonData, true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->message->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($arary);
            $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('documentLifeExtendApiCall')
            ->willReturn(true);

       $this->assertNull($this->DocRefSubscriber->processMessageExtandExpire($this->message));
    }

    public function testProcessMessageExtandExpireToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->DocRefSubscriber->processMessageExtandExpire($this->message);
       $this->assertNull($this->DocRefSubscriber->processMessageExtandExpire($this->message));
    }

    public function testProcessMessageExtandExpireWithException()
    {
       
        $arary= [['produtId'=>2345,'documentId' => 123332211122], ['produtId'=>2345,'documentId' => 123332211122]];
        $jsonData = json_encode($arary);
        $jsonArray = json_decode($jsonData, true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->message->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);
            $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('documentLifeExtendApiCall')
            ->willThrowException(new NoSuchEntityException());

       $this->assertNull($this->DocRefSubscriber->processMessageExtandExpire($this->message));
    }

    public function testprocessMessageAddRef()
    {
       
        $arary[] = ['apiRequestData'=>['data'=>1],'setupUrl' => 'fedex\v2\addrefrance', 'method' => 'POST'];
        $jsonData = json_encode($arary);
        $jsonArray = json_decode($jsonData, true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->message->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);
            $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('curlCall')
            ->willReturn(true);

       $this->assertNull($this->DocRefSubscriber->processMessageAddRef($this->message));
    }

    public function testprocessMessageAddRefToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->DocRefSubscriber->processMessageExtandExpire($this->message);
       $this->assertNull($this->DocRefSubscriber->processMessageAddRef($this->message));
    }

    public function testprocessMessageDeleteRef()
    {
       
        $arary[] = ['api_request_data'=>['data'=>1],'setupURL' => 'fedex\v2\addrefrance', 'method' => 'POST'];
        $jsonData = json_encode($arary);
        $jsonArray = json_decode($jsonData, true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->message->expects($this->any())
            ->method('getMessage')
            ->willReturn($jsonData);
        $this->serializerJson->expects($this->any())
            ->method('unserialize')
            ->willReturn($jsonArray);
            $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('curlCall')
            ->willReturn(true);

       $this->assertNull($this->DocRefSubscriber->processMessageDeleteRef($this->message));
    }

    public function testprocessMessageDeleteRefToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->DocRefSubscriber->processMessageExtandExpire($this->message);
       $this->assertNull($this->DocRefSubscriber->processMessageDeleteRef($this->message));
    }

    public function testprocessMessageMetaData()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        
       $this->assertNull($this->DocRefSubscriber->processMessageMetaData($this->message));
    }
   
}
