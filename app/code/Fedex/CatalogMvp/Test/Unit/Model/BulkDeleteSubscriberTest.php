<?php

namespace Fedex\CatalogMvp\Model\Test\Unit;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Model\BulkDeleteSubscriber;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Api\BulkDeleteMessageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CatalogMvp\Controller\Index\BulkDelete;
use Magento\Framework\Registry;

class BulkDeleteSubscriberTest extends TestCase
{
    protected $toggleConfig;
    protected $message;
    protected $loggerInterface;
    protected $serializerJson;
    protected $bulkDeleteMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $bulkDeleteSubscriber;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->message = $this->getMockBuilder(BulkDeleteMessageInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->serializerJson = $this->getMockBuilder(Json::class)
            ->DisableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();

        $this->bulkDeleteMock = $this->getMockBuilder(BulkDelete::class)
            ->DisableOriginalConstructor()
            ->setMethods(['deleteProduct'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->DisableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->bulkDeleteSubscriber = $objectManagerHelper->getObject(
            BulkDeleteSubscriber::class,
            [
                'serializerJson' => $this->serializerJson,
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'bulkDelete'  => $this->bulkDeleteMock,
                'registry'    => $this->registryMock
            ]
        );
    }
    public function testprocessMessageBulkDelete()
    {
       
        $arary[] = ['produtId'=>234];
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

        $this->bulkDeleteMock->expects($this->any())
        ->method('deleteProduct')
        ->willReturn(true);

       $this->assertNull($this->bulkDeleteSubscriber->processMessageBulkDelete($this->message));
    }

    public function testprocessMessageBulkDeleteToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
       $this->assertNull($this->bulkDeleteSubscriber->processMessageBulkDelete($this->message));
    }

    public function testprocessMessageBulkDeleteWithException()
    {
       
        $arary[] = ['produtId'=>234];
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

        $this->bulkDeleteMock->expects($this->any())
        ->method('deleteProduct')
        ->willThrowException(new \Exception());

       $this->assertNull($this->bulkDeleteSubscriber->processMessageBulkDelete($this->message));
    }
    
}
