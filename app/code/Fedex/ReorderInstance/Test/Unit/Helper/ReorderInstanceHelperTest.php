<?php

/**
 * Php file,Test case for ReorderInstanceHelper.
 *
 * @author  Ayush Anand <ayush.anand@infogain.com>
 * @license http://infogain.com Infogain License
 */
namespace Fedex\ReorderInstance\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\ReorderInstance\Api\ReorderMessageInterface;
use Psr\Log\LoggerInterface;
use Fedex\ReorderInstance\Helper\ReorderInstanceHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ReorderInstanceHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $reorderInstanceHelper;
    /**
     * @var PublisherInterface
     */
    protected $publisherMock;

    /**
     * @var ReorderManagerInterface
     */
    protected $messageMock;

    /**
     * @var LoggerInterface
     */
    protected $loggerMock;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->context           = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publisherMock           = $this->getMockBuilder(PublisherInterface::class)
            ->setMethods(['publish'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageMock = $this->getMockBuilder(ReorderMessageInterface::class)
            ->setMethods(['setMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->reorderInstanceHelper    = $this->objectManager->getObject(
            ReorderInstanceHelper::class,
            [
                'context'                         => $this->context,
                'message'                         => $this->messageMock,
                'publisher'                       => $this->publisherMock,
                'logger'                          => $this->loggerMock
            ]
        );
    }

    /**
     * Assert pushOrderIdInQueue.
     *
     * @return bool
     */
    public function testPushOrderIdInQueue()
    {
        $orderId = '6607';
        $this->assertTrue($this->reorderInstanceHelper->pushOrderIdInQueue($orderId));
    }

    /**
     * Assert pushOrderIdInQueue with exception
     */
    public function testPushOrderIdInQueueWithException()
    {
        $orderId = '6607';
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->messageMock->expects($this->any())->method('setMessage')
        ->with($orderId)
        ->willThrowException($exception);
        $this->assertNull($this->reorderInstanceHelper->pushOrderIdInQueue($orderId));
    }

    /**
     * Assert pushOrderIdInQueueForShipment.
     *
     * @return bool
     */
    public function testPushOrderIdInQueueForShipment()
    {
        $messageRequest = ['orderId' => 12, 'counter' => 0];

        $this->assertTrue(
            $this->reorderInstanceHelper->pushOrderIdInQueueForShipmentCreation(json_encode($messageRequest))
        );
    }

    /**
     * Assert PushOrderIdInQueueForShipment With Exception.
     *
     * @return bool
     */
    public function testPushOrderIdInQueueForShipmentWithException()
    {
        $messageRequest = ['orderId' => 12, 'counter' => 0];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->messageMock->expects($this->any())->method('setMessage')
            ->with(json_encode($messageRequest))
            ->willThrowException($exception);
        $this->assertFalse(
            $this->reorderInstanceHelper->pushOrderIdInQueueForShipmentCreation(json_encode($messageRequest))
        );
    }

}
