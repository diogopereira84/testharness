<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Fedex\SubmitOrderSidebar\Controller\Index\OrderSuccess;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderSuccessTest extends TestCase
{
    protected $orderSuccess;
    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * Function setUp
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->orderSuccess = $this->objectManager->getObject(
            OrderSuccess::class,
            [
                'resultPageFactory' => $this->resultPageFactory
            ]
        );
    }

    /**
     * Function testExecute
     */
    public function testExecute()
    {
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->assertEquals($this->resultPageFactory, $this->orderSuccess->execute());
    }
}
