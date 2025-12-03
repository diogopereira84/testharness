<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Controller\EproCustomer;

use Fedex\SelfReg\Controller\EproCustomer\OrderCount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Fedex\SelfReg\Model\EproCustomer\OrderHistory;

class OrderCountTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $orderHistoryMock;
    protected $resultFactory;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManagerInstance;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderCountMock;
    /**
     * setUp function
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderHistoryMock = $this->getMockBuilder(OrderHistory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrderCountForHomepage'])
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();

        $this->objectManager = new ObjectManager($this);

        $this->orderCountMock = $this->objectManager->getObject(
            OrderCount::class,
            [
                'context' => $this->contextMock,
                'orderHistory' => $this->orderHistoryMock,
                'resultFactory' => $this->resultFactory,
            ]
        );
    }

    /**
     * Function testExecute
     */
    public function testExecute()
    {
        $orderCountData = ['submitted' => '0', 'quote' => '0', 'progress' => '0', 'completed' => '0'];
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($result);

        $this->orderHistoryMock->expects($this->any())
            ->method('getOrderCountForHomepage')
            ->willReturn($orderCountData);

        $this->assertEquals($result, $this->orderCountMock->execute());
    }
}
