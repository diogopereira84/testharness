<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\FXOPricing\Api\Data\RateQuoteInterfaceFactory;
use Fedex\FXOPricing\Api\RateQuoteDetailBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailInterface;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Collection as DeliveryLineCollection;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Discount\Collection as DiscountLineCollection;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\RateQuote;
use Fedex\FXOPricing\Api\RateQuoteBuilderInterface;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Collection as DetailCollection;
use Fedex\FXOPricing\Model\Data\RateQuoteFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail;
use Fedex\FXOPricing\Model\RateQuote\DetailBuilder;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\FXOPricing\Model\RateQuoteBuilder;
use PHPUnit\Framework\TestCase;

class RateQuoteBuilderTest extends TestCase
{
    private const RATE_DATA = [
        'currency' => 'USD',
        'rateQuoteDetails' =>
            [
                [
                    'grossAmount' => 132.39,
                    'totalDiscountAmount' => 9.99,
                    'netAmount' => 122.4,
                    'taxableAmount' => 122.4,
                    'taxAmount' => 10.1,
                    'totalAmount' => 132.5,
                    'estimatedVsActual' => 'ACTUAL',
                    'productLines' =>
                        [
                            [
                                'instanceId' => '90807',
                                'productId' => '1456773326927',
                                'unitQuantity' => 180,
                                'priceable' => true,
                                'unitOfMeasurement' => 'EACH',
                                'productRetailPrice' => 122.4,
                                'productDiscountAmount' => 0.0,
                                'productLinePrice' => 122.4,
                                'productLineDiscounts' =>
                                    [
                                    ],
                                'productLineDetails' =>
                                    [
                                        [
                                            'detailCode' => '0224',
                                            'priceRequired' => false,
                                            'priceOverridable' => false,
                                            'description' => 'CLR 1S on 32# Wht',
                                            'unitQuantity' => 180,
                                            'quantity' => 180,
                                            'detailPrice' => 122.4,
                                            'detailDiscountPrice' => 0.0,
                                            'detailUnitPrice' => 0.68,
                                            'detailDiscountedUnitPrice' => 0.0,
                                            'detailDiscounts' =>
                                                [
                                                ],
                                            'detailCategory' => 'PRINTING',
                                        ],
                                    ],
                                'name' => 'Custom Multi Sheet',
                                'userProductName' => 'Custom Multi Sheet',
                                'type' => 'PRINT_ORDER',
                            ],
                        ],
                    'deliveryLines' =>
                        [
                            [
                                'recipientReference' => '1',
                                'priceable' => true,
                                'deliveryLinePrice' => 0,
                                'deliveryRetailPrice' => 0,
                                'deliveryLineType' => 'PACKING_AND_HANDLING',
                                'deliveryDiscountAmount' => 0,
                            ],
                            [
                                'recipientReference' => '1',
                                'estimatedDeliveryLocalTime' => '2023-06-22T23:59:00',
                                'estimatedShipDate' => '2023-07-13',
                                'priceable' => false,
                                'deliveryLinePrice' => 0.0,
                                'deliveryRetailPrice' => 9.99,
                                'deliveryLineType' => 'SHIPPING',
                                'deliveryDiscountAmount' => 9.99,
                                'deliveryLineDiscounts' =>
                                    [
                                        [
                                            'type' => 'COUPON',
                                            'amount' => 9.99,
                                        ],
                                    ],
                            ],
                        ],
                    'discounts' =>
                        [
                            [
                                'type' => 'COUPON',
                                'amount' => 9.99,
                            ],
                            [
                                'type' => 'COUPON',
                                'amount' => 10,
                            ],
                        ],
                ],
            ],
    ];
    private MockObject|EntityFactoryInterface $entityFactoryMock;
    private MockObject|RateQuoteDetailCollectionInterfaceFactory $detailCollectionFactoryMock;
    private MockObject|RateQuoteDeliveryLineCollectionInterfaceFactory $deliveryLineCollectionFactoryMock;
    private MockObject|RateQuoteDiscountCollectionInterfaceFactory $discountCollectionFactoryMock;
    private MockObject|RateQuoteFactory $rateFactoryMock;
    private MockObject|DetailBuilder $detailBuilder;
    private RateQuoteBuilder $rateBuilder;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->detailBuilder = $this->createMock(DetailBuilder::class);
        $this->deliveryLineCollectionFactoryMock = $this->getMockBuilder(RateQuoteDeliveryLineCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineCollectionFactoryMock
            ->method('create')
            ->willReturn(new DeliveryLineCollection($this->entityFactoryMock));

        $this->discountCollectionFactoryMock = $this->getMockBuilder(RateQuoteDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountCollectionFactoryMock
            ->method('create')
            ->willReturn(new DiscountLineCollection($this->entityFactoryMock));

        $this->detailCollectionFactoryMock = $this->getMockBuilder(RateQuoteDetailCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->detailCollectionFactoryMock->method('create')
            ->willReturn(new DetailCollection($this->entityFactoryMock));
        $this->rateFactoryMock = $this->getMockBuilder(RateQuoteInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateFactoryMock->method('create')
            ->willReturn(new RateQuote($this->detailCollectionFactoryMock));
        $this->rateBuilder = new RateQuoteBuilder(
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
            ));
        $rate = $this->rateBuilder->build(self::RATE_DATA);
        foreach (array_keys(self::RATE_DATA) as $key) {
            $this->assertTrue(in_array($key, array_keys($rate->toArray())), 'Failed =>' . $key);
        }
        $this->assertEquals(self::RATE_DATA['currency'], $rate->getCurrency());
        $this->assertInstanceOf(RateQuoteDetailCollectionInterface::class, $rate->getDetails());
    }

}
