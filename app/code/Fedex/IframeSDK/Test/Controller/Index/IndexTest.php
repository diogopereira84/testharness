<?php
declare(strict_types=1);

namespace Fedex\IframeSDK\Test\Controller\Index;

use Fedex\IframeSDK\Controller\Index\Index;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class IndexTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private Index $indexMock;
    private Context|MockObject $contextMock;
    private PageFactory|MockObject $pageFactoryMock;
    private Page|MockObject $pageMock;

    private RequestInterface|MockObject $request;
    private RedirectFactory|MockObject $redirectFactory;
    private Redirect|MockObject $redirect;
    private UrlInterface|MockObject $url;
    private ToggleConfig|MockObject $toggleConfig;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->indexMock = $this->objectManager->getObject(
            Index::class,
            [
                'context' => $this->contextMock,
                '_pageFactory' => $this->pageFactoryMock,
                'request' => $this->request,
                'redirectFactory' => $this->redirectFactory,
                'url' => $this->url,
                'toggleConfig' => $this->toggleConfig,
            ]
        );
    }

    public function testExecute(): void
    {
        $this->pageFactoryMock->expects($this->once())->method('create')->willReturn($this->pageMock);
        $this->assertInstanceOf(Page::class, $this->indexMock->execute());
    }

    public function testExecuteWithEmptySite(): void
    {
        $data = ['id'=>'c0f774d-0411-fa41-8397-81e4f63b6e56', 'siteName' => '', 'productType' => 'COMMERCIAL_PRODUCT'];
        $redirectUrl = "https://staging3.office.fedex.com/catalogmvp/configurator/index/sku/c0f774d-0411-fa41-8397-81e4f63b6e56/configuratorType/customize";
        $this->request->expects($this->once())->method('getParams')->willReturn($data);
        $this->redirectFactory->expects($this->once())->method('create')->willReturn($this->redirect);
        $this->redirect->expects($this->once())->method('setUrl')->willReturnSelf();
        $this->url->expects($this->once())->method('getUrl')->willReturn($redirectUrl);
        $this->assertInstanceOf(Redirect::class, $this->indexMock->execute());
    }
}
