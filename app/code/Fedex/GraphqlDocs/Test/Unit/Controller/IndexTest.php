<?php

declare(strict_types=1);

namespace Fedex\GraphqlDocs\Test\Unit\Controller;

use Fedex\GraphqlDocs\Controller\Adminhtml\Index\Index;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private $contextMock;
    private $pageFactoryMock;
    private $pageMock;
    private $titleMock;
    private $configMock;
    private $authorizationMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->pageFactoryMock = $this->createMock(PageFactory::class);
        $this->pageMock = $this->createMock(Page::class);
        $this->titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorizationMock = $this->getMockBuilder(\Magento\Framework\AuthorizationInterface::class)
            ->getMock();

        $this->contextMock->method('getAuthorization')->willReturn($this->authorizationMock);
    }

    public function testExecuteSetsTitleAndReturnsPage()
    {
        $this->pageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->pageMock);

        $this->pageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->configMock);

        $this->configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($this->titleMock);

        $this->titleMock->expects($this->once())
            ->method('set')
            ->with(__('GraphQL Schema Browser'));

        $controller = new Index($this->contextMock, $this->pageFactoryMock);
        $result = $controller->execute();
        $this->assertSame($this->pageMock, $result);
    }

    public function testIsAllowedChecksAcl()
    {
        $this->authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with('Fedex_GraphqlDocs::graphqlschema')
            ->willReturn(true);

        $controller = new Index($this->contextMock, $this->pageFactoryMock);
        $this->assertTrue($this->invokeIsAllowed($controller));
    }

    private function invokeIsAllowed(Index $controller): bool
    {
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('_isAllowed');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }
}

