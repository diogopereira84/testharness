<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Controller\Index;

use Fedex\OrderApprovalB2b\Controller\Index\SendOrderEmail;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;

/**
 * Test class for SendOrderEmail
 */
class SendOrderEmailTest extends TestCase
{
    /**
     * @var (\Fedex\OrderApprovalB2b\Helper\OrderEmailHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderEmailHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sendOrderEmail;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var OrderEmailHelper|MockObject
     */
    protected $orderEmailHelper;

    /**
     * @var RequestInterface $requestMock
     */
    protected $requestMock;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->orderEmailHelperMock = $this->getMockBuilder(OrderEmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'sendOrderGenericEmail',
            ])->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->sendOrderEmail = $this->objectManager->getObject(
            SendOrderEmail::class,
            [
                'orderEmailHelperMock' => $this->orderEmailHelperMock
            ]
        );
    }

    /**
     * Test method for Execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn('confirmed');
        $this->requestMock->expects($this->any())->method('getParam')->willReturn('1');

        $this->assertNull($this->sendOrderEmail->execute());
    }
}
