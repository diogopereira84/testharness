<?php

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\DataProvider;

use Fedex\OrderGraphQl\Model\Resolver\DataProvider\ShipmentStatusLabel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @covers \Fedex\OrderGraphQl\Model\Resolver\DataProvider\ShipmentStatusLabel
 */
class ShipmentStatusLabelTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfigMock;
    protected $jsonHelper;
    private ResourceConnection|MockObject $resourceConnectionMock;
    private AdapterInterface|MockObject $connectionMock;
    private Select|MockObject $selectMock;
    private ObjectManager|ObjectManagerInterface $objectManager;
    private ShipmentStatusLabel $testObject;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->createMock(Select::class);
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonHelper = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testObject = $this->objectManager->getObject(
            ShipmentStatusLabel::class,
            [
                'resourceConnection' => $this->resourceConnectionMock,
                'scopeConfig' => $this->scopeConfigMock,
                'jsonHelper' => $this->jsonHelper,
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetShipmentLabel(): void
    {
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturn([[
                'id' => 2,
                'value' => 1,
                'label' => 'processing',
                'key' => 'processing'
            ]]);
        $this->jsonHelper->expects($this->once())
            ->method('unserialize')
            ->willReturn(json_decode('{"item1":{"magento_status":"processing","mapped_status":"CONFIRMED"},"item2":{"magento_status":"confirmed","mapped_status":"CONFIRMED"}}', true));


        $this->resourceConnectionMock->expects($this->any())
            ->method('getTableName')
            ->willReturn('shipment_status');

        $this->resourceConnectionMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->assertEquals('CONFIRMED', $this->testObject->getShipmentLabel(1));
    }
}
