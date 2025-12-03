<?php

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\DataProvider;

use Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderStatusMapping;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @covers \Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderStatusMapping
 */
class OrderStatusMappingTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfigMock;
    protected $jsonHelper;
    private ObjectManager|ObjectManagerInterface $objectManager;
    private OrderStatusMapping $testObject;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonHelper = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testObject = $this->objectManager->getObject(
            OrderStatusMapping::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'jsonHelper' => $this->jsonHelper,
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetMappingKey(): void
    {
        $this->jsonHelper->expects($this->once())
            ->method('unserialize')
            ->willReturn(json_decode('{"item1":{"magento_status":"processing","mapped_status":"CONFIRMED"},"item2":{"magento_status":"confirmed","mapped_status":"CONFIRMED"}}', true));

        $this->assertEquals('CONFIRMED', $this->testObject->getMappingKey('processing'));
    }
}
