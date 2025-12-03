<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Model\Subscriber
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SubscriberTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $subscriber;
    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \Fedex\Shipment\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $helperData;

    /**
     * @var \Fedex\Shipment\Helper\ShipmentEmail\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shipmentHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTime;

    /**
     * @var \Fedex\Shipment\Api\MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageInterface;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->shipmentHelper = $this->createMock(\Fedex\Shipment\Helper\ShipmentEmail::class);
        $this->helperData = $this->getMockBuilder(\Fedex\Shipment\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrderIdByShipmentId','setShipmentEmail'])
            ->getMock();
        $this->messageInterface = $this->getMockForAbstractClass(\Fedex\Shipment\Api\MessageInterface::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->subscriber = $this->objectManagerHelper->getObject(
            \Fedex\Shipment\Model\Subscriber::class,
            [
                'logger' => $this->logger,
                'dateTime' => $this->dateTime,
                'shipmentHelper' => $this->shipmentHelper,
                'data' => $this->helperData
            ]
        );
    }

    public function testProcessMessage()
    {
        $string = '{"transactionId":null,"output":null}';
        $this->helperData = $this->getMockBuilder(\Fedex\Shipment\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById'])
            ->getMock();
        $this->messageInterface->expects($this->any())->method('getMessage')->willReturn("18");
        $this->dateTime->expects($this->any())->method('date')->willReturn('11:07:2021');
        $this->helperData = $this->getMockBuilder(\Fedex\Shipment\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrderIdByShipmentId','setShipmentEmail'])
            ->getMock();
        $this->helperData->method('getOrderIdByShipmentId')->willReturn("2");
        $this->helperData->expects($this->any())->method('setShipmentEmail')->willReturn(['message' => 'success']);
        $this->shipmentHelper->expects($this->any())->method('sendEmail')->willReturn($string);
        $this->testIsJson();
        $this->assertEquals(true, $this->subscriber->processMessage($this->messageInterface));
    }

    public function testIsJson()
    {
        $string = '{"transactionId":null,"output":null}';
        $expectedResult = true;
        $this->assertEquals($expectedResult, $this->subscriber->isJson($string, false));
    }

    public function testIsJsonWithNull()
    {
        $string = '';
        $expectedResult = false;
        $this->assertEquals($expectedResult, $this->subscriber->isJson($string, false));
    }

    public function testIsJsonWithTrue()
    {
        $string = '{"transactionId":null,"output":null}';
        $this->assertEquals(json_decode($string), $this->subscriber->isJson($string, true));
    }

    /**
     * Test ProcessMessage function with exception
     */
    public function testProcessMessageWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->dateTime->expects($this->any())->method('date')->willThrowException($exception);
        $this->assertEquals(true, $this->subscriber->processMessage($this->messageInterface));
    }
}
