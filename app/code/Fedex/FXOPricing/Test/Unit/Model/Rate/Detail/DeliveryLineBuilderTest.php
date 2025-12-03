<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Rate\Detail;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount\Collection as DeliveryLineDiscountCollection;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount;
use Fedex\FXOPricing\Model\Rate\Detail\DeliveryLine\DiscountBuilder;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\FXOPricing\Model\Rate\Detail\DeliveryLineBuilder;
use PHPUnit\Framework\TestCase;

class DeliveryLineBuilderTest extends TestCase
{
    protected $entityFactoryMock;
    protected $deliveryLineDiscountCollectionFactoryMock;
    protected $deliveryLineFactoryMock;
    /**
     * Delivery line data
     */
    private const DELIVERY_LINE_DATA = [
        'recipientReference' => '1',
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
    ];

    /**
     * @var MockObject|DiscountBuilder
     */
    private MockObject|DiscountBuilder $discountBuilderMock;

    /**
     * @var DeliveryLineBuilder
     */
    private DeliveryLineBuilder $deliveryLineBuilder;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->discountBuilderMock = $this->createMock(DiscountBuilder::class);
        $this->deliveryLineDiscountCollectionFactoryMock = $this->getMockBuilder(RateDeliveryLineDiscountCollectionInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineDiscountCollectionFactoryMock
            ->method('create')
            ->willReturn(new DeliveryLineDiscountCollection($this->entityFactoryMock));

        $this->deliveryLineFactoryMock = $this->getMockBuilder(RateDeliveryLineInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryLineFactoryMock->method('create')
            ->willReturn(new DeliveryLine($this->deliveryLineDiscountCollectionFactoryMock));
        $this->deliveryLineBuilder = new DeliveryLineBuilder(
            $this->deliveryLineDiscountCollectionFactoryMock,
            $this->deliveryLineFactoryMock,
            $this->discountBuilderMock,
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
        $deliveryLine = $this->deliveryLineBuilder->build(self::DELIVERY_LINE_DATA);
        foreach (array_keys(self::DELIVERY_LINE_DATA) as $key) {
            $this->assertTrue(in_array($key, array_keys($deliveryLine->toArray())), 'Failed =>' . $key);
        }
        $this->assertEquals(self::DELIVERY_LINE_DATA['deliveryDiscountAmount'], $deliveryLine->getDeliveryDiscountAmount());
        $this->assertEquals(self::DELIVERY_LINE_DATA['deliveryLinePrice'], $deliveryLine->getDeliveryLinePrice());
        $this->assertEquals(self::DELIVERY_LINE_DATA['deliveryLineType'], $deliveryLine->getDeliveryLineType());
        $this->assertEquals(self::DELIVERY_LINE_DATA['deliveryRetailPrice'], $deliveryLine->getDeliveryRetailPrice());
        $this->assertEquals(self::DELIVERY_LINE_DATA['estimatedDeliveryLocalTime'], $deliveryLine->getEstimatedDeliveryLocalTime());
        $this->assertEquals(self::DELIVERY_LINE_DATA['estimatedShipDate'], $deliveryLine->getEstimatedShipDate());
        $this->assertEquals(self::DELIVERY_LINE_DATA['priceable'], $deliveryLine->getPriceable());
        $this->assertEquals(self::DELIVERY_LINE_DATA['recipientReference'], $deliveryLine->getRecipientReference());
        $this->assertInstanceOf(RateDeliveryLineDiscountCollectionInterface::class, $deliveryLine->getDeliveryLineDiscounts());
    }

}
