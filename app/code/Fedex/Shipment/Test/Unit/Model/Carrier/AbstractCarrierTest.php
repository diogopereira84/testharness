<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Test\Unit\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Tracking\ResultFactory as TrackingResultFactory;
use Magento\Shipping\Model\Tracking\Result;

/**
 * Test class for Fedex\Shipment\Model\Carrier\FedexTracker
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class AbstractCarrierTest extends TestCase
{
    /**
     * @var scopeConfigInterface|MockObject
     */
    protected $scopeConfigInterface;

    /**
     * @var errorFactory|MockObject
     */
    protected $errorFactory;

    /**
     * @var loggerInterface|MockObject
     */
    protected $loggerInterface;

    /**
     * @var resultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var statusFactory|MockObject
     */
    protected $statusFactory;

    /**
     * @var trackingResultFactory|MockObject
     */
    protected $trackingResultFactory;

    /**
     * @var carrier|MockObject
     */
    protected $carrier;

    /**
     * @var rateRequest|MockObject
     */
    protected $rateRequest;

    /**
     * @var AbstractCarrier|MockObject
     */
    protected $abstractCarrier;

    /**
     * @var trackStatusFactory|MockObject
     */
    protected $trackStatusFactory;

    /** @var ObjectManagerHelper |MockObject */
    protected $objectManagerHelper;
    private Result|MockObject $trackingResultFactoryn;

    protected function setUp(): void
    {
        $this->scopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $this->errorFactory = $this->createMock(ErrorFactory::class);
        $this->loggerInterface = $this->createMock(LoggerInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->statusFactory = $this->createMock(StatusFactory::class);
        $this->trackingResultFactory = $this->createMock(TrackingResultFactory::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->carrier = $this->objectManagerHelper->getObject(
            AbstractCarrier::class,
            [
                'rateResultFactory' => $this->resultFactory,
                'trackStatusFactory' => $this->statusFactory,
                'trackFactory' => $this->trackingResultFactory
            ]
        );
    }

    /**
     * Test testIsTrackingAvailable method.
     */
    public function testIsTrackingAvailable()
    {
        $expectedOptions = true;
        $result = $this->carrier->isTrackingAvailable();
        $this->assertEquals($expectedOptions, $result);
    }

    /**
     * Test testCollectRates method.
     */
    public function testCollectRates()
    {
        $this->rateRequest = $this->createMock(RateRequest::class);
        $expectedOptions = false;
        $result = $this->carrier->collectRates($this->rateRequest);
        $this->assertEquals($expectedOptions, $result);
    }

    /**
     * Test testGetTrackingInfo method.
     */
    public function testGetTrackingInfo()
    {
        $tracking = "794618958632";
        $this->testGetCgiTracking();
        $this->assertNotNull($this->carrier->getTrackingInfo([$tracking], null));
    }

    /**
     * Test testGetTrackingInfo method.
     */
    public function testGetTrackingInfoWithStringOutput()
    {
        $tracking = "794618958632";
        $this->abstractCarrier = $this->getMockBuilder(AbstractCarrier::class)
                ->disableOriginalConstructor()
                ->setMethods(['getCgiTracking'])
                ->getMock();
        $this->abstractCarrier->expects($this->any())->method('getCgiTracking')->willReturn([$tracking]);
        $values = [
                'carrierTitle' => 'Fedex Office',
                'tracking' => '794618958632',
                'popup' => '1',
                'url' => 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=#POSTCODE#'
            ];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->statusFactory->expects($this->once())
        ->method('create')->willReturn($varienObject);
        $this->trackingResultFactoryn = $this->getMockBuilder(Result::class)
                ->disableOriginalConstructor()
                ->setMethods(['append'])
                ->getMock();
        $this->trackingResultFactory->expects($this->once())
        ->method('create')->willReturn($this->trackingResultFactoryn);
        $this->trackingResultFactoryn->method('append')->willReturn('Test');
        $this->assertEquals(null, $this->carrier->getTrackingInfo([$tracking], null));
    }

    /**
     * Test testGetTracking method.
     */
    public function testGetTracking()
    {
        $tracking = "794618958632";
        $this->abstractCarrier = $this->getMockBuilder(AbstractCarrier::class)
                ->disableOriginalConstructor()
                ->setMethods(['getCgiTracking'])
                ->getMock();
        $this->abstractCarrier->expects($this->any())->method('getCgiTracking')->willReturn([$tracking]);
        $values = [
                'carrierTitle' => 'Fedex Office',
                'tracking' => '794618958632',
                'popup' => '1',
                'url' => 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=#POSTCODE#'
            ];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->statusFactory->expects($this->once())
        ->method('create')->willReturn($varienObject);
        $this->trackingResultFactoryn = $this->getMockBuilder(Result::class)
                ->disableOriginalConstructor()
                ->setMethods(['append'])
                ->getMock();
        $this->trackingResultFactory->expects($this->once())
        ->method('create')->willReturn($this->trackingResultFactoryn);
        $this->trackingResultFactoryn->method('append')->willReturn($varienObject);
        $this->assertNotNull($this->carrier->getTracking([$tracking], '12121'));
    }

    /**
     * Test testGetTrackingWithoutArray method.
     */
    public function testGetTrackingWithoutArray()
    {
        $tracking = "794618958632";
        $this->abstractCarrier = $this->getMockBuilder(AbstractCarrier::class)
                ->disableOriginalConstructor()
                ->setMethods(['getCgiTracking'])
                ->getMock();
        $this->abstractCarrier->expects($this->any())->method('getCgiTracking')->willReturn([$tracking]);
        $values = [
                'carrierTitle' => 'Fedex Office',
                'tracking' => '794618958632',
                'popup' => '1',
                'url' => 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=#TRACKNUM#'
            ];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->statusFactory->expects($this->once())
        ->method('create')->willReturn($varienObject);
        $this->trackingResultFactoryn = $this->getMockBuilder(Result::class)
                ->disableOriginalConstructor()
                ->setMethods(['append'])
                ->getMock();
        $this->trackingResultFactory->expects($this->once())
        ->method('create')->willReturn($this->trackingResultFactoryn);
        $this->trackingResultFactoryn->method('append')->willReturn($varienObject);
        $this->assertNotNull($this->carrier->getTracking($tracking, null));
    }

    /**
     * Test testGetCgiTracking method.
     */
    public function testGetCgiTracking()
    {
        $tracking = "794618958632";
        $values = [
                'carrierTitle' => 'Fedex Office',
                'tracking' => '794618958632',
                'popup' => '1',
                'url' => 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=#TRACKNUM#'
            ];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->statusFactory->expects($this->any())
        ->method('create')->willReturn($varienObject);
        $this->trackingResultFactoryn = $this->getMockBuilder(\Magento\Shipping\Model\Tracking\Result::class)
                ->disableOriginalConstructor()
                ->setMethods(['append'])
                ->getMock();
        $this->trackingResultFactory->expects($this->any())
        ->method('create')->willReturn($this->trackingResultFactoryn);
        $this->trackingResultFactoryn->method('append')->willReturn($varienObject);
        $this->assertNull($this->carrier->getCgiTracking([$tracking], null));
    }

    /**
     * Test testGetCgiTracking method.
     */
    public function testGetCgiTrackingWithNull()
    {
        $tracking = "794618958632";
        $values = [
                'carrierTitle' => 'Fedex Office',
                'tracking' => '794618958632',
                'popup' => '1',
                'url' => 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=#TRACKNUM#'
            ];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($values);
        $this->statusFactory->expects($this->any())
        ->method('create')->willReturn($varienObject);
        $this->trackingResultFactoryn = $this->getMockBuilder(\Magento\Shipping\Model\Tracking\Result::class)
                ->disableOriginalConstructor()
                ->setMethods(['append'])
                ->getMock();
        $this->trackingResultFactory->expects($this->any())
        ->method('create')->willReturn($this->trackingResultFactoryn);
        $this->trackingResultFactoryn->method('append')->willReturn($varienObject);
        $this->assertNull($this->carrier->getCgiTracking([$tracking], 'test'));
    }
}
