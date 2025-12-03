<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Block;

use Fedex\OrderApprovalB2b\Block\ReviewOrder;
use Fedex\OrderApprovalB2b\Model\OrderHistory\GetAllOrders;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ReviewOrderTest extends TestCase
{
    protected $layout;
    protected $reviewBlockMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var GetAllOrders $getAllOrders
     */
    protected $getAllOrders;

    /**
     * @var ReviewOrder $reviewOrder
     */
    protected $reviewOrder;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->getAllOrders = $this->getMockBuilder(GetAllOrders::class)
            ->setMethods(['getAllOrderHirory'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getLayout',
                'createBlock',
                'setCollection',
                'setChild',
                'getUrl'])
            ->getMockForAbstractClass();

        $this->reviewBlockMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setCollection',
                    'setChild'
                ]
            )
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->reviewOrder = $this->objectManager->getObject(
            ReviewOrder::class,
            [
                'getAllOrders' => $this->getAllOrders,
                '_layout' => $this->layout
            ]
        );
    }

    /**
     * Test _prepareLayout
     *
     * @return string
     */
    public function testPrepareLayout()
    {
        $data['items'] = [
            0 => [
                ['key' => 'value'],
                ['quote_name' => 'name_1']
            ],
            1 => [
                ['key' => 'value'],
                ['quote_name' => 'name_1']
            ],
            2 => [
                ['key' => 'value'],
                ['quote_name' => 'name_1']
            ],
        ];
        
        $this->layout->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->layout->expects($this->any())
            ->method('createBlock')
            ->willReturn($this->reviewBlockMock);
        $this->getAllOrders->method('getAllOrderHirory')->willReturn($data);

        $this->layout->expects($this->any())->method('setChild')->willReturnSelf();

        $testMethod = new \ReflectionMethod(
            ReviewOrder::class,
            '_prepareLayout'
        );
        $expectedResult = $testMethod->invoke($this->reviewOrder);

        $this->assertNotEquals('', $expectedResult);
    }
}
