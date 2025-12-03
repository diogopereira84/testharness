<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\RateQuote\Detail\Discount\CollectionTest;

use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Discount\Collection;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RateQuoteDiscountInterface
     */
    private $discountMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EntityFactory
     */
    private $entityFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discountMock = $this->createMock(RateQuoteDiscountInterface::class);
        $this->entityFactoryMock = $this->getMockBuilder(EntityFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityFactoryMock->method('create')->willReturn($this->discountMock);
        $this->collection = new Collection($this->entityFactoryMock);
    }

    /**
     * Test the getItemByType method for existing type
     */
    public function testGetItemByTypeExisting()
    {
        $result = $this->collection->getItemByType('COUPON');
        $this->assertSame($this->discountMock, $result);
    }

    /**
     * Test the getItemByType method for non-existing type
     */
    public function testGetItemByTypeNonExisting()
    {
        $result = $this->collection->getItemByType('NON_EXISTING');
        $this->assertInstanceOf(RateQuoteDiscountInterface::class, $result);
    }

    /**
     * Test the getCouponDiscount method
     */
    public function testGetCouponDiscount()
    {
        $result = $this->collection->getCouponDiscount();
        $this->assertSame($this->discountMock, $result);
    }

    /**
     * Test the getArCustomersDiscount method
     */
    public function testGetArCustomersDiscount()
    {
        $this->discountMock->method('getType')->willReturn('AR_CUSTOMERS');
        $result = $this->collection->getArCustomersDiscount();
        $this->assertSame($this->discountMock, $result);
    }

    /**
     * Test the hasCouponDiscount method when coupon discount exists
     */
    public function testHasCouponDiscountTrue()
    {
        $this->discountMock->method('getType')->willReturn('COUPON');
        $result = $this->collection->hasCouponDiscount();
        $this->assertTrue($result);
    }

    /**
     * Test the hasCouponDiscount method when coupon discount does not exist
     */
    public function testHasCouponDiscountFalse()
    {
        $result = $this->collection->hasCouponDiscount();
        $this->assertFalse($result);
    }

    /**
     * Test the hasArCustomersDiscount method when AR_CUSTOMERS discount exists
     */
    public function testHasArCustomersDiscountTrue()
    {
        $this->discountMock->method('getType')->willReturn('AR_CUSTOMERS');
        $result = $this->collection->hasArCustomersDiscount();
        $this->assertTrue($result);
    }

    /**
     * Test the hasArCustomersDiscount method when AR_CUSTOMERS discount does not exist
     */
    public function testHasArCustomersDiscountFalse()
    {
        $result = $this->collection->hasArCustomersDiscount();
        $this->assertFalse($result);
    }
}
