<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Model;

use Fedex\OrderApprovalB2b\Model\OrderGrid;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for OrderGrid Model
 */
class OrderGridTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    /**
     * @var OrderGrid $orderGrid
     */
    private $orderGrid;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->orderGrid = $this->objectManagerHelper->getObject(OrderGrid::class);
    }

    /**
     * test consturct method
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
