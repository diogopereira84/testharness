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
use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\RateQuote;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Collection as DetailCollection;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Collection as DeliveryLineCollection;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Discount\Collection as DeliveryLineDiscountCollection;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Discount\Collection as DiscountCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailInterface;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterface;

class RateQuoteTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterfaceFactory)
     */
    protected $rateDetailCollectionMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Fedex\FXOPricing\Model\ResourceModel\DeliveryLine\Collection
     * Mock object for the DeliveryLine collection used in unit tests.
     */
    protected $deliveryLineCollectionMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for the discount collection used in unit tests.
     */
    protected $discountCollectionMock;
    /**
     * @var (\Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $detailCollectionMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\DeliveryLine\Collection
     * Represents the collection of delivery lines used for testing rate quotes.
     */
    protected $deliveryLineCollection;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\Discount\Collection
     * Represents the collection of discount data used in the unit test.
     */
    protected $discountCollection;
    /**
     * @var mixed $details
     * This property is used to store details related to the test case.
     * The exact type and structure of the data stored in this property
     * depend on the specific implementation of the test.
     */
    protected $details;
    /**
     * @var mixed $detailCollection
     * A protected property to hold the detail collection.
     * This property is likely used to store data related to rate quotes
     * for testing purposes in the unit test class.
     */
    protected $detailCollection;
    /**
     * Mock object for the discount collection.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $discountCollMock;
    /**
     * @var \Fedex\FXOPricing\Model\ResourceModel\RateQuoteDiscount\Collection
     * Mock object for the RateQuoteDiscount collection used in unit tests.
     */
    protected $rateQuoteDiscountColl;
    /**
     * @var \Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface
     * Mock instance of the RateDeliveryLineInterface used for testing purposes.
     */
    protected $rateDeliveryLineInterface;
    private const CURRENCY_KEY = 'currency';
    private const CURRENCY_VALUE = 'USD';
    private const CURRENCY_VALUE_ALTERNATIVE = 'USD';
    /**
     * @var \Fedex\FXOPricing\Model\Data\RateQuote\Detail
     * Mock instance of the RateDeliveryLineInterface used for testing purposes.
     */
    private $detailsMock;
    /**
     * Details key
     */
    private const RATE_QUOTE_DETAILS = 'rateQuoteDetails';

    private const RATE_ARRAY = [
        'currency' => 'USD',
        'rateQuoteDetails' =>
            [
                [
                    'grossAmount' => 132.39,
                    'totalDiscountAmount' => 9.99,
                    'netAmount' => 122.40,
                    'taxableAmount' => 122.30,
                    'taxAmount' => 10.10,
                    'totalAmount' => 132.50,
                    'estimatedVsActual' => 'ACTUAL',
                    'deliveryLines' => [],
                    'discounts' => [],
                ],
            ],
    ];
    private const JSON_ENCODED = '{"currency":"USD",
    "rateQuoteDetails":[
    {
        "deliveryLines":[],
        "discounts":[],
        "estimatedVsActual":"ACTUAL",
        "grossAmount":132.39,
        "netAmount":122.4,
        "taxableAmount":122.3,
        "taxAmount":10.1,
        "totalAmount":132.5,
        "totalDiscountAmount":9.99
    }]}';
    private const GROSS_AMOUNT = 132.39;
    private const TOTAL_DISCOUNT_AMOUNT = 9.99;
    private const NET_AMOUNT = 122.40;
    private const TAXABLE_AMOUNT = 122.30;
    private const TAX_AMOUNT = 10.10;
    private const TOTAL_AMOUNT = 132.50;
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
     * @var RateQuote
     */
    private RateQuote $rate;

    /**
     * Setup tests
     *
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->rateQuoteDiscountColl = $this->getMockForAbstractClass(RateQuoteDiscountCollectionInterface::class);
        $this->rateDeliveryLineInterface = $this->getMockForAbstractClass(RateDeliveryLineInterface::class);
        $this->rateDetailCollectionMock = $this->getMockBuilder(RateQuoteDetailCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineCollectionMock =
        $this->getMockBuilder(RateQuoteDeliveryLineCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollectionMock = $this->getMockBuilder(RateQuoteDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->detailCollectionMock = $this->getMockBuilder(RateQuoteDetailCollectionInterface::class)
            ->setMethods([
                'getFirstItem',
                'count',
                'hasAnyDeliveryLineWithDiscount',
                'hasFreeGroundShipping',
                'getDiscounts'
                ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->detailsMock = $this->getMockBuilder(Detail::class)
            ->setMethods([
                'hasShippingDeliveryLines',
                'hasFreeShipping',
                'hasShippingDeliveryLineDiscount',
                'hasCouponDiscounts',
                'getDiscounts',
                'getItemByType'
                ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollMock = $this->getMockBuilder(DiscountCollection::class)
            ->setMethods(['getType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryLineCollection = $this->getMockBuilder(DeliveryLineCollection::class)
            ->setMethods(['create', 'getItemByType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollection = new DiscountCollection($this->entityFactoryMock);
        $this->deliveryLineCollectionMock
        ->expects($this->once())
        ->method('create')
        ->willReturn($this->deliveryLineCollection);
        $this->discountCollectionMock->expects($this->once())->method('create')->willReturn($this->discountCollection);
        $this->details = new Detail($this->deliveryLineCollectionMock, $this->discountCollectionMock, self::DETAILS);
        $this->detailCollection = new DetailCollection($this->entityFactoryMock);
        $this->detailCollection->addItem($this->details);
        $this->rate = new RateQuote(
            $this->rateDetailCollectionMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_QUOTE_DETAILS => $this->detailCollection,
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
    public function testGetDetails()
    {
        $this->assertInstanceOf(RateQuoteDetailCollectionInterface::class, $this->rate->getDetails());
        $this->assertEquals($this->detailCollection, $this->rate->getDetails());
    }

    /**
     * Test method setDetails
     *
     * @return void
     */
    public function testSetDetails(): void
    {
        $detailCollection = new DetailCollection($this->entityFactoryMock);
        $this->assertInstanceOf(RateQuoteDetailCollectionInterface::class, $this->rate->getDetails());
        $this->rate->setDetails($detailCollection);
        $this->assertNotEquals($this->detailCollection, $this->rate->getDetails());
        $this->assertEquals($detailCollection, $this->rate->getDetails());
    }

    /**
     * Test method testGetCurrency
     *
     * @return void
     */
    public function toJson(): void
    {
        $this->assertEquals(self::JSON_ENCODED, $this->rate->toJson());
    }

    /**
     * Test method toArray
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
        $rate = new RateQuote(
            $this->rateDetailCollectionMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_QUOTE_DETAILS => $this->detailCollectionMock
            ]
        );

        $this->detailCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->detailsMock);

        $this->detailsMock->expects($this->any())
            ->method('hasShippingDeliveryLines')
            ->willReturn(false);
        $this->assertEquals(false, $rate->hasDetailShippingDeliveryLine());
    }

    /**
     * Test method hasDetailFreeShipping
     *
     * @return void
     */
    public function testHasDetailFreeShipping(): void
    {
        $rate = new RateQuote(
            $this->rateDetailCollectionMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_QUOTE_DETAILS => $this->detailCollectionMock
            ]
        );

        $this->detailCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->detailsMock);

        $this->detailsMock->expects($this->any())
            ->method('hasShippingDeliveryLines')
            ->willReturn(true);

        $this->detailsMock->expects($this->any())
            ->method('hasFreeShipping')
            ->willReturn(true);
            
        $this->assertEquals(true, $rate->hasDetailFreeShipping());
    }

    /**
     * Test method hasDetailCouponDiscounts
     *
     * @return void
     */
    public function testHasDetailCouponDiscounts(): void
    {
        $rate = new RateQuote(
            $this->rateDetailCollectionMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_QUOTE_DETAILS => $this->detailCollectionMock
            ]
        );

        $this->detailCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->detailsMock);

        $this->detailsMock->expects($this->any())
            ->method('hasCouponDiscounts')
            ->willReturn(true);

        $this->assertEquals(true, $rate->hasDetailCouponDiscounts());
    }

    /**
     * Test method hasDetailShippingDeliveryDiscount
     *
     * @return void
     */
    public function testHasDetailShippingDeliveryDiscount(): void
    {
        $rate = new RateQuote(
            $this->rateDetailCollectionMock,
            [
                self::CURRENCY_KEY => self::CURRENCY_VALUE,
                self::RATE_QUOTE_DETAILS => $this->detailCollectionMock
            ]
        );
            
        $this->detailCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->detailsMock);

        $this->detailsMock->expects($this->once())
            ->method('hasShippingDeliveryLineDiscount')
            ->willReturn(true);

        $this->assertEquals(true, $rate->hasDetailShippingDeliveryDiscount());
    }
}
