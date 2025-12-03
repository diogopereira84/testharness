<?php

namespace Fedex\CatalogMvp\Model\Test\Unit;

use Fedex\CatalogMvp\Api\CatalogMvpItemEnableMessageInterface;
use Fedex\CatalogMvp\Api\CatalogMvpItemEnableSubscriberInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Model\CatalogMvpItemEnableSubscriber;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\ProductRepository;

class CatalogMvpItemEnableSubscriberTest extends TestCase
{
    protected $toggleConfig;
    protected $message;
    protected $loggerInterface;
    protected $productFactory;
    protected $product;
    protected $serializerJson;
    protected $catalogMvpItemEnableSubscriber;
    protected $productRepositoryMock;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->message = $this->getMockBuilder(CatalogMvpItemEnableMessageInterface::class)
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
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','save'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvpItemEnableSubscriber = $objectManagerHelper->getObject(
            CatalogMvpItemEnableSubscriber::class,
            [
                'serializerJson' => $this->serializerJson,
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'productFactory' => $this->productFactory,
                'productRepository'=>$this->productRepositoryMock
            ]
        );
    }
    public function testExecute()
    {
        $arary = [];
        $arary[] = ['entity_id'=>23];
        $arary[] = ['entity_id'=>234];
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
        $this->productFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->product->expects($this->any())
            ->method('setStatus')
            ->willReturnSelf();
        $this->product->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->product); 
        $result = $this->catalogMvpItemEnableSubscriber->processMessage($this->message);
        $this->assertEquals(null, $result);
    }
    public function testExecuteWithToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $result = $this->catalogMvpItemEnableSubscriber->processMessage($this->message);
        $this->assertEquals(null, $result);
    }
    public function testExecuteWithException()
    {
        $arary = [];
        $arary[] = ['entity_id'=>23];
        $arary[] = ['entity_id'=>234];
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
            ->willThrowException(new \Exception());
        
        $result = $this->catalogMvpItemEnableSubscriber->processMessage($this->message);
        $this->assertEquals(null, $result);
    }
    public function testExecuteWithProductException()
    {
        $arary = [];
        $arary[] = ['entity_id'=>23];
        $arary[] = ['entity_id'=>234];
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
        $this->productFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('load')
            ->willThrowException(new \Exception());
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->product);     
        $result = $this->catalogMvpItemEnableSubscriber->processMessage($this->message);
        $this->assertEquals(null, $result);
    }
}