<?php

namespace Fedex\SelfReg\Controller\Adminhtml;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Fedex\SelfReg\Controller\Adminhtml\Index\Index;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Controller\ResultFactory;


class IndexTest extends \PHPUnit\Framework\TestCase
{
    protected $pageFactoryMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $toggleConfigMock;
    protected $resultFactoryMock;
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
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create', 'forward'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = $objectManager->getObject(
            Index::class,
            [
                'resultPageFactory' => $this->pageFactoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->pageFactoryMock->expects($this->any())->method('create')->willReturn($this->pageMock);
        $this->pageMock->expects($this->once())->method('getConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('prepend')
            ->with(__('Approver Groups'))
            ->willReturn($this->pageMock);
       

        $this->assertSame($this->pageMock,  $this->controller->execute());
    }

     /**
     * Test execute method
     */
    public function testExecuteElse()
    {

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('forward')->willReturnSelf();

        $this->assertSame($this->resultFactoryMock,  $this->controller->execute());
    }
}
