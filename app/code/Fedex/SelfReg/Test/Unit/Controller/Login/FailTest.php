<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Controller\Login;

use Fedex\SelfReg\Controller\Login\Fail;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class FailTest extends TestCase
{
    protected $pageFactoryMock;
    protected $resultPageMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $selfRegLoginFailMock;
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
        $this->selfRegLoginFailMock = $this->objectManager->getObject(
            Fail::class,
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
        $result = $this->selfRegLoginFailMock->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}
