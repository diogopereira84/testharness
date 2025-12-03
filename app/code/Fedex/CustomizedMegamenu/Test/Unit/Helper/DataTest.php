<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CusomizedMegamenu\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CustomizedMegamenu\Helper\Data;
use Magento\Store\Model\Store;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\App\ResourceConnection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceConnectionMock;
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $data;

    /**
     * @var Store|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeMock;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    // @codingStandardsIgnoreEnd

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue', 'getToggleConfig'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getId', 'getWebsiteId', 'getName', 'getBaseUrl', 'isActive', 'getGroupId'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTableName', 'getConnection', 'fetchAll'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'toggleConfig' => $this->toggleConfig,
                'storeManager' => $this->storeManagerMock,
                'resourceConnection' => $this->resourceConnectionMock,
            ]
        );
    }

    /**
     * Get store identifier
     *
     * @return  StoreId
     */
    public function testGetStoreId()
    {
        $storeId = 1;
        
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );

        $this->assertEquals($storeId, $this->data->getStoreId());
    }
    
    /**
     *
     * @return WebsiteId
     */
    public function testGetWebsiteId()
    {
        $storeWebsiteId = '1';
        $this->storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($storeWebsiteId);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);


        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );

        $this->assertEquals($storeWebsiteId, $this->data->getWebsiteId());
        
    }
    
    /**
     *
     * @return StoreCode
     */
    public function testGetStoreCode()
    {
        $storeCode = 'default';
        $this->storeMock->expects($this->once())
            ->method('getCode')
            ->willReturn($storeCode);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);


        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );

        $this->assertEquals($storeCode, $this->data->getStoreCode());

    }
    
    /**
     *
     * @return StoreName
     */
    public function testGetStoreName()
    {
        $storeName = 'Default Store View';
        $this->storeMock->expects($this->once())
            ->method('getName')
            ->willReturn($storeName);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);


        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );

        $this->assertEquals($storeName, $this->data->getStoreName());
        
    }
    
    /**
     *
     * @return StoreMediaUrl
     */
    public function testGetStoreUrl()
    {
        $storeUrlMediaPath = 'http://magento2/pub/media/';
        $this->storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($storeUrlMediaPath);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);


        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );

        $this->assertEquals($storeUrlMediaPath, $this->data->getStoreUrl());

    }
    
    /**
     *
     * @return StoreStatus
     */
    public function testIsStoreActive()
    {
        $isStoreActive = 0;
        $this->storeMock->expects($this->once())
            ->method('isActive')
            ->willReturn($isStoreActive);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);


        $this->data = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );

        $this->assertEquals($isStoreActive, $this->data->isStoreActive());
    }
}
?>
