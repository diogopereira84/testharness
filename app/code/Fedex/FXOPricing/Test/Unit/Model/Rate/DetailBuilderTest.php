<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Rate;

use Fedex\Base\Api\PriceEscaperInterface;
use Fedex\FXOPricing\Api\Data\RateDetailInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterface;
use Fedex\FXOPricing\Api\RateDeliveryLineBuilderInterface;
use Fedex\FXOPricing\Api\RateDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate\Detail;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Collection as DeliveryLineCollection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\Discount\Collection as DiscountCollection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\Rate\Detail\Discount;
use Fedex\FXOPricing\Model\Rate\Detail\DeliveryLine\DiscountBuilder;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\FXOPricing\Model\Rate\DetailBuilder;
use PHPUnit\Framework\TestCase;

class DetailBuilderTest extends TestCase
{
    protected $entityFactoryMock;
    protected $priceEscaperMock;
    protected $deliveryLineCollectionFactoryMock;
    protected $discountCollectionFactoryMock;
    protected $detailFactoryMock;
    protected $deliveryLineBuilderMock;
    protected $deliveryLineMock;
    /**
     * Delivery line data as array
     */
    private const DELIVERY_LINE_DATA = [
        'grossAmount' => '$132.39',
        'totalDiscountAmount' => '($9.99)',
        'netAmount' => '$122.40',
        'taxableAmount' => '$122.40',
        'taxAmount' => '$10.10',
        'totalAmount' => '$132.50',
        'estimatedVsActual' => 'ACTUAL',
        'deliveryLines' => [
            [
                'recipientReference' => '1',
                'estimatedDeliveryDuration' =>
                    [
                        'value' => 1,
                        'unit' => 'BUSINESSDAYS',
                    ],
                'estimatedDeliveryLocalTime' => '2023-06-22T23:59:00',
                'estimatedShipDate' => '2023-07-13',
                'priceable' => true,
                'deliveryLinePrice' => '$0.00',
                'deliveryRetailPrice' => '$9.99',
                'deliveryLineType' => 'SHIPPING',
                'deliveryDiscountAmount' => '($9.99)',
                'deliveryLineDiscounts' =>
                    [
                        0 =>
                            [
                                'type' => 'COUPON',
                                'amount' => '($9.99)',
                            ],
                    ],
                'shipmentDetails' =>
                    [
                        'address' =>
                            [
                                'streetLines' =>
                                    [
                                        0 => 'Line one',
                                        1 => 'Line two',
                                    ],
                                'city' => 'Plano',
                                'stateOrProvinceCode' => 'TX',
                                'postalCode' => '75024',
                                'countryCode' => 'US',
                                'addressClassification' => 'BUSINESS',
                            ],
                        'serviceType' => 'GROUND_US',
                    ],
            ],
            [
                'recipientReference' => '1',
                'priceable' => true,
                'deliveryLinePrice' => '$0.00',
                'deliveryRetailPrice' => '$0.00',
                'deliveryLineType' => 'PACKING_AND_HANDLING',
                'deliveryDiscountAmount' => '$0.00',
            ],
        ],
        'discounts' => [
            [
                'type' => 'COUPON',
                'amount' => '($9.99)',
            ],
        ],
    ];

    /**
     * @var MockObject|DiscountBuilder
     */
    private MockObject|DiscountBuilder $discountBuilderMock;

    /**
     * @var DetailBuilder
     */
    private DetailBuilder $detailBuilder;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->priceEscaperMock = $this->getMockForAbstractClass(PriceEscaperInterface::class);
        $this->deliveryLineCollectionFactoryMock = $this->getMockBuilder(RateDeliveryLineCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineCollectionFactoryMock
            ->method('create')
            ->willReturn(new DeliveryLineCollection($this->entityFactoryMock));
        $this->discountCollectionFactoryMock = $this->getMockBuilder(RateDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollectionFactoryMock
            ->method('create')
            ->willReturn(new DiscountCollection($this->entityFactoryMock));
        $this->detailFactoryMock = $this->getMockBuilder(RateDetailInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->detailFactoryMock->method('create')
            ->willReturn(new Detail(
                $this->deliveryLineCollectionFactoryMock,
                $this->discountCollectionFactoryMock,
                $this->priceEscaperMock
            ));
        $this->deliveryLineBuilderMock = $this->getMockForAbstractClass(RateDeliveryLineBuilderInterface::class);
        $this->deliveryLineMock = $this->createMock(DeliveryLine::class);
        $this->discountBuilderMock = $this->getMockForAbstractClass(RateDiscountBuilderInterface::class);

        $this->detailBuilder = new DetailBuilder(
            $this->deliveryLineCollectionFactoryMock,
            $this->discountCollectionFactoryMock,
            $this->detailFactoryMock,
            $this->deliveryLineBuilderMock,
            $this->discountBuilderMock
        );
    }

    /**
     * Test method build
     *
     * @return void
     */
    public function testBuild(): void
    {
        $this->discountBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn(new Discount());
        $this->deliveryLineBuilderMock->method('build')->willReturn($this->deliveryLineMock);

        $detail = $this->detailBuilder->build(self::DELIVERY_LINE_DATA);

        foreach (array_keys(self::DELIVERY_LINE_DATA) as $key) {
            $this->assertTrue(in_array($key, array_keys($detail->toArray())), 'Failed =>' . $key);
        }
        $this->assertInstanceOf(RateDeliveryLineCollectionInterface::class, $detail->getDeliveryLines());
        $this->assertInstanceOf(RateDiscountCollectionInterface::class, $detail->getDiscounts());
        $this->assertEquals(self::DELIVERY_LINE_DATA['estimatedVsActual'], $detail->getEstimatedVsActual());
        $this->assertEquals(self::DELIVERY_LINE_DATA['grossAmount'], $detail->getGrossAmount());
        $this->assertEquals(self::DELIVERY_LINE_DATA['netAmount'], $detail->getNetAmount());
        $this->assertEquals(self::DELIVERY_LINE_DATA['taxableAmount'], $detail->getTaxableAmount());
        $this->assertEquals(self::DELIVERY_LINE_DATA['taxAmount'], $detail->getTaxAmount());
        $this->assertEquals(self::DELIVERY_LINE_DATA['totalAmount'], $detail->getTotalAmount());
        $this->assertEquals(self::DELIVERY_LINE_DATA['totalDiscountAmount'], $detail->getTotalDiscountAmount());
    }

}
