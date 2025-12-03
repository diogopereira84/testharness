<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\Alert;

use Magento\Framework\Data\Collection\EntityFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\Data\Alert;
use Fedex\FXOPricing\Model\Data\Alert\Collection;

class CollectionTest extends TestCase
{
    /**
     * Code key
     */
    private const CODE = 'code';

    /**
     * Message key
     */
    private const MESSAGE = 'message';

    /**
     * AlertType key
     */
    private const ALERT_TYPE = 'alertType';

    /**
     * Code value alternative
     */
    private const CODE_VALUE = 'COUPONS.CODE.INVALID';

    /**
     * Message value
     */
    private const MESSAGE_VALUE = 'Invalid Coupon please try again';

    /**
     * AlertType value
     */
    private const ALERT_TYPE_VALUE = 'WARNING';

    /**
     * @var MockObject|EntityFactoryInterface
     */
    private MockObject|EntityFactoryInterface $entityFactoryMock;

    /**
     * @var Alert
     */
    private Alert $alert;

    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $this->alert = new Alert([
            self::CODE => self::CODE_VALUE,
            self::MESSAGE => self::MESSAGE_VALUE,
            self::ALERT_TYPE => self::ALERT_TYPE_VALUE
        ]);
        $this->collection = new Collection($this->entityFactoryMock);
        $this->collection->addItem($this->alert);
    }

    /**
     * Test method getItemByCode
     *
     * @return void
     */
    public function testGetItemByCode(): void
    {
        $this->assertEquals(self::CODE_VALUE, $this->collection->getItemByCode(self::CODE_VALUE)->getCode());
    }

    /**
     * Test method hasAlerts
     *
     * @return void
     */
    public function testHasAlerts(): void
    {
        $this->assertTrue($this->collection->hasAlerts());
        $this->collection->clear();
        $this->assertFalse($this->collection->hasAlerts());
    }

    /**
     * Test method getCouponCodeInvalid
     *
     * @return void
     */
    public function testGetCouponCodeInvalid(): void
    {
        $this->assertEquals(self::CODE_VALUE, $this->collection->getCouponCodeInvalid()->getCode());
    }

    /**
     * Test method hasInvalidCouponCode
     *
     * @return void
     */
    public function testHasInvalidCouponCode(): void
    {
        $this->assertTrue($this->collection->hasInvalidCouponCode());
        $this->collection->clear();
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn(new Alert());
        $this->assertFalse($this->collection->hasInvalidCouponCode());
    }
}
