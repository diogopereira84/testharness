<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Controller\Landing;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Controller\Landing\Mock;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

class MockTest extends TestCase
{
    /**
     * @var Mock
     */
    protected $controller;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var PageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultPageFactoryMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->controller = $objectManager->getObject(
            Mock::class,
            [
                'context' => $this->contextMock,
                'resultPageFactory' => $this->resultPageFactoryMock
            ]
        );
    }

    public function testExecute()
    {
        $resultPageMock = $this->getMockBuilder(ResultPage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPageMock);

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultPage::class, $result);
    }
}
