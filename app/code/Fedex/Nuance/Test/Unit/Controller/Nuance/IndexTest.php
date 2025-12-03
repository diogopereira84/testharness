<?php
declare(strict_types=1);

namespace Fedex\Nuance\Test\Unit\Controller\Nuance;

use Fedex\Nuance\Controller\Nuance\Index;
use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class IndexTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var HttpResponse|MockObject
     */
    private $redirect;

    /**
     * @var NuanceInterface|MockObject
     */
    private $nuanceInterface;

    /**
     * @var Index
     */
    private $controller;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->redirect = $this->createMock(HttpResponse::class);
        $this->nuanceInterface = $this->createMock(NuanceInterface::class);

        $this->controller = new Index(
            $this->context,
            $this->resultPageFactory,
            $this->redirect,
            $this->nuanceInterface
        );
    }

    public function testExecuteWithEnabledNuance()
    {
        $this->nuanceInterface->method('isEnabledNuanceForCompany')->willReturn(true);

        $resultPage = $this->createMock(Page::class);
        $this->resultPageFactory->method('create')->willReturn($resultPage);

        $resultPage->expects($this->once())->method('addHandle')->with('nuance_nuance_index');
        $resultPage->expects($this->atMost(2))
            ->method('setHeader')
            ->withConsecutive(['Content-Type', 'text/html; charset=utf-8'], ['Cache-Control', 'max-age=3600, private']);

        $result = $this->controller->execute();

        $this->assertInstanceOf(Page::class, $result);
    }

    public function testExecuteWithDisabledNuance()
    {
        $this->nuanceInterface->method('isEnabledNuanceForCompany')->willReturn(false);

        $url = 'http://example.com';
        $this->context->method('getUrl')->willReturn($this->createConfiguredMock(\Magento\Framework\UrlInterface::class, ['getUrl' => $url]));

        $this->redirect->expects($this->once())->method('setRedirect')->with($url, 301);

        $result = $this->controller->execute();

        $this->assertInstanceOf(HttpResponse::class, $result);
    }
}
