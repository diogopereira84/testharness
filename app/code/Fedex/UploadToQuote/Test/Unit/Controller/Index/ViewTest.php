<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Fedex\UploadToQuote\Controller\Index\View;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $viewData;
    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->viewData = $this->objectManagerHelper->getObject(
            View::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecute()
    {

        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->assertEquals($this->resultPageFactory, $this->viewData->execute());
    }
}
