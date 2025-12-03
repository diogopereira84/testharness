<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

namespace Fedex\OrdersCleanup\Test\Unit\Cron;

use Fedex\OrdersCleanup\Cron\RemoveOrders;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class RemoveOrdersTest extends TestCase
{

    protected $removeOrdersHelperMock;
    protected $objectManager;
    protected $removeOrdersMock;
    protected function setUp(): void
    {
        $this->removeOrdersHelperMock = $this->getMockBuilder(\Fedex\OrdersCleanup\Helper\RemoveOrders::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->objectManager = new ObjectManager($this);
        $this->removeOrdersMock = $this->objectManager->getObject(
            RemoveOrders::class,
            [
                'removeOrdersHelper' => $this->removeOrdersHelperMock
            ]
        );
    }

    public function testExecuteError()
    {
        $this->removeOrdersHelperMock->method('removeOrders')->willThrowException(new \Exception());
        $this->assertEmpty($this->removeOrdersMock->execute());
    }
}
