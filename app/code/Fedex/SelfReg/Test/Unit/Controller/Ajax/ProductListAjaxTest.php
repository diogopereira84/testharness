<?php

namespace Fedex\SelfReg\Test\Unit\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Registry;
use Magento\Framework\App\Request\Http;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use PHPUnit\Framework\TestCase;
use Fedex\CustomerCanvas\ViewModel\CanvasParams;

class ProductListAjaxTest extends TestCase
{
    /**
     * @var ProductListAjax
     */
    protected $controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultPageFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $viewModelMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mvpViewModelMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mvpHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $outputHelperMock;
    protected $canvasParamsMock;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->viewModelMock = $this->getMockBuilder(\Fedex\Catalog\ViewModel\ProductList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mvpViewModelMock = $this->getMockBuilder(\Fedex\CatalogMvp\ViewModel\MvpHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->outputHelperMock = $this->getMockBuilder(\Magento\Catalog\Helper\Output::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->canvasParamsMock = $this->getMockBuilder(CanvasParams::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new \Fedex\SelfReg\Controller\Ajax\ProductListAjax(
            $contextMock,
            $this->resultFactoryMock,
            $this->resultPageFactoryMock,
            $this->resultJsonFactoryMock,
            $this->viewModelMock,
            $this->mvpViewModelMock,
            $this->registryMock,
            $this->categoryRepositoryMock,
            $this->mvpHelperMock,
            $this->requestMock,
            $this->outputHelperMock,
            $this->canvasParamsMock
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $resultPageMock = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPageMock);

        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn(123);

        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($categoryMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('current_category', $categoryMock);

        $this->mvpHelperMock->expects($this->once())
            ->method('getMergedSharedCatalogFilesToggle')
            ->willReturn(false);
        $this->mvpHelperMock->expects($this->once())
            ->method('isSharedCatalogPermissionEnabled')
            ->willReturn(true);

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultPageMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $blockMock = $this->getMockBuilder(\Fedex\CatalogMvp\Block\Product\ListProduct::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mocking the child blocks to avoid null when setChild() is called
        $breadcrumbsBlockMock = $this->getMockBuilder(\Magento\Catalog\Block\Breadcrumbs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $toolbarBlockMock = $this->getMockBuilder(\Magento\Catalog\Block\Product\ProductList\Toolbar::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pagerBlockMock = $this->getMockBuilder(\Magento\Theme\Block\Html\Pager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ensure the layout mock returns the correct blocks for the createBlock() calls
        $layoutMock->expects($this->exactly(4)) // 1 main block + 3 child blocks
            ->method('createBlock')
            ->withConsecutive(
                ['Fedex\CatalogMvp\Block\Product\ListProduct', 'product_list_view_model', ['data' => [
                    'product_list_view_model' => $this->viewModelMock,
                    'view_model_mvphelper' => $this->mvpViewModelMock,
                    'outputHelper' => $this->outputHelperMock,
                    'dyesubViewModel' => $this->canvasParamsMock
                ]]],
                ['Magento\Catalog\Block\Breadcrumbs'],
                ['Magento\Catalog\Block\Product\ProductList\Toolbar', 'ajax_view_model_mvphelper', ['data' => [
                    'view_model_mvphelper' => $this->mvpViewModelMock,
                ]]],
                ['Magento\Theme\Block\Html\Pager']
            )
            ->willReturnOnConsecutiveCalls($blockMock, $breadcrumbsBlockMock, $toolbarBlockMock, $pagerBlockMock);

        $blockMock->expects($this->once())
            ->method('setTemplate')
            ->with('Magento_Catalog::product/product-category-list-customer-admin.phtml')
            ->willReturnSelf();

        // Set expectations for setChild() calls
        $blockMock->expects($this->exactly(2))
            ->method('setChild')
            ->withConsecutive(
                ['breadcrumbs', $breadcrumbsBlockMock],
                ['toolbar', $toolbarBlockMock]
            )
            ->willReturnSelf();

        $toolbarBlockMock->expects($this->once())
            ->method('setTemplate')
            ->with('Magento_Catalog::product/list/ajaxToolbar.phtml')
            ->willReturnSelf();

        // Here we mock the setCollection call to avoid calling it on null
        $toolbarBlockMock->expects($this->once())
            ->method('setCollection')
            ->with($this->isType('array')) // Assuming an array is passed, adjust if necessary
            ->willReturnSelf();

        $toolbarBlockMock->expects($this->once())
            ->method('setChild')
            ->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn('<div>Block HTML</div>');

        $rawResultMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Raw::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($rawResultMock);

        $rawResultMock->expects($this->once())
            ->method('setContents')
            ->with('<div>Block HTML</div>')
            ->willReturnSelf();

        $this->assertSame($rawResultMock, $this->controller->execute());
    }

    /**
     * Test execute method for customer user
     */
    public function testExecuteForCustomerUser()
    {
        $resultPageMock = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPageMock);

        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn(123);

        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($categoryMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('current_category', $categoryMock);

        $this->mvpHelperMock->expects($this->once())
            ->method('getMergedSharedCatalogFilesToggle')
            ->willReturn(false);

        $this->mvpHelperMock->expects($this->once())
            ->method('isSharedCatalogPermissionEnabled')
            ->willReturn(false);

        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultPageMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $blockMock = $this->getMockBuilder(\Fedex\CatalogMvp\Block\Product\ListProduct::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mocking the child blocks to avoid null when setChild() is called
        $breadcrumbsBlockMock = $this->getMockBuilder(\Magento\Catalog\Block\Breadcrumbs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $toolbarBlockMock = $this->getMockBuilder(\Magento\Catalog\Block\Product\ProductList\Toolbar::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pagerBlockMock = $this->getMockBuilder(\Magento\Theme\Block\Html\Pager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ensure the layout mock returns the correct blocks for the createBlock() calls
        $layoutMock->expects($this->exactly(4)) // 1 main block + 3 child blocks
            ->method('createBlock')
            ->withConsecutive(
                ['Fedex\CatalogMvp\Block\Product\ListProduct', 'product_list_view_model', ['data' => [
                    'product_list_view_model' => $this->viewModelMock,
                    'view_model_mvphelper' => $this->mvpViewModelMock,
                    'outputHelper' => $this->outputHelperMock,
                    'dyesubViewModel' => $this->canvasParamsMock
                ]]],
                ['Magento\Catalog\Block\Breadcrumbs'],
                ['Magento\Catalog\Block\Product\ProductList\Toolbar', 'ajax_view_model_mvphelper', ['data' => [
                    'view_model_mvphelper' => $this->mvpViewModelMock,
                ]]],
                ['Magento\Theme\Block\Html\Pager']
            )
            ->willReturnOnConsecutiveCalls($blockMock, $breadcrumbsBlockMock, $toolbarBlockMock, $pagerBlockMock);

        $blockMock->expects($this->once())
            ->method('setTemplate')
            ->with('Magento_Catalog::product/product-category-list-customer.phtml')
            ->willReturnSelf();

        // Set expectations for setChild() calls
        $blockMock->expects($this->exactly(2))
            ->method('setChild')
            ->withConsecutive(
                ['breadcrumbs', $breadcrumbsBlockMock],
                ['toolbar', $toolbarBlockMock]
            )
            ->willReturnSelf();

        $toolbarBlockMock->expects($this->once())
            ->method('setTemplate')
            ->with('Magento_Catalog::product/list/ajaxToolbar.phtml')
            ->willReturnSelf();

        // Here we mock the setCollection call to avoid calling it on null
        $toolbarBlockMock->expects($this->once())
            ->method('setCollection')
            ->with($this->isType('array')) // Assuming an array is passed, adjust if necessary
            ->willReturnSelf();

        $toolbarBlockMock->expects($this->once())
            ->method('setChild')
            ->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn('<div>Block HTML</div>');

        $rawResultMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Raw::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($rawResultMock);

        $rawResultMock->expects($this->once())
            ->method('setContents')
            ->with('<div>Block HTML</div>')
            ->willReturnSelf();

        $this->assertSame($rawResultMock, $this->controller->execute());
    }
}
