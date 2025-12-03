<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data;

use Exception;
use Fedex\Base\Api\PriceEscaperInterface;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterface;
use Fedex\FXOPricing\Model\Data\Rate;
use Fedex\FXOPricing\Model\Data\Rate\Detail;
use Fedex\FXOPricing\Model\Data\Rate\Detail\Collection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Collection as DeliveryLineCollection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount\Collection as DiscountCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDetailInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface;

class RateTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Api\Data\RateDetailCollectionInterfaceFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rateDetailCollectionMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\Data\DeliveryLineCollection
     * Mock object for the DeliveryLineCollection class used for testing purposes.
     */
    protected $deliveryLineCollectionMock;
    /**
     * @var \Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface
     * Mock instance of the RateDeliveryLineInterface used for testing purposes.
     */
    protected $rateDeliveryLineInterface;
    /**
     * @var \Fedex\FXOPricing\Api\Data\RateDetailInterface
     * Mock instance of the RateDeliveryLineInterface used for testing purposes.
     */
    protected $detailMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for the discount collection used in unit tests.
     */
    protected $discountCollectionMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\DeliveryLine\Collection
     * Represents the collection of delivery lines used for testing purposes.
     */
    protected $deliveryLineCollection;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Discount\Collection
     * Represents the collection of discount data used in the test.
     */
    protected $discountCollection;
    /**
     * @var mixed $details
     * This property is used to store details related to the test case.
     * The exact type and purpose of the details should be defined based on its usage in the test methods.
     */
    protected $details;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Rate\Collection
     * Represents the collection of rate data used in the unit test.
     */
    protected $collection;
    private const CURRENCY_KEY = 'currency';
    private const CURRENCY_VALUE = 'USD';
    private const CURRENCY_VALUE_ALTERNATIVE = 'USD';
    /**
     * Details key
     */
    private const RATE_DETAILS_KEY = 'rateDetails';

    private const RATE_ARRAY = [
        'currency' => 'USD',
        'rateDetails' =>
            [
                [
                    'grossAmount' => '$132.39',
                    'totalDiscountAmount' => '($9.99)',
                    'netAmount' => '$122.40',
                    'taxableAmount' => '$122.30',
                    'taxAmount' => '$10.10',
                    'totalAmount' => '$132.50',
                    'estimatedVsActual' => 'ACTUAL',
                    'deliveryLines' => [],
                    'discounts' => [],
                ],
            ],
    ];
    /**
     * @codingStandardsIgnoreStart
     */
    private const JSON_ENCODED = '{"currency":"USD","rateDetails":[{"deliveryLines":[],"discounts":[],"estimatedVsActual":"ACTUAL","grossAmount":"$132.39","netAmount":"$122.40","taxableAmount":"$122.30","taxAmount":"$10.10","totalAmount":"$132.50","totalDiscountAmount":"($9.99)"}]}';
    /**
     * @codingStandardsIgnoreEnd
     */
    private const GROSS_AMOUNT = '$132.39';
    private const TOTAL_DISCOUNT_AMOUNT = '($9.99)';
    private const NET_AMOUNT = '$122.40';
    private const TAXABLE_AMOUNT = '$122.30';
    private const TAX_AMOUNT = '$10.10';
    private const TOTAL_AMOUNT = '$132.50';
    private const ESTIMATED_VS_ACTUAL = 'ACTUAL';
    private const DETAILS = [
        'grossAmount' => self::GROSS_AMOUNT,
        'totalDiscountAmount' => self::TOTAL_DISCOUNT_AMOUNT,
        'netAmount' => self::NET_AMOUNT,
        'taxableAmount' => self::TAXABLE_AMOUNT,
        'taxAmount' => self::TAX_AMOUNT,
        'totalAmount' => self::TOTAL_AMOUNT,
        'estimatedVsActual' => self::ESTIMATED_VS_ACTUAL,
        'deliveryLines' => [],
        'discounts' => [],
    ];

    /**
     * @var MockObject|EntityFactoryInterface
     */
    private MockObject|EntityFactoryInterface $entityFactoryMock;

    /**
     * @var MockObject|PriceEscaperInterface
     */
    private MockObject|PriceEscaperInterface $priceEscaperMock;

    /**
     * @var Rate
     */
    private Rate $rate;
    /**
     * @var MockObject|RateDetailCollectionInterface Mock object for testing RateDetailCollectionInterface.
     */
    private MockObject|RateDetailCollectionInterface $detailCollectionMock;

    /**
     * Setup tests
     *
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->rateDeliveryLineInterface = $this->getMockForAbstractClass(RateDeliveryLineInterface::class);
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->priceEscaperMock = $this->getMockForAbstractClass(PriceEscaperInterface::class);
        $this->rateDetailCollectionMock = $this->getMockBuilder(RateDetailCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineCollectionMock = $this->getMockBuilder(RateDeliveryLineCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollectionMock = $this->getMockBuilder(RateDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->detailMock = $this->getMockBuilder(RateDetailInterface::class)
            ->setMethods([
                'getGrossAmount',
                'getTotalDiscountAmount',
                'getNetAmount',
                'getTaxableAmount',
                'getTaxAmount',
                'getTotalAmount',
                'getEstimatedVsActual',
                'hasShippingDeliveryLineDiscount',
                'toArray',
                'getItemByType',
                'hasShippingDeliveryLines',
                'hasFreeShipping',
                'getDeliveryLines',
                'getDiscounts',
                'hasFreeGroundShipping'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->detailCollectionMock = $this->getMockBuilder(RateDetailCollectionInterface::class)
            ->setMethods([
                'count',
                'hasAnyDeliveryLineWithDiscount',
                'hasFreeGroundShipping',
                'toArrayItems',
                'getFirstItem'
            ])
            ->disableOriginalConstructor()
            ->getMock();
//$this->deliveryLineCollection = new DeliveryLineCollection($this->entityFactoryMock);
        $this->deliveryLineCollection = $this->getMockBuilder(DeliveryLineCollection::class)
            ->setMethods(['create', 'getItemByType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollection = new DiscountCollection($this->entityFactoryMock);
        $this->deliveryLineCollectionMock->method('create')->willReturn($this->deliveryLineCollection);
        $this->discountCollectionMock->method('create')->willReturn($this->discountCollection);
        $this->details = new Detail(
            $this->deliveryLineCollectionMock,
            $this->discountCollectionMock,
            $this->priceEscaperMock,
            self::DETAILS
        );
        $this->collection = new Collection($this->entityFactoryMock);
        $this->collection->addItem($this->details);
        $this->rate = new Rate(
            $this->rateDetailCollectionMock,
            $this->priceEscaperMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_DETAILS_KEY => $this->collection,
            ]
        );
    }

    /**
     * Test method getCurrency
     *
     * @return void
     */
    public function testGetCurrency(): void
    {
        $this->assertEquals(self::CURRENCY_VALUE, $this->rate->getCurrency());
    }

    /**
     * Test method setCurrency
     *
     * @return void
     */
    public function testSetCurrency(): void
    {
        $this->rate->setCurrency(self::CURRENCY_VALUE_ALTERNATIVE);
        $this->assertEquals(self::CURRENCY_VALUE_ALTERNATIVE, $this->rate->getCurrency());
    }

    /**
     * Test method getDetails
     *
     * @return void
     */
    public function testGetDetails(): void
    {
        $this->assertInstanceOf(RateDetailCollectionInterface::class, $this->rate->getDetails());
        $this->assertEquals($this->collection, $this->rate->getDetails());
    }

    /**
     * Test method setDetails
     *
     * @return void
     */
    public function testSetDetails(): void
    {
        $collection = new Collection($this->entityFactoryMock);
        $this->assertInstanceOf(RateDetailCollectionInterface::class, $this->rate->getDetails());
        $this->rate->setDetails($collection);
        $this->assertNotEquals($this->collection, $this->rate->getDetails());
        $this->assertEquals($collection, $this->rate->getDetails());
    }

    /**
     * Test method testToJson
     *
     * @return void
     */
    public function testToJson(): void
    {
        $this->assertEquals(self::JSON_ENCODED, $this->rate->toJson());
    }

    /**
     * Test method testToArray
     *
     * @return void
     */
    public function testToArray(): void
    {
        $this->assertEquals(self::RATE_ARRAY, $this->rate->toArray());
    }

    /**
     * Test method hasDetailShippingDeliveryLine
     *
     * @return void
     */
    public function testHasDetailShippingDeliveryLine(): void
    {
        $this->detailMock->expects($this->once())
            ->method('hasShippingDeliveryLines')
            ->willReturn(true);

        $this->detailCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->detailMock);

        $this->rate = new Rate(
            $this->rateDetailCollectionMock,
            $this->priceEscaperMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_DETAILS_KEY => $this->detailCollectionMock,
            ]
        );

        $this->assertEquals(true, $this->rate->hasDetailShippingDeliveryLine());
    }

    /**
     * Test method hasDetailFreeShipping
     *
     * @return void
     */
    public function testHasDetailFreeShipping(): void
    {
        $this->detailMock->expects($this->once())
            ->method('hasShippingDeliveryLines')
            ->willReturn(true);

        $this->detailMock->expects($this->once())
            ->method('hasFreeShipping')
            ->willReturn(true);

        $this->detailCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->detailMock);

        $this->rate = new Rate(
            $this->rateDetailCollectionMock,
            $this->priceEscaperMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_DETAILS_KEY => $this->detailCollectionMock,
            ]
        );

        $this->assertEquals(true, $this->rate->hasDetailFreeShipping());
    }

    /**
     * Test method testHasDetailShippingDeliveryDiscount
     *
     * @return void
     */
    public function testHasDetailShippingDeliveryDiscount(): void
    {
        $this->detailMock->expects($this->once())
            ->method('hasShippingDeliveryLineDiscount')
            ->willReturn(true);

        $this->detailCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->detailMock);

        $this->rate = new Rate(
            $this->rateDetailCollectionMock,
            $this->priceEscaperMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_DETAILS_KEY => $this->detailCollectionMock,
            ]
        );

        $this->assertEquals(true, $this->rate->hasDetailShippingDeliveryDiscount());
    }

    /**
     * Test method hasDetailCouponDiscounts
     *
     * @return void
     */
    public function testHasDetailCouponDiscounts(): void
    {
        $this->detailMock->expects($this->once())
            ->method('hasCouponDiscounts')
            ->willReturn(true);

        $this->detailCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->detailMock);

        $this->rate = new Rate(
            $this->rateDetailCollectionMock,
            $this->priceEscaperMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_DETAILS_KEY => $this->detailCollectionMock,
            ]
        );

        $this->assertEquals(true, $this->rate->hasDetailCouponDiscounts());
    }

    /**
     * Test method hasFreeGroundShipping
     *
     * @return void
     */
    public function testHasFreeGroundShipping(): void
    {
        $this->detailMock->expects($this->once())
            ->method('hasFreeGroundShipping')
            ->willReturn(true);

        $this->detailCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->detailMock);

        $this->rate = new Rate(
            $this->rateDetailCollectionMock,
            $this->priceEscaperMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_DETAILS_KEY => $this->detailCollectionMock,
            ]
        );

        $this->assertEquals(true, $this->rate->hasFreeGroundShipping());
    }

    /**
     * Test method isDetailCouponDiscountSameAsShippingDeliveryLineDiscount
     *
     * @return void
     */
    public function testIsDetailCouponDiscountSameAsShippingDeliveryLineDiscount(): void
    {
        $totalDiscountAmount = '($9.99)';
        $escapedDiscountAmount = 9.99;
        $this->detailMock->expects($this->any())
            ->method('getTotalDiscountAmount')
            ->willReturn($totalDiscountAmount);

        $this->priceEscaperMock->expects($this->any())
            ->method('escape')
            ->willReturn($escapedDiscountAmount);

        $this->detailMock->expects($this->any())
            ->method('compareShippingDeliveryLineDiscounts')
            ->with($escapedDiscountAmount)
            ->willReturn(true);
    
        $this->detailCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->detailMock);

        $this->detailMock->expects($this->any())
            ->method('getItemByType')
            ->willReturn($this->rateDeliveryLineInterface);

        $this->assertEquals(9.99, $this->rate->isDetailCouponDiscountSameAsShippingDeliveryLineDiscount());
    }
}
