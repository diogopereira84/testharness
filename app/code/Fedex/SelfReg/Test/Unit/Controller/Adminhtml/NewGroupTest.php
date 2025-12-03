<?php

namespace Fedex\SelfReg\Controller\Adminhtml;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Fedex\SelfReg\Controller\Adminhtml\Index\NewGroup;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;




class NewGroupTest extends \PHPUnit\Framework\TestCase
{
    protected $pageFactoryMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
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

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setActiveMenu', 'getConfig', 'getTitle'])
            ->getMock();
        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = $objectManager->getObject(
            NewGroup::class,
            [
                'resultPageFactory' => $this->pageFactoryMock
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {

        $this->pageFactoryMock->expects($this->any())->method('create')->willReturn($this->pageMock);
        $this->pageMock->expects($this->once())->method('getConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('prepend')
            ->with(__('New Approver Group'))
            ->willReturn($this->pageMock);
       

        $this->assertSame($this->pageMock,  $this->controller->execute());
    }
}
