<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Test\Unit\Controller\Adminhtml\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\CIDPSG\Controller\Adminhtml\Index\Index;

/**
 * Test class for index
 */
class IndexTest extends TestCase
{
    protected $resultPageFactory;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $indexMock;
    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
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

        $this->objectManager = new ObjectManager($this);

        $this->indexMock = $this->objectManager->getObject(
            Index::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'page' => $this->pageMock
            ]
        );
    }

    /**
     * Controller test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->resultPageFactory->expects($this->any())->method('create')->willReturn($this->pageMock);

        $this->pageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Fedex_CIDPSG::psg_customers')
            ->willReturnSelf();

        $this->pageMock->expects($this->once())->method('getConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);

        $this->pageTitle->expects($this->any())->method('prepend')
            ->with(__('PSG Customers Details'))
            ->willReturn($this->pageMock);

        $this->assertSame($this->pageMock, $this->indexMock->execute());
    }
}
