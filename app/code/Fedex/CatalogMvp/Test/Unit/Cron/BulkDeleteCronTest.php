<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Cron\BulkDeleteCron;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Product;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CatalogMvp\Model\BulkDeleteMessage;
use Magento\Framework\Serialize\Serializer\Json;

class BulkDeleteCronTest extends TestCase
{
    protected $toggleConfig;
    protected $loggerInterface;
    protected $productCollectionFactory;
    protected $productCollection;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisher;
    /**
     * @var (\Fedex\CatalogMvp\Model\BulkDeleteMessage & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $message;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerJson;
    protected $productMock;
    protected $bulkDeleteCron;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

            $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
            
            $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getIterator'])
            ->getMock();

        $this->publisher = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMockForAbstractClass();

        $this->message = $this->getMockBuilder(BulkDeleteMessage::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();

        $this->serializerJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds','getId'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->bulkDeleteCron = $objectManagerHelper->getObject(
            BulkDeleteCron::class,
            [
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'serializerJson' => $this->serializerJson,
                'message' => $this->message,
                'publisher' => $this->publisher,
                'productCollectionFactory' => $this->productCollectionFactory
             ]
        );
    }
    /**
     * @test Execute
     */
    public function testExecute()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();

            $this->productCollectionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
    
            $this->productCollection
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

            $this->productCollection->expects($this->any())
            ->method('getIterator') ->willReturn(new \ArrayIterator([$this->productMock]));

            $this->productMock
            ->expects($this->any())
            ->method('getCategoryIds')
            ->willReturn([]);

            $this->productMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $result = $this->bulkDeleteCron->execute();
        $this->assertNull($result);
    }
}
