<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Controller\ReviewOrder;

use Fedex\OrderApprovalB2b\Controller\ReviewOrder\History;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Test class for History
 */
class HistoryTest extends TestCase
{
    protected $history;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var RedirectFactory $resultRedirectFactory
     */
    protected $resultRedirectFactory;
    
    /**
     * @var RevieworderHelper $revieworderHelper
     */
    protected $revieworderHelper;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create', 'setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->revieworderHelper = $this->getMockBuilder(RevieworderHelper::class)
            ->setMethods(['checkIfUserHasReviewOrderPermission'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->history = $this->objectManagerHelper->getObject(
            History::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'revieworderHelper' => $this->revieworderHelper,
                'resultRedirectFactory' => $this->resultRedirectFactory
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
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturn(null);

        $this->assertNull($this->history->execute());
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecuteWithoutRedirect()
    {
        $this->resultPageFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->revieworderHelper->expects($this->once())
        ->method('checkIfUserHasReviewOrderPermission')->willReturn(true);

        $this->assertIsObject($this->history->execute());
    }
}
