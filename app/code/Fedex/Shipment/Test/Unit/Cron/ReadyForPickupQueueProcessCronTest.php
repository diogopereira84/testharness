<?php

namespace Fedex\Shipment\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Test class for Fedex\Shipment\Cron\SendEmail
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ReadyForPickupQueueProcessCronTest extends \PHPUnit\Framework\TestCase
{
    protected $shipmentResult;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $readyForPickupQueueProcessCron;
    /**
     * @var objectManagerHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shipmentRepositoryInterface;

    /**
     * @var \Fedex\Shipment\Model\ShipmentFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shipmentFactory;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $publisherInterface;

    /**
     * @var \Fedex\Shipment\Api\MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageInterface;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTime;

    /**
     * @var \Fedex\Shipment\Helper\ShipmentEmail|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shipmentHelper;

    /**
     * @var \Fedex\Shipment\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $helperData;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->shipmentRepositoryInterface = $this->getMockForAbstractClass(
            \Magento\Sales\Api\ShipmentRepositoryInterface::class,
            ['getList'],
            '',
            false
        );

        $this->searchCriteriaBuilder = $this->createPartialMock(
            \Magento\Framework\Api\SearchCriteriaBuilder::class,
            ['addFilter', 'create']
        );

        $this->shipmentResult = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentSearchResultInterface::class)
            ->setMethods(['getPickupAllowedUntilDate', 'getCreatedAt', 'getEntityId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shipmentFactory = $this->createMock(\Fedex\Shipment\Model\ShipmentFactory::class);
        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->shipmentHelper = $this->createMock(\Fedex\Shipment\Helper\ShipmentEmail::class);
        $this->helperData = $this->getMockBuilder(\Fedex\Shipment\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentStatus'])
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->readyForPickupQueueProcessCron = $this->objectManagerHelper->getObject(
            \Fedex\Shipment\Cron\ReadyForPickupQueueProcessCron::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'shipmentRepository' => $this->shipmentRepositoryInterface,
                'shipmentFactory' => $this->shipmentFactory,
                'dateTime' => $this->dateTime,
                'shipmentHelper' => $this->shipmentHelper,
                'data' => $this->helperData,
                'logger' => $this->loggerMock
            ]
        );
    }

    public function testExecute()
    {
        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);

        $this->helperData->expects($this->any())->method('getShipmentStatus')->willReturn("2");
        $this->dateTime->expects($this->any())->method('date')->willReturn('11:07:2021');
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->shipmentRepositoryInterface->expects($this->any())
            ->method('getList')
            ->willReturn($this->shipmentResult);
        $this->shipmentResult->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->shipmentResult]);
        $this->shipmentResult->expects($this->any())
            ->method('getPickupAllowedUntilDate')
            ->willReturn('12.06.2020');
        $this->shipmentResult->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('11.03.2019');

        $this->assertNotNull($this->readyForPickupQueueProcessCron->execute());
    }

    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $localizedException = new LocalizedException($phrase);
        $this->searchCriteriaBuilder->expects($this->any())
        ->method('addFilter')->willThrowException($localizedException);
        $this->assertNotNull($this->readyForPickupQueueProcessCron->execute());
    }
}
