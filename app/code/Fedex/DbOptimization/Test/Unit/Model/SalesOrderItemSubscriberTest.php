<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\DbOptimization\Test\Unit\Model;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\DbOptimization\Model\SalesOrderItemSubscriber;
use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;

class SalesOrderItemSubscriberTest extends TestCase
{

    protected $soiMessageInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $soiSubscriberMock;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|MockObject
     */
    protected $jsonMock;

    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var \Psr\Log\LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Sales\Model\Order\Item|MockObject
     */
    protected $soiModelMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
         $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->soiModelMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->setMethods(['load', 'setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->soiMessageInterfaceMock = $this->getMockBuilder(SalesOrderItemMessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->soiSubscriberMock = $this->objectManager->getObject(
            SalesOrderItemSubscriber::class,
            [
                'serializerJson' => $this->jsonMock,
                'loggerInterface' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'salesOrderItem' => $this->soiModelMock
            ]
        );
    }

    /**
     * Test Case for processMessage
     *
     * @return null
     */
    public function testProcessMessage()
    {
        $dummyData = [['item_id'=> '1', 'created_at' =>'2021-06-01', 'product_options' => []]];
        $dummyDataSerialize = json_encode($dummyData);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->soiMessageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($dummyDataSerialize);
        $this->jsonMock->expects($this->any())->method('unserialize')->willReturn($dummyData);

        $this->soiModelMock->expects($this->any())->method('load')->willReturnSelf();
        $this->soiModelMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->soiModelMock->expects($this->any())->method('save')->willReturnSelf();

        $this->assertEquals(null, $this->soiSubscriberMock->processMessage($this->soiMessageInterfaceMock));
    }

    /**
     * Test Case for processMessage with Toggle OFF
     *
     * @return null
     */
    public function testProcessMessageToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertEquals(null, $this->soiSubscriberMock->processMessage($this->soiMessageInterfaceMock));
    }

    /**
     * Test Case for processMessage with Exception
     *
     * @return null
     */
    public function testProcessMessageWithException()
    {
        $exception = new \Exception();
        $dummyIncorrectData = 'INCORRECT DATA';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->soiMessageInterfaceMock->expects($this->any())
                ->method('getMessage')->willReturn($dummyIncorrectData);
        $this->jsonMock->expects($this->any())->method('unserialize')->with($dummyIncorrectData)
                ->willThrowException($exception);
        $this->assertEquals(null, $this->soiSubscriberMock->processMessage($this->soiMessageInterfaceMock));
    }

    /**
     * Test Case for processMessage with Exception2
     *
     * @return null
     */
    public function testProcessMessageWithExceptionInLoad()
    {
        $exception = new \Exception();
        $dummyData = [['item_id'=> '1', 'created_at' =>'2021-06-01', 'product_options' => []]];
        $dummyDataSerialize = json_encode($dummyData);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->soiMessageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($dummyDataSerialize);
        $this->jsonMock->expects($this->any())->method('unserialize')->willReturn($dummyData);

        $this->soiModelMock->expects($this->any())->method('load')->with('TEST')->willThrowException($exception);

        $this->assertEquals(null, $this->soiSubscriberMock->processMessage($this->soiMessageInterfaceMock));
    }
}
