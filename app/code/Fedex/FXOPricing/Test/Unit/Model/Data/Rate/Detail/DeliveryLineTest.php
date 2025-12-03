<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\Rate\Detail;

use Exception;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount\Collection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\TestCase;

class DeliveryLineTest extends TestCase
{

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for the entity factory used in unit tests.
     */
    protected $entityFactoryMock;
    /**
     * @var (\Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory)
     */
    protected $rateDeliveryLineDiscountCollectionFactoryMock;
    /**
     * Delivery line discounts key
     */
    private const DELIVERY_LINE_DISCOUNTS_KEY = 'deliveryLineDiscounts';
    private const DELIVERY_DISCOUNT_AMOUNT_KEY = 'deliveryDiscountAmount';
    private const DELIVERY_DISCOUNT_AMOUNT_VALUE = '($9.99)';
    private const DELIVERY_DISCOUNT_AMOUNT_ALTERNATIVE = '($29.29)';
    private const DELIVERY_LINE_PRICE_KEY = 'deliveryLinePrice';
    private const DELIVERY_LINE_PRICE_VALUE = '$0.00';
    private const DELIVERY_LINE_PRICE_ALTERNATIVE = '$20.60';
    private const DELIVERY_LINE_TYPE_KEY = 'deliveryLineType';
    private const DELIVERY_LINE_TYPE_VALUE = 'SHIPPING';
    private const DELIVERY_LINE_TYPE_ALTERNATIVE = 'OTHER';
    private const DELIVERY_RETAIL_PRICE_KEY = 'deliveryRetailPrice';
    private const DELIVERY_RETAIL_PRICE_VALUE = '$9.99';
    private const DELIVERY_RETAIL_PRICE_ALTERNATIVE = '$29.29';

    private const ESTIMATED_DELIVERY_DURATION_KEY = 'estimatedDeliveryDuration';
    private const ESTIMATED_DELIVERY_DURATION_VALUE = 'estimatedDeliveryDuration';
    private const ESTIMATED_DELIVERY_DURATION_ALTERNATIVE = 'estimatedDeliveryDuration';

    private const ESTIMATED_DELIVERY_LOCAL_TIME_KEY = 'estimatedDeliveryLocalTime';
    private const ESTIMATED_DELIVERY_LOCAL_TIME_VALUE = '2023-06-22T23:59:00';
    private const ESTIMATED_DELIVERY_LOCAL_TIME_ALTERNATIVE = '2024-01-01T23:59:00';
    private const ESTIMATED_SHIP_DATE_KEY = 'estimatedShipDate';
    private const ESTIMATED_SHIP_DATE_VALUE = '2023-07-13';
    private const ESTIMATED_SHIP_DATE_ALTERNATIVE = '2023-07-13';
    private const PRICEABLE_KEY = 'priceable';
    private const PRICEABLE_VALUE = true;
    private const PRICEABLE_ALTERNATIVE = false;
    private const RECIPIENT_REFERENCE_KEY = 'recipientReference';
    private const RECIPIENT_REFERENCE_VALUE = '1';
    private const RECIPIENT_REFERENCE_ALTERNATIVE = '2';

    private const SHIPMENT_DETAILS_KEY = 'shipmentDetails';
    private const SHIPMENT_DETAILS_VALUE = 'shipmentDetails';
    private const SHIPMENT_DETAILS_ALTERNATIVE = 'shipmentDetails';

    /**
     * Discount type key
     */
    private const DISCOUNT_TYPE_KEY = 'type';

    /**
     * Discount type value
     */
    private const DISCOUNT_TYPE_VALUE = 'COUPON';

    /**
     * Discount amount key
     */
    private const DISCOUNT_AMOUNT_KEY = 'amount';

    /**
     * Discount amount value
     */
    private const DISCOUNT_AMOUNT_VALUE = '($9.99)';
    /**
     * @var Discount Represents the discount associated with the delivery line.
     */
    private Discount $discount;
    /**
     * @var DeliveryLine Represents the delivery line object being tested in the unit test.
     */
    private DeliveryLine $deliveryLine;
    /**
     * @var Collection $collection
     *
     * Represents a collection of items used in the test case.
     * This property is utilized to manage and test data related to
     * delivery line details in the FedEx FXO Pricing module.
     */
    private Collection $collection;

    /**
     * Setup tests
     *
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->rateDeliveryLineDiscountCollectionFactoryMock =
        $this->getMockBuilder(RateDeliveryLineDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = new Collection($this->entityFactoryMock);
        $this->discount = new Discount([
            self::DISCOUNT_TYPE_KEY => self::DISCOUNT_TYPE_VALUE,
            self::DISCOUNT_AMOUNT_KEY => self::DISCOUNT_AMOUNT_VALUE,
        ]);
        $this->collection->addItem($this->discount);
        $this->deliveryLine = new DeliveryLine(
            $this->rateDeliveryLineDiscountCollectionFactoryMock,
            [
                self::DELIVERY_LINE_DISCOUNTS_KEY => $this->collection,
                self::DELIVERY_DISCOUNT_AMOUNT_KEY => self::DELIVERY_DISCOUNT_AMOUNT_VALUE,
                self::DELIVERY_LINE_PRICE_KEY => self::DELIVERY_LINE_PRICE_VALUE,
                self::DELIVERY_LINE_TYPE_KEY => self::DELIVERY_LINE_TYPE_VALUE,
                self::DELIVERY_RETAIL_PRICE_KEY => self::DELIVERY_RETAIL_PRICE_VALUE,
                self::ESTIMATED_DELIVERY_LOCAL_TIME_KEY => self::ESTIMATED_DELIVERY_LOCAL_TIME_VALUE,
                self::ESTIMATED_SHIP_DATE_KEY => self::ESTIMATED_SHIP_DATE_VALUE,
                self::PRICEABLE_KEY => self::PRICEABLE_VALUE,
                self::RECIPIENT_REFERENCE_KEY => self::RECIPIENT_REFERENCE_VALUE
            ]
        );
    }

    /**
     * Test method getDeliveryDiscountAmount
     *
     * @return void
     */
    public function testGetDeliveryDiscountAmount(): void
    {
        $this->assertEquals(self::DELIVERY_DISCOUNT_AMOUNT_VALUE, $this->deliveryLine->getDeliveryDiscountAmount());
    }

    /**
     * Test method setDeliveryDiscountAmount
     *
     * @return void
     */
    public function testSetDeliveryDiscountAmount(): void
    {
        $this->deliveryLine->setDeliveryDiscountAmount(self::DELIVERY_DISCOUNT_AMOUNT_ALTERNATIVE);
        $this->assertEquals(
            self::DELIVERY_DISCOUNT_AMOUNT_ALTERNATIVE,
            $this->deliveryLine->getDeliveryDiscountAmount()
        );
    }

    /**
     * Test method getDeliveryLineDiscounts
     *
     * @return void
     */
    public function testGetDeliveryLineDiscounts(): void
    {
        $this->assertInstanceOf(
            RateDeliveryLineDiscountCollectionInterface::class,
            $this->deliveryLine->getDeliveryLineDiscounts()
        );
        $this->assertEquals($this->collection, $this->deliveryLine->getDeliveryLineDiscounts());
    }

    /**
     * Test method setDeliveryLineDiscounts
     *
     * @return void
     */
    public function testSetDeliveryLineDiscounts(): void
    {
        $collection = new Collection($this->entityFactoryMock);
        $this->deliveryLine->setDeliveryLineDiscounts($collection);
        $this->assertNotEquals($this->collection, $this->deliveryLine->getDeliveryLineDiscounts());
        $this->assertEquals($collection, $this->deliveryLine->getDeliveryLineDiscounts());
    }

    /**
     * Test method getDeliveryLinePrice
     *
     * @return void
     */
    public function testGetDeliveryLinePrice(): void
    {
        $this->assertEquals(self::DELIVERY_LINE_PRICE_VALUE, $this->deliveryLine->getDeliveryLinePrice());
    }

    /**
     * Test method setDeliveryLinePrice
     *
     * @return void
     */
    public function testSetDeliveryLinePrice(): void
    {
        $this->deliveryLine->setDeliveryLinePrice(self::DELIVERY_LINE_PRICE_ALTERNATIVE);
        $this->assertEquals(self::DELIVERY_LINE_PRICE_ALTERNATIVE, $this->deliveryLine->getDeliveryLinePrice());
    }

    /**
     * Test method getDeliveryLineType
     *
     * @return void
     */
    public function testGetDeliveryLineType(): void
    {
        $this->assertEquals(self::DELIVERY_LINE_TYPE_VALUE, $this->deliveryLine->getDeliveryLineType());
    }

    /**
     * Test method setDeliveryLineType
     *
     * @return void
     */
    public function testSetDeliveryLineType(): void
    {
        $this->deliveryLine->setDeliveryLineType(self::DELIVERY_LINE_TYPE_ALTERNATIVE);
        $this->assertEquals(self::DELIVERY_LINE_TYPE_ALTERNATIVE, $this->deliveryLine->getDeliveryLineType());
    }

    /**
     * Test method getDeliveryRetailPrice
     *
     * @return void
     */
    public function testGetDeliveryRetailPrice(): void
    {
        $this->assertEquals(self::DELIVERY_RETAIL_PRICE_VALUE, $this->deliveryLine->getDeliveryRetailPrice());
    }

    /**
     * Test method setDeliveryRetailPrice
     *
     * @return void
     */
    public function testSetDeliveryRetailPrice(): void
    {
        $this->deliveryLine->setDeliveryRetailPrice(self::DELIVERY_RETAIL_PRICE_ALTERNATIVE);
        $this->assertEquals(self::DELIVERY_RETAIL_PRICE_ALTERNATIVE, $this->deliveryLine->getDeliveryRetailPrice());
    }

    /**
     * Test method getEstimatedDeliveryLocalTime
     *
     * @return void
     */
    public function testGetEstimatedDeliveryLocalTime(): void
    {
        $this->assertEquals(
            self::ESTIMATED_DELIVERY_LOCAL_TIME_VALUE,
            $this->deliveryLine->getEstimatedDeliveryLocalTime()
        );
    }

    /**
     * Test method setEstimatedDeliveryLocalTime
     *
     * @return void
     */
    public function testSetEstimatedDeliveryLocalTime(): void
    {
        $this->deliveryLine->setEstimatedDeliveryLocalTime(self::ESTIMATED_DELIVERY_LOCAL_TIME_ALTERNATIVE);
        $this->assertEquals(
            self::ESTIMATED_DELIVERY_LOCAL_TIME_ALTERNATIVE,
            $this->deliveryLine->getEstimatedDeliveryLocalTime()
        );
    }

    /**
     * Test method getEstimatedShipDate
     *
     * @return void
     */
    public function testGetEstimatedShipDate(): void
    {
        $this->assertEquals(self::ESTIMATED_SHIP_DATE_VALUE, $this->deliveryLine->getEstimatedShipDate());
    }

    /**
     * Test method setEstimatedShipDate
     *
     * @return void
     */
    public function testSetEstimatedShipDate(): void
    {
        $this->deliveryLine->setEstimatedShipDate(self::ESTIMATED_SHIP_DATE_ALTERNATIVE);
        $this->assertEquals(self::ESTIMATED_SHIP_DATE_ALTERNATIVE, $this->deliveryLine->getEstimatedShipDate());
    }

    /**
     * Test method getPriceable
     *
     * @return void
     */
    public function testGetPriceable(): void
    {
        $this->assertEquals(self::PRICEABLE_VALUE, $this->deliveryLine->getPriceable());
    }

    /**
     * Test method setPriceable
     *
     * @return void
     */
    public function testSetPriceable(): void
    {
        $this->deliveryLine->setPriceable(self::PRICEABLE_ALTERNATIVE);
        $this->assertEquals(self::PRICEABLE_ALTERNATIVE, $this->deliveryLine->getPriceable());
    }

    /**
     * Test method getRecipientReference
     *
     * @return void
     */
    public function testGetRecipientReference(): void
    {
        $this->assertEquals(self::RECIPIENT_REFERENCE_VALUE, $this->deliveryLine->getRecipientReference());
    }

    /**
     * Test method setRecipientReference
     *
     * @return void
     */
    public function testSetRecipientReference(): void
    {
        $this->deliveryLine->setRecipientReference(self::RECIPIENT_REFERENCE_ALTERNATIVE);
        $this->assertEquals(self::RECIPIENT_REFERENCE_ALTERNATIVE, $this->deliveryLine->getRecipientReference());
    }

    /**
     * Test method hasDiscounts
     *
     * @return void
     */
    public function testHasDiscountsTrue(): void
    {
        $this->assertTrue($this->deliveryLine->hasDiscounts());
    }

    /**
     * Test method hasDiscounts
     *
     * @return void
     */
    public function testHasDiscountsFalse(): void
    {
        $this->deliveryLine->setDeliveryLineDiscounts(new Collection($this->entityFactoryMock));
        $this->assertFalse($this->deliveryLine->hasDiscounts());
    }
}
