<?php
namespace Fedex\OptimizeProductinstance\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Magento\Framework\Phrase;

class OptimizeItemInstanceHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $optimizeItemInstanceHelper;
    /**
     * @var PublisherInterface $publisher
     */
    protected $publisher;

    /**
     * @var OptimizeInstanceMessageInterface $message
     */
    protected $message;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->context = $this->getMockBuilder(Context::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();

        $this->publisher  = $this->getMockBuilder(PublisherInterface::class)
        ->setMethods(['publish'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->message  = $this->getMockBuilder(OptimizeInstanceMessageInterface::class)
        ->setMethods(['setMessage'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
        ->setMethods(['error', 'info'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->optimizeItemInstanceHelper    = $this->objectManager->getObject(
            OptimizeItemInstanceHelper::class,
            [
                'context'               => $this->context,
                'publisher'             => $this->publisher,
                'message'               => $this->message,
                '_logger'            => $this->loggerMock
            ]
        );
    }

    /**
     * Test pushQuoteIdQueue.
     *
     * @return bool
     */
    public function testPushQuoteIdQueue()
    {
        $quoteId = 123;
        $this->message->expects($this->any())
        ->method('setMessage')
        ->willReturnSelf();
        $this->publisher->expects($this->any())
        ->method('publish')
        ->willReturnSelf();
        $this->assertEquals(true, $this->optimizeItemInstanceHelper->pushQuoteIdQueue($quoteId));
    } 

    /**
     * Test pushQuoteIdQueue.
     *
     * @return null
     */
    public function testPushQuoteIdQueueWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception();
        $quoteId = 123;
        $this->message->expects($this->any())
        ->method('setMessage')
        ->willThrowException($exception);
        $this->assertEquals(null, $this->optimizeItemInstanceHelper->pushQuoteIdQueue($quoteId));
    } 

    /**
     * Test pushTempQuoteCompressionIdQueue.
     *
     * @return bool
     */
    public function testPushTempQuoteCompressionIdQueue()
    {
        $id = 123;
        $this->message->expects($this->any())
        ->method('setMessage')
        ->willReturnSelf();
        $this->publisher->expects($this->any())
        ->method('publish')
        ->willReturnSelf();
        $this->assertEquals(true, $this->optimizeItemInstanceHelper->pushTempQuoteCompressionIdQueue($id));
    } 

    /**
     * Test pushTempQuoteCompressionIdQueue.
     *
     * @return null
     */
    public function testPushTempQuoteCompressionIdQueueWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception();
        $id = 123;
        $this->message->expects($this->any())
        ->method('setMessage')
        ->willThrowException($exception);
        $this->assertEquals(null, $this->optimizeItemInstanceHelper->pushTempQuoteCompressionIdQueue($id));
    } 

    /**
     * Test pushTempOrderCompressionIdQueue.
     *
     * @return bool
     */
    public function testPushTempOrderCompressionIdQueue()
    {
        $id = 123;
        $this->message->expects($this->any())
        ->method('setMessage')
        ->willReturnSelf();
        $this->publisher->expects($this->any())
        ->method('publish')
        ->willReturnSelf();
        $this->assertEquals(true, $this->optimizeItemInstanceHelper->pushTempOrderCompressionIdQueue($id));
    } 

    /**
     * Test pushTempOrderCompressionIdQueue.
     *
     * @return null
     */
    public function testPushTempOrderCompressionIdQueueWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception();
        $id = 123;
        $this->message->expects($this->any())
        ->method('setMessage')
        ->willThrowException($exception);
        $this->assertEquals(null, $this->optimizeItemInstanceHelper->pushTempOrderCompressionIdQueue($id));
    }
    
}
