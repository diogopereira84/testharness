<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CatalogMvp\Api\CatalogMvpItemDisableMessageInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Cron\ItemDisableCron;
use PHPUnit\Framework\TestCase;

class ItemDisableCronTest extends TestCase
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
     * @var (\Fedex\CatalogMvp\Api\CatalogMvpItemDisableMessageInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $message;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerJson;
    protected $itemDisableCron;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
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
            ->setMethods(['addAttributeToSelect','addAttributeToFilter','getSelect','limit','getData'])
            ->getMock();
        $this->publisher = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMockForAbstractClass();
        $this->message = $this->getMockBuilder(CatalogMvpItemDisableMessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();
        $this->serializerJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->itemDisableCron = $objectManagerHelper->getObject(
            ItemDisableCron::class,
            [
                'serializerJson' => $this->serializerJson,
                'message' => $this->message,
                'publisher' => $this->publisher,
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'productCollectionFactory' => $this->productCollectionFactory
            ]
        );
    }

    public function getProductData()
    {
        $productData = [];
        $productData[] = ['sku'=>'test','name'=>'test','entity_id'=>23];
        return $productData;
    }

    public function testExecute()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('limit')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getData')
            ->willReturn($this->getProductData());
            //$this->serializerJson
        $result = $this->itemDisableCron->execute();
        $this->assertEquals(null, $result);
    }

    
    public function testExecuteWithProductCountZero()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('limit')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getData')
            ->willReturn([]);
            //$this->serializerJson
        $result = $this->itemDisableCron->execute();
        $this->assertEquals(null, $result);
    }
}
