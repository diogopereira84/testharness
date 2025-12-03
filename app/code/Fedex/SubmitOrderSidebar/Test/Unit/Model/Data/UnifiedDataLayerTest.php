<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\Data;

use Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayer;
use PHPUnit\Framework\TestCase;

class UnifiedDataLayerTest extends TestCase
{
    /**
     * @var UnifiedDataLayer
     */
    private UnifiedDataLayer $unifiedDataLayer;

    /**
     * Setup test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->unifiedDataLayer = new UnifiedDataLayer();
    }

    /**
     * Test method testSetDeliveriesLabel
     *
     * @return void
     */
    public function testSetDeliveriesLabel()
    {
        $this->unifiedDataLayer->setDeliveries([
            'key1' => 'value',
            'key2' => 'value',
            'key3' => 'value',
            'key4' => 'value',
        ]);

        $this->assertTrue(in_array('deliveries', array_keys($this->unifiedDataLayer->toArray())));
    }
}
