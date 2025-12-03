<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\Rate;

use Exception;
use Fedex\Base\Api\PriceEscaperInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterface;
use Fedex\FXOPricing\Model\Data\Rate\Detail;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Collection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\Discount\Collection as DiscountCollection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount\Collection as DeliveryLineDiscountCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\TestCase;

class DetailTest extends TestCase
{
    /**
     * @var \Fedex\FXOPricing\Model\EntityFactory|\PHPUnit\Framework\MockObject\MockObject
     * Mock object for the EntityFactory class used in unit tests.
     */
    protected $entityFactoryMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for the price escaper used in unit tests.
     */
    protected $priceEscaperMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Rate\DeliveryLineDiscount\CollectionFactory
     * Mock object for the DeliveryLineDiscountCollectionFactory used in unit tests.
     */
    protected $rateDeliveryLineDiscountCollectionFactoryMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Discount\Collection
     * Collection of discount data used for testing purposes.
     */
    protected $discountCollection;
    /**
     * @var mixed Represents the discount property used in the test case.
     */
    protected $discount;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Rate\Detail\Collection
     * Represents the collection used for testing rate details in the unit test.
     */
    protected $collection;
    /**
     * @var (\Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory)
     */
    protected $deliveryLineCollectionMock;
    /**
     * @var mixed $deliveryLine
     * Represents the delivery line data used in the test case.
     */
    protected $deliveryLine;
    /**
     * @var (\Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory)
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
    private const GROSS_AMOUNT_VALUE = '$132.39';

    /**
     * Gross amount alternative
     */
    private const GROSS_AMOUNT_ALTERNATIVE = '$200.39';

    /**
     * Net amount key
     */
    private const NET_AMOUNT_KEY = 'netAmount';

    /**
     * Net amount value
     */
    private const NET_AMOUNT_VALUE = '$122.40';

    /**
     * Net amount alternative
     */
    private const NET_AMOUNT_ALTERNATIVE = '$322.40';

    /**
     * Taxable amount key
     */
    private const TAXABLE_AMOUNT_KEY = 'taxableAmount';

    /**
     * Taxable amount value
     */
    private const TAXABLE_AMOUNT_VALUE = '$122.40';

    /**
     * Taxable amount alternative
     */
    private const TAXABLE_AMOUNT_ALTERNATIVE = '$155.40';

    /**
     * Tax amount key
     */
    private const TAX_AMOUNT_KEY = 'taxAmount';

    /**
     * Tax amount value
     */
    private const TAX_AMOUNT_VALUE = 'taxAmount';

    /**
     * Tax amount alternative
     */
    private const TAX_AMOUNT_ALTERNATIVE = 'taxAmount';

    /**
     * Total amount key
     */
    private const TOTAL_AMOUNT_KEY = 'totalAmount';

    /**
     * Total amount value
     */
    private const TOTAL_AMOUNT_VALUE = '$10.10';

    /**
     * Total amount alternative
     */
    private const TOTAL_AMOUNT_ALTERNATIVE = '$33.10';

    /**
     * Total discount amount key
     */
    private const TOTAL_DISCOUNT_AMOUNT_KEY = 'totalDiscountAmount';

    /**
     * Total discount amount value
     */
    private const TOTAL_DISCOUNT_AMOUNT_VALUE = '($9.99)';

    /**
     * Total discount amount value as float
     */
    private const TOTAL_DISCOUNT_AMOUNT_FLOAT_VALUE = 9.99;

    /**
     * Total discount amount alternative
     */
    private const TOTAL_DISCOUNT_AMOUNT_ALTERNATIVE = '($2.22)';

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
        $this->priceEscaperMock = $this->getMockForAbstractClass(PriceEscaperInterface::class);
        $this->rateDeliveryLineDiscountCollectionFactoryMock =
        $this->getMockBuilder(RateDeliveryLineDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->discountCollection = new DiscountCollection($this->entityFactoryMock);
        $this->discount = new Detail\Discount();
        $this->discountCollection->addItem($this->discount);
        $this->collection = new Collection($this->entityFactoryMock);
        $this->rateDeliveryLineDiscountCollectionFactoryMock
            ->method('create')
            ->willReturn(new DeliveryLineDiscountCollection($this->entityFactoryMock));
        $this->deliveryLineCollectionMock =
        $this->getMockBuilder(RateDeliveryLineCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLine = new DeliveryLine($this->rateDeliveryLineDiscountCollectionFactoryMock);
        $this->entityFactoryMock->method('create')->willReturn($this->deliveryLine);
        $this->discountCollectionMock =
        $this->getMockBuilder(RateDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->detail = new Detail(
            $this->deliveryLineCollectionMock,
            $this->discountCollectionMock,
            $this->priceEscaperMock,
            [
                self::DELIVERY_LINES_KEY => $this->collection,
                self::DISCOUNTS_KEY => $this->discountCollection,
                self::ESTIMATED_VS_ACTUAL_KEY => self::ESTIMATED_VS_ACTUAL_VALUE,
                self::GROSS_AMOUNT_KEY => self::GROSS_AMOUNT_VALUE,
                self::NET_AMOUNT_KEY => self::NET_AMOUNT_VALUE,
                self::TAXABLE_AMOUNT_KEY => self::TAXABLE_AMOUNT_VALUE,
                self::TAX_AMOUNT_KEY => self::TAX_AMOUNT_VALUE,
                self::TOTAL_AMOUNT_KEY => self::TOTAL_AMOUNT_VALUE,
                self::TOTAL_DISCOUNT_AMOUNT_KEY => self::TOTAL_DISCOUNT_AMOUNT_VALUE,
            ]
        );
    }

    /**
     * Test method GetDeliveryLines
     *
     * @return void
     */
    public function testGetDeliveryLines(): void
    {
        $this->assertInstanceOf(RateDeliveryLineCollectionInterface::class, $this->detail->getDeliveryLines());
        $this->assertEquals($this->collection, $this->detail->getDeliveryLines());
    }

    /**
     * Test method SetDeliveryLines
     *
     * @return void
     */
    public function testSetDeliveryLines(): void
    {
        $collection = new Collection($this->entityFactoryMock);
        $collection->addItem(new DeliveryLine($this->rateDeliveryLineDiscountCollectionFactoryMock));
        $this->assertInstanceOf(RateDeliveryLineCollectionInterface::class, $this->detail->getDeliveryLines());
        $this->detail->setDeliveryLines($collection);
        $this->assertNotEquals($this->collection, $this->detail->getDeliveryLines());
        $this->assertEquals($collection, $this->detail->getDeliveryLines());
    }

    /**
     * Test method GetDiscounts
     *
     * @return void
     */
    public function testGetDiscounts(): void
    {
        $this->assertInstanceOf(RateDiscountCollectionInterface::class, $this->detail->getDiscounts());
        $this->assertEquals($this->discountCollection, $this->detail->getDiscounts());
    }

    /**
     * Test method SetDiscounts
     *
     * @return void
     */
    public function testSetDiscounts(): void
    {
        $collection = new DiscountCollection($this->entityFactoryMock);
        $this->assertInstanceOf(RateDiscountCollectionInterface::class, $this->detail->getDiscounts());
        $this->detail->setDiscounts($collection);
        $this->assertNotEquals($this->discountCollection, $this->detail->getDiscounts());
        $this->assertEquals($collection, $this->detail->getDiscounts());
    }

    /**
     * Test method getEstimatedVsActual
     *
     * @return void
     */
    public function testGetEstimatedVsActual(): void
    {
        $this->assertEquals(self::ESTIMATED_VS_ACTUAL_VALUE, $this->detail->getEstimatedVsActual());
    }

    /**
     * Test method setEstimatedVsActual
     *
     * @return void
     */
    public function testSetEstimatedVsActual(): void
    {
        $this->detail->setEstimatedVsActual(self::ESTIMATED_VS_ACTUAL_ALTERNATIVE);
        $this->assertEquals(self::ESTIMATED_VS_ACTUAL_ALTERNATIVE, $this->detail->getEstimatedVsActual());
    }

    /**
     * Test method getGrossAmount
     *
     * @return void
     */
    public function testGetGrossAmount(): void
    {
        $this->assertEquals(self::GROSS_AMOUNT_VALUE, $this->detail->getGrossAmount());
    }

    /**
     * Test method setGrossAmount
     *
     * @return void
     */
    public function testSetGrossAmount(): void
    {
        $this->detail->setGrossAmount(self::GROSS_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::GROSS_AMOUNT_ALTERNATIVE, $this->detail->getGrossAmount());
    }

    /**
     * Test method getNetAmount
     *
     * @return void
     */
    public function testGetNetAmount(): void
    {
        $this->assertEquals(self::NET_AMOUNT_VALUE, $this->detail->getNetAmount());
    }

    /**
     * Test method setNetAmount
     *
     * @return void
     */
    public function testSetNetAmount(): void
    {
        $this->detail->setNetAmount(self::NET_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::NET_AMOUNT_ALTERNATIVE, $this->detail->getNetAmount());
    }

    /**
     * Test method getTaxableAmount
     *
     * @return void
     */
    public function testGetTaxableAmount(): void
    {
        $this->assertEquals(self::TAXABLE_AMOUNT_VALUE, $this->detail->getTaxableAmount());
    }

    /**
     * Test method setTaxableAmount
     *
     * @return void
     */
    public function testSetTaxableAmount(): void
    {
        $this->detail->setTaxableAmount(self::TAXABLE_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TAXABLE_AMOUNT_ALTERNATIVE, $this->detail->getTaxableAmount());
    }

    /**
     * Test method getTaxAmount
     *
     * @return void
     */
    public function testGetTaxAmount(): void
    {
        $this->assertEquals(self::TAX_AMOUNT_VALUE, $this->detail->getTaxAmount());
    }

    /**
     * Test method setTaxAmount
     *
     * @return void
     */
    public function testSetTaxAmount(): void
    {
        $this->detail->setTaxAmount(self::TAX_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TAX_AMOUNT_ALTERNATIVE, $this->detail->getTaxAmount());
    }

    /**
     * Test method getTotalAmount
     *
     * @return void
     */
    public function testGetTotalAmount(): void
    {
        $this->assertEquals(self::TOTAL_AMOUNT_VALUE, $this->detail->getTotalAmount());
    }

    /**
     * Test method setTotalAmount
     *
     * @return void
     */
    public function testSetTotalAmount(): void
    {
        $this->detail->setTotalAmount(self::TOTAL_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TOTAL_AMOUNT_ALTERNATIVE, $this->detail->getTotalAmount());
    }

    /**
     * Test method getTotalDiscountAmount
     *
     * @return void
     */
    public function testGetTotalDiscountAmount(): void
    {
        $this->assertEquals(self::TOTAL_DISCOUNT_AMOUNT_VALUE, $this->detail->getTotalDiscountAmount());
    }

    /**
     * Test method setTotalDiscountAmount
     *
     * @return void
     */
    public function testSetTotalDiscountAmount(): void
    {
        $this->detail->setTotalDiscountAmount(self::TOTAL_DISCOUNT_AMOUNT_ALTERNATIVE);
        $this->assertEquals(self::TOTAL_DISCOUNT_AMOUNT_ALTERNATIVE, $this->detail->getTotalDiscountAmount());
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
        $this->collection->addItem($deliveryLine);
        $this->detail->setDeliveryLines($this->collection);
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

    /**
     * Test method compareShippingDeliveryLineDiscounts
     *
     * @return void
     * @throws Exception
     */
    public function testCompareShippingDeliveryLineDiscountsTrue(): void
    {
        $this->deliveryLine->setDeliveryLineType('SHIPPING');
        $this->deliveryLine->setDeliveryDiscountAmount('$(9.99)');
        $this->priceEscaperMock->expects($this->once())->method('escape')->willReturn(9.99);
        $this->assertTrue(
            $this->detail->compareShippingDeliveryLineDiscounts(
                self::TOTAL_DISCOUNT_AMOUNT_FLOAT_VALUE
            )
        );
    }

    /**
     * Test method compareShippingDeliveryLineDiscounts
     *
     * @return void
     */
    public function testCompareShippingDeliveryLineDiscountsFalse(): void
    {
        $this->assertFalse(
            $this->detail->compareShippingDeliveryLineDiscounts(
                self::TOTAL_DISCOUNT_AMOUNT_FLOAT_VALUE
            )
        );
    }
}
