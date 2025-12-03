<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\Base\Api\PriceEscaperInterface;
use Fedex\FXOPricing\Api\Data\RateInterfaceFactory;
use Fedex\FXOPricing\Api\RateDetailBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDetailInterface;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Collection as DeliveryLineCollection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\Discount\Collection as DiscountLineCollection;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate;
use Fedex\FXOPricing\Api\RateBuilderInterface;
use Fedex\FXOPricing\Model\Data\Rate\Detail\Collection as DetailCollection;
use Fedex\FXOPricing\Model\Data\RateFactory;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate\Detail;
use Fedex\FXOPricing\Model\Rate\DetailBuilder;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\FXOPricing\Model\RateBuilder;
use PHPUnit\Framework\TestCase;

class RateBuilderTest extends TestCase
{
    protected $priceEscaperMock;
    private const RATE_DATA = [
        'currency' => 'USD',
        'rateDetails' => [
            [
                'grossAmount' => '$132.39',
                'totalDiscountAmount' => '($9.99)',
                'netAmount' => '$122.40',
                'taxableAmount' => '$122.40',
                'taxAmount' => '$10.10',
                'totalAmount' => '$132.50',
                'estimatedVsActual' => 'ACTUAL',
                'productLines' => [
                    [
                        'instanceId' => '90807',
                        'productId' => '1456773326927',
                        'unitQuantity' => 180,
                        'priceable' => true,
                        'unitOfMeasurement' => 'EACH',
                        'productRetailPrice' => '$122.40',
                        'productDiscountAmount' => '$0.00',
                        'productLinePrice' => '$122.40',
                        'productLineDetails' =>
                            [
                                [
                                    'detailCode' => '0224',
                                    'priceRequired' => false,
                                    'priceOverridable' => false,
                                    'description' => 'CLR 1S on 32# Wht',
                                    'unitQuantity' => 180,
                                    'quantity' => 180,
                                    'detailPrice' => '$122.40',
                                    'detailDiscountPrice' => '$0.00',
                                    'detailUnitPrice' => '$0.6800',
                                    'detailDiscountedUnitPrice' => '$0.0000',
                                    'detailCategory' => 'PRINTING',
                                ],
                            ],
                        'name' => 'Multi Sheet',
                        'userProductName' => 'foto',
                        'type' => 'PRINT_ORDER',
                    ],
                ],
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
            ],
        ],
    ];
    private MockObject|EntityFactoryInterface $entityFactoryMock;
    private MockObject|RateDetailCollectionInterfaceFactory $detailCollectionFactoryMock;
    private MockObject|RateDeliveryLineCollectionInterfaceFactory $deliveryLineCollectionFactoryMock;
    private MockObject|RateDiscountCollectionInterfaceFactory $discountCollectionFactoryMock;
    private MockObject|RateFactory $rateFactoryMock;
    private MockObject|DetailBuilder $detailBuilder;
    private RateBuilder $rateBuilder;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->priceEscaperMock = $this->getMockForAbstractClass(PriceEscaperInterface::class);
        $this->detailBuilder = $this->createMock(DetailBuilder::class);
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
            ->willReturn(new DiscountLineCollection($this->entityFactoryMock));

        $this->detailCollectionFactoryMock = $this->getMockBuilder(RateDetailCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->detailCollectionFactoryMock->method('create')
            ->willReturn(new DetailCollection($this->entityFactoryMock));
        $this->rateFactoryMock = $this->getMockBuilder(RateInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateFactoryMock->method('create')
            ->willReturn(new Rate(
                $this->detailCollectionFactoryMock,
                $this->priceEscaperMock
            ));
        $this->rateBuilder = new RateBuilder(
            $this->rateFactoryMock,
            $this->detailBuilder,
            $this->detailCollectionFactoryMock,
        );
    }

    /**
     * Test method build
     *
     * @return void
     */
    public function testBuild(): void
    {
        $this->detailBuilder->expects($this->once())
            ->method('build')
            ->willReturn(new Detail(
                $this->deliveryLineCollectionFactoryMock,
                $this->discountCollectionFactoryMock,
                $this->priceEscaperMock
            ));
        $rate = $this->rateBuilder->build(self::RATE_DATA);
        $this->assertEquals(array_keys(self::RATE_DATA), array_keys($rate->toArray()));
        $this->assertEquals(self::RATE_DATA['currency'], $rate->getCurrency());
        $this->assertInstanceOf(RateDetailCollectionInterface::class, $rate->getDetails());
    }

}
