<?php

namespace Fedex\TrackOrder\Test\Unit\Controller\Home;

use Fedex\TrackOrder\Model\Config;
use PHPUnit\Framework\TestCase;
use Fedex\TrackOrder\Controller\Home\Search;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\TrackOrder\Model\OrderDetailApi;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\View\Result\Page;

class SearchTest extends TestCase
{
    private $context;
    private $resultJsonFactory;
    private $resultPageFactory;
    private $searchCriteriaBuilder;
    private $orderRepository;
    private $orderDetailApi;
    private $request;
    private $resultJson;
    private $resultPage;
    private $layout;
    private $block;

    private $configMock;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderDetailApi = $this->createMock(OrderDetailApi::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPost'])
            ->getMockForAbstractClass();
        $this->resultJson = $this->createMock(Json::class);
        $this->resultPage = $this->createMock(Page::class);
        $this->layout = $this->createMock(Layout::class);
        $this->block = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setTemplate', 'setData'])
            ->onlyMethods(['toHtml'])
            ->getMockForAbstractClass();
        $this->configMock = $this->createMock(Config::class);

        $this->context->method('getRequest')->willReturn($this->request);
        $this->resultJsonFactory->method('create')->willReturn($this->resultJson);
        $this->resultPageFactory->method('create')->willReturn($this->resultPage);
        $this->resultPage->method('getLayout')->willReturn($this->layout);
    }

    public function testExecuteWithValidOrders()
    {
        $orderIds = ['20101234','30101234','20204321'];
        $orderDetailsBlockResult = '<div>Order Details</div>';

        $this->request->method('getPost')->with('inputValues')->willReturn($orderIds);

        $this->layout->method('createBlock')->willReturn($this->block);
        $this->block->method('setTemplate')->willReturnSelf();
        $this->block->method('setData')->willReturnSelf();
        $this->block->method('toHtml')->willReturn($orderDetailsBlockResult);

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(['output' => $orderDetailsBlockResult, 'flag' => false])
            ->willReturnSelf();

        $controller = new Search(
            $this->context,
            $this->resultJsonFactory,
            $this->resultPageFactory,
            $this->searchCriteriaBuilder,
            $this->orderRepository,
            $this->orderDetailApi,
            $this->configMock
        );

        $result = $controller->execute();

        $this->assertSame($this->resultJson, $result);
    }
}