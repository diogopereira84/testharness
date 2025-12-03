<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\Rate\Detail\DeliveryLine\Discount;

use Exception;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount;
use Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine\Discount\Collection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var EntityFactoryInterface
     */
    private EntityFactoryInterface $entityFactoryMock;

    /**
     * @var RateDeliveryLineDiscountCollectionInterface
     */
    private RateDeliveryLineDiscountCollectionInterface $deliveryLineCollection;

    /**
     * @var RateDeliveryLineDiscountInterface
     */
    private RateDeliveryLineDiscountInterface $discount;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->discount = new Discount();
        $this->entityFactoryMock->method('create')->willReturn($this->discount);

        $this->deliveryLineCollection = new Collection($this->entityFactoryMock);
    }

    /**
     * Test method toArrayItems with items
     *
     * @return void
     * @throws Exception
     */
    public function testToArrayItemsWithItems(): void
    {
        $this->deliveryLineCollection->addItem($this->discount);
        $this->deliveryLineCollection->addItem($this->discount);
        $items = $this->deliveryLineCollection->toArrayItems();
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
    }

    /**
     * Test method toArrayItems without items
     *
     * @return void
     * @throws Exception
     */
    public function testToArrayItemsWithoutItems(): void
    {
        $items = $this->deliveryLineCollection->toArrayItems();
        $this->assertIsArray($items);
        $this->assertCount(0, $items);
    }
}
