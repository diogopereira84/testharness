<?php
namespace Fedex\TrackOrder\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Fedex\TrackOrder\Helper\OrderHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class OrderHelperTest extends TestCase
{
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $context = $this->createMock(Context::class);
        $this->orderHelper = $objectManager->getObject(OrderHelper::class, ['context' => $context]);
    }

    public function testSegregateOrderIds()
    {
        $orderIds = ['30101234', '40105678', '30107890', '50101234'];
        $expectedResult = [
            'apiOrders' => [30101234, 30107890],
            'magOrders' => ['40105678', '50101234'],
        ];

        $result = $this->orderHelper->segregateOrderIds($orderIds);

        $this->assertEquals($expectedResult, $result);
    }
}