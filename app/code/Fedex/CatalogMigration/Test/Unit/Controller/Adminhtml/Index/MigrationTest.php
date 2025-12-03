<?php

namespace Fedex\CatalogMigration\Test\Unit\Controller\Adminhtml\Index;

use Fedex\CatalogMigration\Controller\Adminhtml\Index\Migration;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    /**
     * @var Migration
     */
    private $controller;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactoryMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var File|MockObject
     */
    private $driverInterfaceMock;

    /**
     * @var CatalogMigrationHelper|MockObject
     */
    private $catalogMigrationHelperMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    protected $resource;

    protected function setUp(): void
    {
        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->driverInterfaceMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMigrationHelperMock = $this->getMockBuilder(CatalogMigrationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new Migration(
            $this->contextMock,
            $this->resultJsonFactoryMock,
            $this->driverInterfaceMock,
            $this->catalogMigrationHelperMock,
            $this->requestMock
        );
    }

    public function testExecute()
    {
        // Set up mock data
        $fileMock = ['tmp_name' => 'sample_file.csv'];
        $compId = 123;
        $sharedCatId = 456;
        $extUrl = 'http://example.com';
        $datas = [['col1', 'col2']];

        $this->contextMock
            ->method('getRequest')
            ->willReturn($this->requestMock);

        // Configure mocks
        $this->requestMock->expects($this->once())
            ->method('getFiles')
            ->with('file')
            ->willReturn($fileMock);

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(['comp_id'], ['shared_cat_id'], ['ext_url'])
            ->willReturnOnConsecutiveCalls($compId, $sharedCatId, $extUrl);

        $this->driverInterfaceMock->expects($this->once())
            ->method('fileOpen')
            ->with($fileMock['tmp_name'], 'r')
            ->willReturn('resource');
            
        $this->driverInterfaceMock->expects($this->exactly(2)) 
            ->method('fileGetCsv')
            ->withConsecutive(['resource', 100000], ['resource', 100000])
            ->willReturnOnConsecutiveCalls(['col1', 'col2'], false);

        $this->catalogMigrationHelperMock->expects($this->once())
            ->method('validateSheetData')
            ->with($datas, $compId, $sharedCatId, $extUrl)
            ->willReturn(['status' => 'success', 'message' => 'Validation passed']);

        $jsonResultMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($jsonResultMock);

        $jsonResultMock->expects($this->once())
            ->method('setData')
            ->with(['status' => 'success', 'message' => 'Validation passed'])
            ->willReturnSelf();

        // Execute the controller action
        $result = $this->controller->execute();

        // Assertions
        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals($jsonResultMock, $result);
    }
}
