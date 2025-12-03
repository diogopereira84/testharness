<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Controller\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\OrderApprovalB2b\Controller\Index\DeclineOrder;
use Fedex\OrderApprovalB2b\Helper\DeclineHelper;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Magento\Framework\Phrase;

/**
 * PHPUnit Test class for DeclineOrder
 */
class DeclineOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var DeclineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockDeclineHelper;

    /**
     * @var RevieworderHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRevieworderHelper;

    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockJsonFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockLogger;

    /**
     * @var DeclineOrder
     */
    protected $declineOrderController;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->mockDeclineHelper = $this->getMockBuilder(DeclineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRevieworderHelper = $this->getMockBuilder(RevieworderHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getRequest')->willReturn($this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock());

        $this->declineOrderController = $this->objectManager->getObject(
            DeclineOrder::class,
            [
                'context' => $context,
                'jsonFactory' => $this->mockJsonFactory,
                'declineHelper' => $this->mockDeclineHelper,
                'logger' => $this->mockLogger,
                'revieworderHelper' => $this->mockRevieworderHelper,
            ]
        );
    }

    /**
     * Test method for execute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->mockRevieworderHelper->expects($this->any())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);

        $this->mockRevieworderHelper->expects($this->any())
            ->method('sendResponseData')
            ->willReturn($this->mockJsonFactory);

        $this->mockDeclineHelper->expects($this->any())
            ->method('declinedOrder')
            ->willReturn([
                'success' => true,
                'msg' => 'Order is Declined successfully'
            ]);
        $result = $this->declineOrderController->execute();

        $this->assertNotEquals("You are not authorized to access this request.", $result);
    }

    /**
     * Test execute method with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new \Exception();
        
        $this->mockLogger->expects($this->any())
            ->method('error')
            ->willReturnSelf();
            
        $exception = new \Exception();

        $this->assertNull($this->declineOrderController->execute());
    }
}
