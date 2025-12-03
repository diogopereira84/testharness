<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\Data\UnifiedDataLayer;

use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayer\Delivery;

class DeliveryTest extends TestCase
{
    /**
     * @var Delivery
     */
    private Delivery $delivery;

    /**
     * Setup test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->delivery = new Delivery();
    }

    /**
     * Test method setLineItemsLabel
     *
     * @return void
     */
    public function testSetLineItemsLabel()
    {
        $this->delivery->setLineItems([
            'key1' => 'value',
            'key2' => 'value',
            'key3' => 'value',
            'key4' => 'value',
        ]);

        $this->assertTrue(in_array('lineItems', array_keys($this->delivery->toArray())));
    }
}
