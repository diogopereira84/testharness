<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CategoryLayout\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CategoryLayout\Controller\Adminhtml\Index\Update;

class UpdateTest extends TestCase
{
    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $resultJsonFactory;
    protected $jsonMock;
    protected $requestMock;
    protected $pageFactoryMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $categoryFactory;
    protected $category;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Api\MessageInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageMock;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisherMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfacenMock;
    protected $updateMock;
    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
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
        $this->categoryFactory = $this
            ->getMockBuilder(CategoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'getAllChildren'
            ])->getMock();
        $this->messageMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerInterfacenMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);

        $this->updateMock = $this->objectManager->getObject(
            Update::class,
            [
                'resultPageFactory'  => $this->pageFactoryMock,
                'resultJsonFactory'  => $this->resultJsonFactory,
                'request'            => $this->requestMock,
                'categoryFactory'    => $this->categoryFactory,
                'messageInterface'   => $this->messageMock,
                'publisherInterface' => $this->publisherMock,
                'logger'             => $this->loggerInterfacenMock
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $categoryId = 20;

        $this->requestMock
            ->method('getPostValue')
            ->with('category_id')
            ->willReturn($categoryId);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);
        $this->category->expects($this->any())->method('getAllChildren')->willReturn([12, 23]);

        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->updateMock->execute());
    }

    /**
     * Test execute without category post value
     *
     * @return void
     */
    public function testExecuteWithNoPostValue()
    {
        $this->requestMock
            ->method('getPostValue')
            ->with('category_id')
            ->willReturn(null);

        $this->pageFactoryMock->expects($this->any())->method('create')->willReturn($this->pageMock);
        $this->pageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Fedex_CategoryLayout::category_update')
            ->willReturnSelf();

        $this->pageMock->expects($this->once())->method('getConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);

        $this->pageTitle->expects($this->any())->method('prepend')
            ->with(__('Category Update Manager'))
            ->willReturn($this->pageMock);

        $this->assertSame($this->pageMock,  $this->updateMock->execute());
    }
}