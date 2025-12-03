<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Controller\Adminhtml\Shop;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Page\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceCheckout\Controller\Adminhtml\Shop\Edit;

class EditTest extends TestCase
{
    /**
     * @var MockObject|Context
     */
    private $contextMock;

    /**
     * @var MockObject|ResultFactory
     */
    private $resultFactory;

    /**
     * @var MockObject|ShopRepositoryInterface
     */
    private $shopRepository;

    /**
     * @var Edit
     */
    private $editController;

    /**
     * @var Page|MockObject
     */
    private $resultPage;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var Config|MockObject
     */
    private $resultConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shopRepository = $this->getMockBuilder(ShopRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $this->resultPage = $this->createMock(Page::class);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($this->resultPage);

        $this->resultConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->addMethods(['prepend'])
            ->onlyMethods(['getTitle'])
            ->getMock();
        $this->resultPage->expects($this->once())->method('getConfig')->willReturn($this->resultConfig);

        $this->editController = new Edit(
            $this->contextMock,
            $this->resultFactory,
            $this->shopRepository
        );
    }

    /**
     * Text execute method with valid id
     */
    public function testExecuteWithValidId(): void
    {
        $id = 123;
        $storeName = 'Test Store';

        $this->requestMock->expects($this->any())
            ->method('getParam')->with('id')->willReturn($id);

        $shopMock = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getName'])
            ->getMockForAbstractClass();
        $shopMock->expects($this->once())->method('getName')->willReturn($storeName);

        $this->shopRepository->expects($this->once())->method('getById')->with($id)->willReturn($shopMock);

        $this->resultConfig->expects($this->once())->method('prepend')->with(__('Update Shipping Methods for ' . $storeName));
        $this->resultConfig->expects($this->once())->method('getTitle')->willReturnSelf();

        $this->assertSame($this->resultPage, $this->editController->execute());
    }

    /**
     * Text execute method with invalid id
     */
    public function testExecuteWithInvalidId(): void
    {
        $id = null;
        $storeName = '';

        $this->requestMock->expects($this->once())->method('getParam')->with('id')->willReturn($id);

        $this->shopRepository->expects($this->never())->method('getById');

        $this->resultConfig->expects($this->once())->method('prepend')->with(__('Update Shipping Methods for ' . $storeName));
        $this->resultConfig->expects($this->once())->method('getTitle')->willReturnSelf();

        $this->assertSame($this->resultPage, $this->editController->execute());
    }
}
