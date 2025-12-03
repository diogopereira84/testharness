<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\RateQuote;

use Exception;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterface;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Collection as DeliveryLineCollection;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Discount\Collection as DiscountCollection;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Discount\Collection as DeliveryLineDiscountCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\TestCase;

class DetailTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\EntityFactory
     * Mock object for the EntityFactory class used in unit testing.
     */
    protected $entityFactoryMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\RateDeliveryLineDiscount\CollectionFactory
     * Mock object for the RateDeliveryLineDiscountCollectionFactory used for testing purposes.
     */
    protected $rateDeliveryLineDiscountCollectionFactoryMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Discount\Collection
     * A collection of discount data used for testing purposes.
     */
    protected $discountCollection;
    /**
     * @var mixed The discount property used for testing purposes in the RateQuote DetailTest.
     */
    protected $discount;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\DeliveryLine\Collection
     * Represents the collection of delivery lines used in the test case.
     */
    protected $deliveryLineCollection;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\Data\RateQuote\DeliveryLineCollection
     * Mock object for the DeliveryLineCollection class used in unit tests.
     */
    protected $deliveryLineCollectionMock;
    /**
     * @var mixed $deliveryLine
     * Represents the delivery line data used in the test case.
     */
    protected $deliveryLine;
    /**
     * Mock object for the discount collection.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $discountCollectionMock;
    /**
     * Delivery line key
     */
    private const DELIVERY_LINES_KEY = 'deliveryLines';

    /**
     * Discounts collection key
     */
    private const DISCOUNTS_KEY = 'discounts';

    /**
     * Estimated vs actual key
     */
    private const ESTIMATED_VS_ACTUAL_KEY = 'estimatedVsActual';

    /**
     * Estimated vs actual value
     */
    private const ESTIMATED_VS_ACTUAL_VALUE = 'ACTUAL';

    /**
     * Estimated vs actual alternative
     */
    private const ESTIMATED_VS_ACTUAL_ALTERNATIVE = 'ESTIMATED';

    /**
     * Gross amount key
     */
    private const GROSS_AMOUNT_KEY = 'grossAmount';

    /**
     * Gross amount value
     */
    private const GROSS_AMOUNT_VALUE = 132.39;

    /**
     * Gross amount alternative
     */
    private const GROSS_AMOUNT_ALTERNATIVE = 200.39;

    /**
     * Net amount key
     */
    private const NET_AMOUNT_KEY = 'netAmount';

    /**
     * Net amount value
     */
    private const NET_AMOUNT_VALUE = 122.40;

    /**
     * Net amount alternative
     */
    private const NET_AMOUNT_ALTERNATIVE = 322.40;

    /**
     * Product Lines key
     */
    private const PRODUCT_LINES_KEY = 'productLines';

    /**
     * Product Lines value
     */
    private const PRODUCT_LINES_VALUE = [];

    /**
     * Product Lines alternative
     */
    private const PRODUCT_LINES_ALTERNATIVE = [[],[]];

    /**
     * Taxable amount key
     */
    private const TAXABLE_AMOUNT_KEY = 'taxableAmount';

    /**
     * Taxable amount value
     */
    private const TAXABLE_AMOUNT_VALUE = 122.40;

    /**
     * Taxable amount alternative
     */
    private const TAXABLE_AMOUNT_ALTERNATIVE = 155.40;

    /**
     * Tax amount key
     */
    private const TAX_AMOUNT_KEY = 'taxAmount';

    /**
     * Tax amount value
     */
    private const TAX_AMOUNT_VALUE = 125.40;

    /**
     * Tax amount alternative
     */
    private const TAX_AMOUNT_ALTERNATIVE = 200.40;

    /**
     * Total amount key
     */
    private const TOTAL_AMOUNT_KEY = 'totalAmount';

    /**
     * Total amount value
     */
    private const TOTAL_AMOUNT_VALUE = 10.10;

    /**
     * Total amount alternative
     */
    private const TOTAL_AMOUNT_ALTERNATIVE = 33.10;

    /**
     * Total discount amount key
     */
    private const TOTAL_DISCOUNT_AMOUNT_KEY = 'totalDiscountAmount';

    /**
     * Total discount amount value
     */
    private const TOTAL_DISCOUNT_AMOUNT_VALUE = 9.99;

    /**
     * Total discount amount alternative
     */
    private const TOTAL_DISCOUNT_AMOUNT_ALTERNATIVE = 2.22;

    /**
     * Rate quote id key
     */
    private const RATE_QUOTE_ID_KEY = 'rateQuoteId';

    /**
     * Rate quote id value
     */
    private const RATE_QUOTE_ID_VALUE = 'some-id';

    /**
     * Rate quote id alternative
     */
    private const RATE_QUOTE_ID_ALTERNATIVE = 'other-id';

    /**
     * Responsible location id key
     */
    private const RESPONSIBLE_LOCATION_ID_KEY = 'responsibleLocationId';

    /**
     * Responsible location id value
     */
    private const RESPONSIBLE_LOCATION_ID_VALUE = 'some-id';

    /**
     * Responsible location id alternative
     */
    private const RESPONSIBLE_LOCATION_ID_ALTERNATIVE = 'other-id';

    /**
     * @var Detail
     */
    private Detail $detail;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->rateDeliveryLineDiscountCollectionFactoryMock = $this->getMockBuilder(
            RateQuoteDeliveryLineDiscountCollectionInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateDeliveryLineDiscountCollectionFactoryMock
            ->method('create')
            ->willReturn(new DeliveryLineDiscountCollection($this->entityFactoryMock));
        $this->discountCollection = new DiscountCollection($this->entityFactoryMock);
        $this->discount = new Detail\Discount();
        $this->discountCollection->addItem($this->discount);
        $this->discountCollection->addItem(new Detail\Discount());
        $this->deliveryLineCollection = new DeliveryLineCollection($this->entityFactoryMock);
        $this->deliveryLineCollection->addItem(new DeliveryLine($this->rateDeliveryLineDiscountCollectionFactoryMock));
        $this->deliveryLineCollectionMock = $this->getMockBuilder(
            RateQuoteDeliveryLineCollectionInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLine = new DeliveryLine($this->rateDeliveryLineDiscountCollectionFactoryMock);
        $this->entityFactoryMock->method('create')->willReturn($this->deliveryLine);
        $this->discountCollectionMock = $this->getMockBuilder(RateQuoteDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineCollectionMock->method('create')->willReturn($this->deliveryLineCollection);
        $this->discountCollectionMock->method('create')->willReturn($this->discountCollection);
        $this->detail = new Detail(
            $this->deliveryLineCollectionMock,
            $this->discountCollectionMock,
            [
                self::DELIVERY_LINES_KEY => $this->deliveryLineCollection,
                self::DISCOUNTS_KEY => $this->discountCollection,
                self::ESTIMATED_VS_ACTUAL_KEY => self::ESTIMATED_VS_ACTUAL_VALUE,
                self::GROSS_AMOUNT_KEY => self::GROSS_AMOUNT_VALUE,
                self::NET_AMOUNT_KEY => self::NET_AMOUNT_VALUE,
                self::TAXABLE_AMOUNT_KEY => self::TAXABLE_AMOUNT_VALUE,
                self::TAX_AMOUNT_KEY => self::TAX_AMOUNT_VALUE,
                self::TOTAL_AMOUNT_KEY => self::TOTAL_AMOUNT_VALUE,
                self::TOTAL_DISCOUNT_AMOUNT_KEY => self::TOTAL_DISCOUNT_AMOUNT_VALUE,
                self::RATE_QUOTE_ID_KEY => self::RATE_QUOTE_ID_VALUE,
                self::PRODUCT_LINES_KEY => self::PRODUCT_LINES_VALUE,
                self::RESPONSIBLE_LOCATION_ID_KEY => self::RESPONSIBLE_LOCATION_ID_VALUE,
            ]
        );
    }

    /**
     * Test method GetDeliveryLines
     *
     * @return void
     */
    public function testGetDeliveryLines()
    {
        $this->assertInstanceOf(RateQuoteDeliveryLineCollectionInterface::class, $this->detail->getDeliveryLines());
        $this->assertEquals($this->deliveryLineCollection, $this->detail->getDeliveryLines());
    }

    /**
     * Test method SetDeliveryLines
     *
     * @return void
     */
    public function testSetDeliveryLines()
    {
        $collection = new DeliveryLineCollection($this->entityFactoryMock);
        $this->assertInstanceOf(RateQuoteDeliveryLineCollectionInterface::class, $this->detail->getDeliveryLines());
        $this->detail->setDeliveryLines($collection);
        $this->assertNotEquals($this->deliveryLineCollection, $this->detail->getDeliveryLines());
        $this->assertEquals($collection, $this->detail->getDeliveryLines());
    }

    /**
     * Test method GetDiscounts
     *
     * @return void
     */
    public function testGetDiscounts()
    {
        $this->assertInstanceOf(RateQuoteDiscountCollectionInterface::class, $this->detail->getDiscounts());
        $this->assertEquals($this->discountCollection, $this->detail->getDiscounts());
    }

    /**
     * Test method SetDiscounts
     *
     * @return void
     */
    public function testSetDiscounts()
    {
        $collection = new DiscountCollection($this->entityFactoryMock);
        $this->assertInstanceOf(RateQuoteDiscountCollectionInterface::class, $this->detail->getDiscounts());
        $this->detail->setDiscounts($collection);
        $this->assertNotEquals($this->discountCollection, $this->detail->getDiscounts());
        $this->assertEquals($collection, $this->detail->getDiscounts());
    }

    /**
     * Test method getEstimatedVsActual
     *
     * @return void
     */
    public function testGetEstimatedVsActual()
    {
        $this->assertEquals(self::ESTIMATED_VS_ACTUAL_VALUE, $this->detail->getEstimatedVsActual());
    }

    /**
     * Test method setEstimatedVsActual
     *
     * @return void
     */
    public function testSetEstimatedVsActual()
    {
        $this->detail->setEstimatedVsActual(self::ESTIMATED_VS_ACTUAL_ALTERNATIVE);
        $this->assertEquals(self::ESTIMATED_VS_ACTUAL_ALTERNATIVE, $this->detail->getEstimatedVsActual());
    }

    /**
     * Test method getGrossAmount
     *
     * @return void
     */
    public function testGetGrossAmount()
    {
        $this->assertEquals(self::GROSS_AMOUNT_VALUE, $this->detail->getGrossAmount());
    }

    /**
     * Test method setGrossAmount
     *
     * @return void
     */
    public function testSetGrossAmount()
    {
        $this->detail->setGrossAmount(self::GROSS_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::GROSS_AMOUNT_ALTERNATIVE, $this->detail->getGrossAmount());
    }

    /**
     * Test method getNetAmount
     *
     * @return void
     */
    public function testGetNetAmount()
    {
        $this->assertEquals(self::NET_AMOUNT_VALUE, $this->detail->getNetAmount());
    }

    /**
     * Test method setNetAmount
     *
     * @return void
     */
    public function testSetNetAmount()
    {
        $this->detail->setNetAmount(self::NET_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::NET_AMOUNT_ALTERNATIVE, $this->detail->getNetAmount());
    }

    /**
     * Test method getProductLines
     *
     * @return void
     */
    public function testGetProductLines()
    {
        $this->assertEquals(self::PRODUCT_LINES_VALUE, $this->detail->getProductLines());
    }

    /**
     * Test method setProductLines
     *
     * @return void
     */
    public function testSetProductLines()
    {
        $this->detail->setProductLines(self::PRODUCT_LINES_ALTERNATIVE);
        $this->assertEquals(self::PRODUCT_LINES_ALTERNATIVE, $this->detail->getProductLines());
    }

    /**
     * Test method getTaxableAmount
     *
     * @return void
     */
    public function testGetTaxableAmount()
    {
        $this->assertEquals(self::TAXABLE_AMOUNT_VALUE, $this->detail->getTaxableAmount());
    }

    /**
     * Test method setTaxableAmount
     *
     * @return void
     */
    public function testSetTaxableAmount()
    {
        $this->detail->setTaxableAmount(self::TAXABLE_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TAXABLE_AMOUNT_ALTERNATIVE, $this->detail->getTaxableAmount());
    }

    /**
     * Test method getTaxAmount
     *
     * @return void
     */
    public function testGetTaxAmount()
    {
        $this->assertEquals(self::TAX_AMOUNT_VALUE, $this->detail->getTaxAmount());
    }

    /**
     * Test method setTaxAmount
     *
     * @return void
     */
    public function testSetTaxAmount()
    {
        $this->detail->setTaxAmount(self::TAX_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TAX_AMOUNT_ALTERNATIVE, $this->detail->getTaxAmount());
    }

    /**
     * Test method getTotalAmount
     *
     * @return void
     */
    public function testGetTotalAmount()
    {
        $this->assertEquals(self::TOTAL_AMOUNT_VALUE, $this->detail->getTotalAmount());
    }

    /**
     * Test method setTotalAmount
     *
     * @return void
     */
    public function testSetTotalAmount()
    {
        $this->detail->setTotalAmount(self::TOTAL_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TOTAL_AMOUNT_ALTERNATIVE, $this->detail->getTotalAmount());
    }

    /**
     * Test method getTotalDiscountAmount
     *
     * @return void
     */
    public function testGetTotalDiscountAmount()
    {
        $this->assertEquals(self::TOTAL_DISCOUNT_AMOUNT_VALUE, $this->detail->getTotalDiscountAmount());
    }

    /**
     * Test method setTotalDiscountAmount
     *
     * @return void
     */
    public function testSetTotalDiscountAmount()
    {
        $this->detail->setTotalDiscountAmount(self::TOTAL_DISCOUNT_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TOTAL_DISCOUNT_AMOUNT_ALTERNATIVE, $this->detail->getTotalDiscountAmount());
    }

    /**
     * Test method getRateQuoteId
     *
     * @return void
     */
    public function testGetRateQuoteId()
    {
        $this->assertEquals(self::RATE_QUOTE_ID_VALUE, $this->detail->getRateQuoteId());
    }

    /**
     * Test method setRateQuoteId
     *
     * @return void
     */
    public function testSetRateQuoteId()
    {
        $this->detail->setRateQuoteId(self::RATE_QUOTE_ID_ALTERNATIVE);
        $this->assertEquals(self::RATE_QUOTE_ID_ALTERNATIVE, $this->detail->getRateQuoteId());
    }

    /**
     * Test method getResponsibleLocationId
     *
     * @return void
     */
    public function testGetResponsibleLocationId()
    {
        $this->assertEquals(self::RESPONSIBLE_LOCATION_ID_VALUE, $this->detail->getResponsibleLocationId());
    }

    /**
     * Test method setResponsibleLocationId
     *
     * @return void
     */
    public function testSetResponsibleLocationId()
    {
        $this->detail->setResponsibleLocationId(self::RESPONSIBLE_LOCATION_ID_ALTERNATIVE);
        $this->assertEquals(self::RESPONSIBLE_LOCATION_ID_ALTERNATIVE, $this->detail->getResponsibleLocationId());
    }

    /**
     * Test method hasShippingDeliveryLineDiscount
     *
     * @return void
     * @throws Exception
     */
    public function testHasShippingDeliveryLineDiscountTrue(): void
    {
        $deliveryLineDiscountCollection = new DeliveryLineDiscountCollection($this->entityFactoryMock);
        $deliveryLineDiscountCollection->addItem(new Detail\DeliveryLine\Discount());
        $deliveryLine = new DeliveryLine($this->rateDeliveryLineDiscountCollectionFactoryMock);
        $deliveryLine->setDeliveryLineType('SHIPPING');
        $deliveryLine->setDeliveryLineDiscounts($deliveryLineDiscountCollection);
        $this->deliveryLineCollection->addItem($deliveryLine);
        $this->detail->setDeliveryLines($this->deliveryLineCollection);
        $this->assertTrue($this->detail->hasShippingDeliveryLineDiscount());
    }

    /**
     * Test method hasShippingDeliveryLineDiscount
     *
     * @return void
     */
    public function testHasShippingDeliveryLineDiscountFalse(): void
    {
        $this->assertFalse($this->detail->hasShippingDeliveryLineDiscount());
    }

    /**
     * Test method hasCouponDiscounts
     *
     * @return void
     * @throws Exception
     */
    public function testHasCouponDiscountsTrue(): void
    {
        $this->assertTrue($this->detail->hasCouponDiscounts());
    }

    /**
     * Test method hasCouponDiscounts
     *
     * @return void
     */
    public function testHasCouponDiscountsFalse(): void
    {
        $this->detail->setDiscounts(new DiscountCollection($this->entityFactoryMock));
        $this->assertFalse($this->detail->hasCouponDiscounts());
    }
}
