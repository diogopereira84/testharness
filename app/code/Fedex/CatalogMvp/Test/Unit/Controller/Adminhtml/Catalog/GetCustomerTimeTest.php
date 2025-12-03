<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\Result\Raw;
use Fedex\CatalogMvp\Controller\Adminhtml\Catalog\GetCustomerTime;

class GetCustomerTimeTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Controller\Result\Raw & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultMock;
    protected $getCustomerTime;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepositoryInterfaceMock;

    /**
     * @var Product|MockObject
     */
    protected $productMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestInterfaceMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();


        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();
        
        $this->productRepositoryInterfaceMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','save','get','delete','getList','deleteById'])
            ->getMock();
        
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->resultMock = $this->createMock(Raw::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->getCustomerTime = $objectManagerHelper->getObject(
            GetCustomerTime::class,
            [
                'resultFactory' => $this->resultFactoryMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'context' => $this->contextMock,
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testExecute()
    {
        $postData = [
            'custimezone' => 'America/Los_Angeles',
            'productId' => '5416'
        ];
        $this->requestMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())
            ->method('getData')->willReturn('2023-08-08 12:00:00');
        $this->productMock->expects($this->any())
            ->method('getData')->willReturn('2023-08-08 13:00:00');
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJsonFactoryMock);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->assertEquals($this->resultJsonFactoryMock, $this->getCustomerTime->execute());
    }
    /**
     * @test testExecute
     */
    public function testExecuteElse()
    {
        $postData = [
            'custimezone' => 'America/Los_Angeles',
            'productId' => '5416'
        ];
        $this->requestMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())
            ->method('getData')->willReturn('2023-08-08 12:00:00');
        $this->productMock->expects($this->any())
            ->method('getData')->willReturn('');
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJsonFactoryMock);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->assertEquals($this->resultJsonFactoryMock, $this->getCustomerTime->execute());
    }
}
