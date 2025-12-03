<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Fedex\UploadToQuote\Controller\Index\ProcessQueue;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\UploadToQuote\Helper\QueueHelper;

class ProcessQueueTest extends TestCase
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
     * @var ObjectManagerHelper $objectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var ProcessQueue $processQueue
     */
    protected $processQueue;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->queueHelper = $this->getMockBuilder(QueueHelper::class)
            ->setMethods(['processQueue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->processQueue = $this->objectManagerHelper->getObject(
            ProcessQueue::class,
            [
                'queueHelper' => $this->queueHelper,
                'jsonFactory' => $this->jsonFactory
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
        $this->queueHelper->expects($this->once())->method('processQueue')->willReturn(true);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();

        $this->assertIsObject($this->processQueue->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->queueHelper->expects($this->once())->method('processQueue')->willThrowException($exception);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();

        $this->assertIsObject($this->processQueue->execute());
    }
}
