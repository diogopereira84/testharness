<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Controller\Configurator;

use Fedex\CatalogMvp\Controller\Configurator\Index;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result;
use Magento\Framework\App\Request\Http;

class IndexTest extends TestCase
{
    protected $redirectInterface;
    protected $resultFactory;
    protected $result;
    protected $request;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private Index $indexMock;
    private Context|MockObject $contextMock;
    private PageFactory|MockObject $pageFactoryMock;
    private Page|MockObject $pageMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectInterface = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRefererUrl'])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->setMethods(['setUrl'])
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->indexMock = $this->objectManager->getObject(
            Index::class,
            [
                'context' => $this->contextMock,
                '_pageFactory' => $this->pageFactoryMock,
                '_redirect' => $this->redirectInterface,
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request
            ]
        );
    }

    public function testExecute(): void
    {
        $this->pageFactoryMock->expects($this->any())->method('create')->willReturn($this->pageMock);
        $this->request->expects($this->any())->method('getParams')->willReturn(['undefined'=>'']);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->result);
        $this->result->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->redirectInterface->expects($this->any())->method('getRefererUrl')->willReturn("https://staging3.office.fedex.com/ondemand/catalogmvp/configurator/index/");
        $this->assertInstanceOf(Result::class, $this->indexMock->execute());
    }
    public function testExecuteWithoutParams(): void
    {
        $this->pageFactoryMock->expects($this->any())->method('create')->willReturn($this->pageMock);
        $this->request->expects($this->any())->method('getParams')->willReturn([]);
        $this->assertInstanceOf(Page::class, $this->indexMock->execute());
    }
}
