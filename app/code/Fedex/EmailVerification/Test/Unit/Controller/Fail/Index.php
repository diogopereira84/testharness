<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EmailVerification\Test\Unit\Controller\Fail;

use Fedex\EmailVerification\Controller\Fail\Index;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    protected $pageFactoryMock;
    protected $resultPageMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $indexMock;
    protected $contextMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->indexMock = $this->objectManager->getObject(
            Index::class,
            [
                'pageFactory' => $this->pageFactoryMock,
            ]
        );
    }

    /**
     * Test for  execute
     *
     * @return  PageFactory
     */
    public function testExecute()
    {
        $this->pageFactoryMock->expects($this->any())->method('create')->willReturn($this->resultPageMock);
        $result = $this->indexMock->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}