<?php
declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Controller\Adminhtml\Categories;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CustomerGroup\Controller\Adminhtml\Categories\Fetch;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class FetchTest extends TestCase
{
    protected $requestMock;
    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $resourceConnectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    const MAGENTOCATALOG_PERMISSION_TABLE = 'magento_catalogpermissions';
    /**
     * @var Fetch
     */
    private $controller;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactoryMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
            $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName','select'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();
        $this->controller = $this->objectManager->getObject(
            Fetch::class,
            [
                'resourceConnection' =>  $this->resourceConnectionMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'logger' => $this->loggerInterfaceMock
            ]
        );
    }

    public function testExecute(): void
    {
        $data = ['parent_id' => 1];
        $categoryId = null;
        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with('data')->willReturn($data);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchOne')->willReturn($categoryId);

        $resultJsonMock->expects($this->any())
            ->method('setData')
            ->with(
                [
                    'categoryId' => $categoryId,
                ]
            );

        $this->resultJsonFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($resultJsonMock);

        $reflection = new \ReflectionClass(Fetch::class);
        $getData = $reflection->getMethod('execute');
        $getData->setAccessible(true);
        $expectedResult = $getData->invoke($this->controller);
        $this->assertEquals($resultJsonMock, $expectedResult);
    }
    public function testExecuteWithException(): void
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $data = ['parent_id' => 1];
        $categoryId = null;
        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with('data')->willReturn($data);
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn(self::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willThrowException($exception);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchOne')->willThrowException($exception);

        $resultJsonMock->expects($this->any())
            ->method('setData')
            ->with(
                [
                    'categoryId' => $categoryId,
                ]
            );

        $this->resultJsonFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($resultJsonMock);

        $reflection = new \ReflectionClass(Fetch::class);
        $getData = $reflection->getMethod('execute');
        $getData->setAccessible(true);
        $expectedResult = $getData->invoke($this->controller);
        $this->assertEquals($resultJsonMock, $expectedResult);
    }
}
