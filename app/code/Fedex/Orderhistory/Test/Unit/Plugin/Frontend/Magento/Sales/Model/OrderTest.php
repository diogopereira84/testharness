<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\Sales\Model;

use Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Model\Order;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Sales\Model\Order as OrderModel;
use Fedex\SelfReg\Helper\SelfReg;

class OrderTest extends \PHPUnit\Framework\TestCase
{
   /**
     * @var (\Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Model\Order & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderPluginMock;
    protected $selfRegHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
    * @var \Fedex\Orderhistory\Helper\Data $helper
    */
    protected $helper;

    /**
     * @var \Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Model\Order $order
     */
    protected $order;

    /**
     * @var OrderModel $orderModel
     */
    protected $orderModel;
    
    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
                                        ->setMethods(['isModuleEnabled'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->orderModel = $this->getMockBuilder(OrderModel::class)
                                        ->setMethods(['getExtOrderId', 'getIncrementId'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->orderPluginMock = $this->getMockBuilder(Order::class)
                                        ->setMethods(['afterGetRealOrderId'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
                                        ->setMethods(['isSelfRegCustomer'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->order = $this->objectManager->getObject(
            Order::class,
            [
                                'helper' => $this->helper,
                                'selfRegHelper' => $this->selfRegHelperMock
                            ]
        );
    }

    /**
     * The test itself, every test function must start with 'test'
     */
    public function testAfterGetRealOrderId()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $this->assertEquals(null, $this->order->afterGetRealOrderId($this->orderModel, ''));
    }
}
