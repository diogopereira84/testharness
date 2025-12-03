<?php
namespace Fedex\CustomerGroup\Test\Unit\Plugin\Adminhtml\Group;

use Magento\Customer\Controller\Adminhtml\Group\Save;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Fedex\CustomerGroup\Plugin\Adminhtml\Group\SaveCustomerGroupPlugin;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\DB\Adapter\AdapterInterface;

class SaveCustomerGroupPluginTest extends TestCase
{
    protected $connectionMock;
    const TABLE_NAME = 'customer_group';
    /**
     * @var SaveCustomerGroupPlugin
     */
    protected $plugin;

    /**
     * @var MockObject|Save
     */
    protected $saveControllerMock;

    /**
     * @var MockObject|ResourceConnection
     */
    protected $resourceConnectionMock;
    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName','update'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->saveControllerMock = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new SaveCustomerGroupPlugin($this->resourceConnectionMock);
    }
    public function testAfterExecute()
    {
        $parentGroupId = 123;
        $customerGroupCode = 'Test';
        $requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->method('getParam')->willReturnMap([
            ['parent_group_code', null, $parentGroupId],
            ['code', null, $customerGroupCode],
        ]);

        $this->saveControllerMock->method('getRequest')->willReturn($requestMock);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->any())
            ->method('getTableName')
            ->willReturn('customer_group');

        $this->connectionMock->expects($this->any())
            ->method('update')
            ->with(
                'customer_group',
                ['parent_group_id' => $parentGroupId],
                ['customer_group_code = ?' => $customerGroupCode]
            );
        $result = $this->plugin->afterExecute($this->saveControllerMock, []);
        $this->assertEquals([], $result);
    }
}
