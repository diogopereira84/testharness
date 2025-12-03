<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Fedex\UploadToQuote\Controller\Index\SetQueue;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\UploadToQuote\Helper\QueueHelper;

class SetQueueTest extends TestCase
{
    /**
     * @var QueueHelper $queueHelper
     */
    protected $queueHelper;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var Http $requestMock
     */
    protected $requestMock;

    /**
     * @var ObjectManagerHelper $objectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var SetQueue $setQueue
     */
    protected $setQueue;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->queueHelper = $this->getMockBuilder(QueueHelper::class)
            ->setMethods(['setQueue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->createMock(Http::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->setQueue = $this->objectManagerHelper->getObject(
            SetQueue::class,
            [
                'queueHelper' => $this->queueHelper,
                'jsonFactory' => $this->jsonFactory,
                '_request' => $this->requestMock,
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $postData = [
            'quoteId' => 123423,
            'action' => 'declined'
        ];

        $this->requestMock->expects($this->once())->method('getPostValue')->willReturn($postData);
        $this->queueHelper->expects($this->once())->method('setQueue')->willReturn(true);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();

        $this->assertIsObject($this->setQueue->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $postData = [
            'quoteId' => 123423,
            'action' => 'declined'
        ];

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestMock->expects($this->once())->method('getPostValue')->willReturn($postData);
        $this->queueHelper->expects($this->once())->method('setQueue')->willThrowException($exception);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();

        $this->assertIsObject($this->setQueue->execute());
    }
}
