<?php

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\PageFactory;

class LeftMenuTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LeftMenu
     */
    protected $controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PageFactory
     */
    protected $resultPageFactoryMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resultPageFactoryMock = $this->createMock(PageFactory::class);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = $objectManager->getObject(
            LeftMenu::class,
            [
                'context' => $contextMock,
                'resultPageFactory' => $this->resultPageFactoryMock
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $blockContent = '<div>Block content</div>';

        $resultPageMock = $this->getMockBuilder(ResultPage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $blockMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPageMock);

        $resultPageMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $layoutMock->expects($this->once())
            ->method('createBlock')
            ->willReturn($blockMock);

        $blockMock->expects($this->once())
            ->method('setTemplate')
            ->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($blockContent);

        $responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setBody'])
            ->getMockForAbstractClass();
        $responseMock->expects($this->once())
            ->method('setBody')
            ->with($blockContent);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->once())
            ->method('getResponse')
            ->willReturn($responseMock);

        $this->controller->__construct(
            $contextMock,
            $this->resultPageFactoryMock
        );

        $this->assertNull($this->controller->execute());
    }
}
