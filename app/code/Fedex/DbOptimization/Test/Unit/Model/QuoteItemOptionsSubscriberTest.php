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
use Fedex\DbOptimization\Model\QuoteItemOptionsSubscriber;
use Magento\Framework\DB\Select;
use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;

class QuoteItemOptionsSubscriberTest extends TestCase
{

    protected $qioMessageInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $qioSubscriberMock;
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
     * @var \Magento\Quote\Model\Quote\Item\Option|MockObject
     */
    protected $qioModelMock;

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

        $this->qioModelMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->setMethods(['load', 'setValue', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->qioMessageInterfaceMock = $this->getMockBuilder(QuoteItemOptionsMessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->qioSubscriberMock = $this->objectManager->getObject(
            QuoteItemOptionsSubscriber::class,
            [
                'serializerJson' => $this->jsonMock,
                'loggerInterface' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'quoteItemOptionModel' => $this->qioModelMock
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
        $dummyData = [['entity_id'=> '1', 'updated_at' =>'2021-06-01', 'option_id' => '1']];
        $dummyDataSerialize = json_encode([['entity_id'=> '1', 'updated_at' =>'2021-06-01', 'option_id' => '1']]);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->qioMessageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($dummyDataSerialize);
        $this->jsonMock->expects($this->any())->method('unserialize')->willReturn($dummyData);

        $this->qioModelMock->expects($this->any())->method('load')->willReturnSelf();
        $this->qioModelMock->expects($this->any())->method('setValue')->willReturnSelf();
        $this->qioModelMock->expects($this->any())->method('save')->willReturnSelf();

        $this->assertEquals(null, $this->qioSubscriberMock->processMessage($this->qioMessageInterfaceMock));
    }

    /**
     * Test Case for processMessage with Toggle OFF
     *
     * @return null
     */
    public function testProcessMessageToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertEquals(null, $this->qioSubscriberMock->processMessage($this->qioMessageInterfaceMock));
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
        $this->qioMessageInterfaceMock->expects($this->any())
                ->method('getMessage')->willReturn($dummyIncorrectData);
        $this->jsonMock->expects($this->any())->method('unserialize')->with($dummyIncorrectData)
                ->willThrowException($exception);
        $this->assertEquals(null, $this->qioSubscriberMock->processMessage($this->qioMessageInterfaceMock));
    }

    /**
     * Test Case for processMessage with Exception2
     *
     * @return null
     */
    public function testProcessMessageWithExceptionInLoad()
    {
        $exception = new \Exception();
        $dummyData = [['entity_id'=> '1', 'updated_at' =>'2021-06-01', 'option_id' => '1']];
        $dummyDataSerialize = json_encode([['entity_id'=> '1', 'updated_at' =>'2021-06-01', 'option_id' => '1']]);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->qioMessageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($dummyDataSerialize);
        $this->jsonMock->expects($this->any())->method('unserialize')->willReturn($dummyData);

        $this->qioModelMock->expects($this->any())->method('load')->with('TEST')->willThrowException($exception);

        $this->assertEquals(null, $this->qioSubscriberMock->processMessage($this->qioMessageInterfaceMock));
    }
}
