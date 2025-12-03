<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Controller\Adminhtml\Import;

use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\ImportExport\Helper\Data;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Page\Config;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\File\Size as FileSize;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Fedex\Import\Controller\Adminhtml\Import\Index;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Test class for Index controller class
 */
class IndexTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Phrase & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $phraseMock;
    protected $dataHelperMock;
    protected $resultFactory;
    protected $resultPage;
    protected $messageManager;
    protected $pageConfig;
    protected $pageTitle;
    protected $_objectManager;
    /**
     * @var (\Magento\Framework\File\Size & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fileSizeMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $Mock;
    protected $index;
    /**
     * Test setUp method
     */
    public function setUp(): void
    {
        $this->phraseMock = $this->getMockBuilder(Phrase::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
        ->setMethods(['getMaxUploadSizeMessage'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPage = $this->getMockBuilder(Page::class)
            ->setMethods([
                'getLayout',
                'createBlock',
                'setTemplate',
                'toHtml',
                'setActiveMenu',
                'getConfig',
                'getTitle',
                'prepend',
                'getBlock',
                'addBreadcrumb',
                'setActive'
            ])->disableOriginalConstructor()->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addNotice'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fileSizeMock = $this->createMock(FileSize::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->Mock = $this->objectManagerHelper->getObject(
            Index::class,
            [
                'fileSize' => $this->fileSizeMock,
                'messageManager' =>$this->messageManager,
                '_objectManager' => $this->_objectManager,
                'data' => $this->dataHelperMock,
                'resultFactory' => $this->resultFactory,
            ]
        );
        $this->index = $this->objectManagerHelper->getObject(Index::class);
    }

    /**
     * Test method for execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $temp = ['ABC'];
        $this->messageManager->expects($this->any())->method('addNotice')->willReturnSelf();
        $this->_objectManager->expects($this->any())->method('get')->willReturn($this->dataHelperMock);
        $this->dataHelperMock->expects($this->any())->method('getMaxUploadSizeMessage')->willReturn($temp);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->resultPage);
        $this->resultPage->expects($this->any())->method('getConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->exactly(2))->method('prepend')
        ->withConsecutive(['Import/Export'], ['Import'])
        ->willReturnOnConsecutiveCalls([], []);
        $this->resultPage->expects($this->any())->method('addBreadcrumb')
        ->with(__('Import'), __('Import'))->willReturnSelf();
        $this->resultPage->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->resultPage->expects($this->any())->method('getBlock')->willReturnSelf();
        $this->resultPage->expects($this->any())->method('setActive')->willReturnSelf();

        $testMethod = new \ReflectionMethod(
            Index::class,
            '_isAllowed',
        );
        $testMethod->invoke($this->index);
        $this->Mock->execute();
    }
}
