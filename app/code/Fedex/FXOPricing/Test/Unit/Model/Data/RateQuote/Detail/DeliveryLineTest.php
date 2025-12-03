<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\RateQuote\Detail;

use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterface;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Discount;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Discount\Collection;
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
     * @var (\Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterfaceFactory
     */
    protected $rateDeliveryLineDiscountCollectionFactoryMock;
    /**
     * Delivery line discounts key
     */
    private const DELIVERY_LINE_DISCOUNTS_KEY = 'deliveryLineDiscounts';
    private const DELIVERY_DISCOUNT_AMOUNT_KEY = 'deliveryDiscountAmount';
    private const DELIVERY_DISCOUNT_AMOUNT_VALUE = 9.99;
    private const DELIVERY_DISCOUNT_AMOUNT_ALTERNATIVE = 29.29;
    private const DELIVERY_LINE_PRICE_KEY = 'deliveryLinePrice';
    private const DELIVERY_LINE_PRICE_VALUE = 0.00;
    private const DELIVERY_LINE_PRICE_ALTERNATIVE = 20.60;
    private const DELIVERY_LINE_TYPE_KEY = 'deliveryLineType';
    private const DELIVERY_LINE_TYPE_VALUE = 'SHIPPING';
    private const DELIVERY_LINE_TYPE_ALTERNATIVE = 'OTHER';
    private const DELIVERY_RETAIL_PRICE_KEY = 'deliveryRetailPrice';
    private const DELIVERY_RETAIL_PRICE_VALUE = 9.99;
    private const DELIVERY_RETAIL_PRICE_ALTERNATIVE = 29.29;

    private const PRICEABLE_KEY = 'priceable';
    private const PRICEABLE_VALUE = true;
    private const PRICEABLE_ALTERNATIVE = false;
    private const RECIPIENT_REFERENCE_KEY = 'recipientReference';
    private const RECIPIENT_REFERENCE_VALUE = '1';
    private const RECIPIENT_REFERENCE_ALTERNATIVE = '2';

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
     * @var DeliveryLine Represents the delivery line object used in the test.
     */
    private DeliveryLine $deliveryLine;
    /**
     * @var Collection $collection
     *
     * Represents a collection of items used in the test case.
     * This property is utilized to perform operations on a set of data
     * within the unit test for the DeliveryLine model.
     */
    private Collection $collection;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->rateDeliveryLineDiscountCollectionFactoryMock =
        $this->getMockBuilder(RateQuoteDeliveryLineDiscountCollectionInterfaceFactory::class)
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
    public function testGetDeliveryDiscountAmount()
    {
        $this->assertEquals(self::DELIVERY_DISCOUNT_AMOUNT_VALUE, $this->deliveryLine->getDeliveryDiscountAmount());
    }

    /**
     * Test method setDeliveryDiscountAmount
     *
     * @return void
     */
    public function testSetDeliveryDiscountAmount()
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
    public function testGetDeliveryLineDiscounts()
    {
        $this->assertInstanceOf(
            RateQuoteDeliveryLineDiscountCollectionInterface::class,
            $this->deliveryLine->getDeliveryLineDiscounts()
        );
        $this->assertEquals($this->collection, $this->deliveryLine->getDeliveryLineDiscounts());
    }

    /**
     * Test method setDeliveryLineDiscounts
     *
     * @return void
     */
    public function testSetDeliveryLineDiscounts()
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
    public function testGetDeliveryLinePrice()
    {
        $this->assertEquals(self::DELIVERY_LINE_PRICE_VALUE, $this->deliveryLine->getDeliveryLinePrice());
    }

    /**
     * Test method setDeliveryLinePrice
     *
     * @return void
     */
    public function testSetDeliveryLinePrice()
    {
        $this->deliveryLine->setDeliveryLinePrice(self::DELIVERY_LINE_PRICE_ALTERNATIVE);
        $this->assertEquals(self::DELIVERY_LINE_PRICE_ALTERNATIVE, $this->deliveryLine->getDeliveryLinePrice());
    }

    /**
     * Test method getDeliveryLineType
     *
     * @return void
     */
    public function testGetDeliveryLineType()
    {
        $this->assertEquals(self::DELIVERY_LINE_TYPE_VALUE, $this->deliveryLine->getDeliveryLineType());
    }

    /**
     * Test method setDeliveryLineType
     *
     * @return void
     */
    public function testSetDeliveryLineType()
    {
        $this->deliveryLine->setDeliveryLineType(self::DELIVERY_LINE_TYPE_ALTERNATIVE);
        $this->assertEquals(self::DELIVERY_LINE_TYPE_ALTERNATIVE, $this->deliveryLine->getDeliveryLineType());
    }

    /**
     * Test method getDeliveryRetailPrice
     *
     * @return void
     */
    public function testGetDeliveryRetailPrice()
    {
        $this->assertEquals(self::DELIVERY_RETAIL_PRICE_VALUE, $this->deliveryLine->getDeliveryRetailPrice());
    }

    /**
     * Test method setDeliveryRetailPrice
     *
     * @return void
     */
    public function testSetDeliveryRetailPrice()
    {
        $this->deliveryLine->setDeliveryRetailPrice(self::DELIVERY_RETAIL_PRICE_ALTERNATIVE);
        $this->assertEquals(self::DELIVERY_RETAIL_PRICE_ALTERNATIVE, $this->deliveryLine->getDeliveryRetailPrice());
    }

    /**
     * Test method getPriceable
     *
     * @return void
     */
    public function testGetPriceable()
    {
        $this->assertEquals(self::PRICEABLE_VALUE, $this->deliveryLine->getPriceable());
    }

    /**
     * Test method setPriceable
     *
     * @return void
     */
    public function testSetPriceable()
    {
        $this->deliveryLine->setPriceable(self::PRICEABLE_ALTERNATIVE);
        $this->assertEquals(self::PRICEABLE_ALTERNATIVE, $this->deliveryLine->getPriceable());
    }

    /**
     * Test method getRecipientReference
     *
     * @return void
     */
    public function testGetRecipientReference()
    {
        $this->assertEquals(self::RECIPIENT_REFERENCE_VALUE, $this->deliveryLine->getRecipientReference());
    }

    /**
     * Test method setRecipientReference
     *
     * @return void
     */
    public function testSetRecipientReference()
    {
        $this->deliveryLine->setRecipientReference(self::RECIPIENT_REFERENCE_ALTERNATIVE);
        $this->assertEquals(self::RECIPIENT_REFERENCE_ALTERNATIVE, $this->deliveryLine->getRecipientReference());
    }
}
