<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\Rate\Detail\DeliveryLine;

use Exception;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Collection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount\Collection as DeliveryLineDiscountCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\EntityFactory
     * Mock object for the EntityFactory class used in unit tests.
     */
    protected $entityFactoryMock;
    /**
     * @var \Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the DeliveryLineDiscountCollectionFactory used in unit tests.
     */
    protected $deliveryLineDiscountCollectionFactoryMock;
    /**
     * Delivery line type shipping
     */
    private const DELIVERY_LIVE_TYPE_SHIPPING = 'SHIPPING';

    /**
     * Delivery line type packing and handling
     */
    private const DELIVERY_LIVE_TYPE_PACKING_AND_HANDLING = 'PACKING_AND_HANDLING';

    /**
     * @var RateDeliveryLineCollectionInterface
     */
    private RateDeliveryLineCollectionInterface $deliveryLineCollection;

    /**
     * @var RateDeliveryLineInterface
     */
    private RateDeliveryLineInterface $rateDeliveryLineMock;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->rateDeliveryLineMock = $this->getMockForAbstractClass(RateDeliveryLineInterface::class);
        $this->entityFactoryMock->method('create')->willReturn($this->rateDeliveryLineMock);
        $this->deliveryLineDiscountCollectionFactoryMock =
        $this->getMockBuilder(RateDeliveryLineDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineDiscountCollectionFactoryMock
            ->method('create')
            ->willReturn(new DeliveryLineDiscountCollection($this->entityFactoryMock));

        $this->deliveryLineCollection = new Collection($this->entityFactoryMock);
    }

    /**
     * Test method getShippingDeliveryLine with shipping delivery line available
     *
     * @return void
     * @throws Exception
     */
    public function testGetShippingDeliveryLineAvailable(): void
    {
        $deliveryLine = new DeliveryLine($this->deliveryLineDiscountCollectionFactoryMock);
        $deliveryLine->setDeliveryLineType(self::DELIVERY_LIVE_TYPE_SHIPPING);
        $this->deliveryLineCollection->addItem($deliveryLine);
        $this->assertEquals(
            self::DELIVERY_LIVE_TYPE_SHIPPING,
            $this->deliveryLineCollection->getShippingDeliveryLine()->getDeliveryLineType()
        );
    }

    /**
     * Test method getShippingDeliveryLine with shipping delivery line unavailable
     *
     * @return void
     * @throws Exception
     */
    public function testGetShippingDeliveryLineUnavailable(): void
    {
        $this->assertEquals(
            '',
            $this->deliveryLineCollection->getShippingDeliveryLine()->getDeliveryLineType()
        );
    }

    /**
     * Test method getPackingAndHandlingDeliveryLine
     * with packing and handling delivery line available
     *
     * @return void
     * @throws Exception
     */
    public function testGetPackingAndHandlingDeliveryLineAvailable(): void
    {
        $deliveryLine = new DeliveryLine($this->deliveryLineDiscountCollectionFactoryMock);
        $deliveryLine->setDeliveryLineType(
            self::DELIVERY_LIVE_TYPE_PACKING_AND_HANDLING
        );
        $this->deliveryLineCollection->addItem($deliveryLine);
        $this->assertEquals(
            self::DELIVERY_LIVE_TYPE_PACKING_AND_HANDLING,
            $this->deliveryLineCollection->getPackingAndHandlingDeliveryLine()
                ->getDeliveryLineType()
        );
    }

    /**
     * Test method getPackingAndHandlingDeliveryLine
     * with packing and handling delivery line unavailable
     *
     * @return void
     * @throws Exception
     */
    public function testGetPackingAndHandlingDeliveryLineUnavailable(): void
    {
        $this->assertEquals(
            '',
            $this->deliveryLineCollection->getPackingAndHandlingDeliveryLine()
                ->getDeliveryLineType()
        );
    }

    /**
     * Test method hasShippingDeliveryLineDiscounts
     * with delivery line discount unavailable
     *
     * @return void
     * @throws Exception
     */
    public function testHasShippingDeliveryLineDiscountsTrue(): void
    {
        $deliveryLineDiscount = new Discount();
        $deliveryLine = new DeliveryLine($this->deliveryLineDiscountCollectionFactoryMock);
        $deliveryLineDiscount->setType('COUPON');
        $deliveryLineDiscount->setAmount('$(10.3)');
        $deliveryLine->getDeliveryLineDiscounts()->addItem($deliveryLineDiscount);
        $deliveryLine->setDeliveryLineType(self::DELIVERY_LIVE_TYPE_SHIPPING);
        $this->deliveryLineCollection->addItem($deliveryLine);

        $this->assertTrue(
            $this->deliveryLineCollection->hasShippingDeliveryLineDiscounts()
        );
    }

    /**
     * Test method hasShippingDeliveryLineDiscounts
     * with none delivery line discount
     *
     * @return void
     * @throws Exception
     */
    public function testHasShippingDeliveryLineDiscountsFalse(): void
    {
        $this->assertFalse(
            $this->deliveryLineCollection->hasShippingDeliveryLineDiscounts()
        );
    }
}
