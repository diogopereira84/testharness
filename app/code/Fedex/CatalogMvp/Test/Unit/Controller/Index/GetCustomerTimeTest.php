<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use Exception;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Fedex\CatalogMvp\Controller\Index\GetCustomerTime;
use Magento\Framework\App\RequestInterface;

/**
 * Class SearchCategoryByNameTest
 *
 */
class GetCustomerTimeTest extends TestCase
{
    protected $requestMock;
    /**
     * @var (\Magento\Framework\Controller\ResultFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultFactoryMock;
    /**
     * @var (\Magento\Framework\Controller\Result\Raw & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultMock;
    protected $GetCustomerTime;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    protected function setUp(): void
    {

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
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

        $this->resultMock = $this->createMock(Raw::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->GetCustomerTime = $objectManagerHelper->getObject(
            GetCustomerTime::class,
            [
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'context' => $this->contextMock,
                'resultFactory' => $this->resultFactoryMock,
                'request' => $this->requestMock
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
            'productStartDate' => '2023-08-08 12:00:00',
            'productEndDate' => '2023-08-08 13:00:00'
        ];
        $this->requestMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJsonFactoryMock);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->assertEquals($this->resultJsonFactoryMock, $this->GetCustomerTime->execute());
    }
    /**
     * @test testExecute
     */
    public function testExecuteElse()
    {
        $postData = [
            'custimezone' => 'America/Los_Angeles',
            'productStartDate' => '2023-08-08 12:00:00'
        ];
        $this->requestMock->expects($this->any())
            ->method('getParams')->willReturn($postData);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJsonFactoryMock);
        $this->resultJsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->assertEquals($this->resultJsonFactoryMock, $this->GetCustomerTime->execute());
    }
}
