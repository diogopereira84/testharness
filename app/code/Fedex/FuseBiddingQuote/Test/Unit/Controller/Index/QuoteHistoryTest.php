<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Controller\Index;

use Fedex\FuseBiddingQuote\Controller\Index\QuoteHistory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Quote History Controller
 */
class QuoteHistoryTest extends TestCase
{
    protected $quoteHistory;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->quoteHistory = $this->objectManagerHelper->getObject(
            QuoteHistory::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->assertNotNull($this->quoteHistory->execute());
    }
}
