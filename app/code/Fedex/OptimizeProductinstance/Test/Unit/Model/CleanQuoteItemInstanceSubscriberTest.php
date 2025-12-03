<?php
namespace Fedex\OptimizeProductinstance\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface;
use Fedex\OptimizeProductinstance\Model\CleanQuoteItemInstanceSubscriber;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Psr\Log\LoggerInterface;

class CleanQuoteItemInstanceSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quoteItem;
    protected $quoteOption;
    protected $quote;
    protected $message;
    /**
     * @var (\Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $subscribe;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $cleanQuoteItemInstanceSubscriber;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();

        $this->quoteItem = $this->getMockBuilder(Item::class)
        ->setMethods([
            'getOptionId',
            'getOptionByCode',
            'setIsSuperMode',
            'setValue',
            'save',
        ])->disableOriginalConstructor()->getMock();

        $this->quoteOption = $this->getMockBuilder(Option::class)
        ->setMethods(['getValue', 'setValue', 'save', 'getOptionId'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->quote  = $this->getMockBuilder(Quote::class)
        ->setMethods(['load', 'getAllItems'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->message  = $this->getMockBuilder(OptimizeInstanceMessageInterface::class)
        ->setMethods(['getMessage'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->subscribe  = $this->getMockBuilder(OptimizeInstanceSubscriberInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
        ->setMethods(['error', 'info'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->cleanQuoteItemInstanceSubscriber    = $this->objectManager->getObject(
            CleanQuoteItemInstanceSubscriber::class,
            [
                'quoteFactory'            => $this->quoteFactory,
                'logger'                  => $this->loggerMock
            ]
        );
    }

    /**
     * Test processMessage.
     *
     * @return bool
     */
    public function testprocessMessage()
    {
        $this->message->expects($this->any())
        ->method('getMessage')
        ->willReturn("Test Message");
        $this->quoteFactory->expects($this->any())
        ->method('create')
        ->willReturn($this->quote);
        $this->quote->expects($this->any())
        ->method('load')
        ->willReturnSelf();
        $this->quote->expects($this->any())
        ->method('getAllItems')
        ->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())
        ->method('setIsSuperMode')
        ->willReturnSelf();
        $this->quoteItem->expects($this->any())
        ->method('getOptionByCode')
        ->willReturn($this->quoteOption);
        $this->quoteOption->expects($this->any())
        ->method('getOptionId')
        ->willReturnSelf();
        $this->quoteOption->expects($this->any())
        ->method('setValue')
        ->willReturnSelf();
        $this->quoteOption->expects($this->any())
        ->method('setValue')
        ->willReturnSelf();
        $this->quoteOption->expects($this->any())
        ->method('save')
        ->willReturnSelf();
        $this->assertEquals(null, $this->cleanQuoteItemInstanceSubscriber->processMessage($this->message));
    }

    /**
     * Test processMessage.
     *
     * @return bool
     */
    public function testprocessMessageWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception();
        $this->message->expects($this->any())
        ->method('getMessage')
        ->willThrowException($exception);
        $this->assertEquals(null, $this->cleanQuoteItemInstanceSubscriber->processMessage($this->message));
    }
}
