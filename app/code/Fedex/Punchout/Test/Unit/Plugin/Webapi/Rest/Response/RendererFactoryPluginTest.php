<?php

namespace Magento\Tools\Sanity\Fedex\Punchout\Plugin\Webapi\Rest\Response;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Plugin\Webapi\Rest\Response\RendererFactoryPlugin;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Webapi\Rest\Response\Renderer\Xml as XmlRenderer;
use Magento\Framework\Webapi\Rest\Response\Renderer\Json as JsonRenderer;
use Magento\Framework\Webapi\Rest\Response\RendererFactory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RendererFactoryPluginTest extends TestCase
{
    private RendererFactoryPlugin $plugin;
    private Http|MockObject $requestMock;
    private XmlRenderer|MockObject $xmlRendererMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(Http::class);
        $this->xmlRendererMock = $this->createMock(XmlRenderer::class);

        $this->plugin = new RendererFactoryPlugin(
            $this->requestMock,
            $this->xmlRendererMock
        );
    }

    /**
     * @throws Exception
     */
    public function testAfterGetReturnsXmlRendererWhenPathMatches(): void
    {
        $rendererFactoryMock = $this->createMock(RendererFactory::class);
        $resultRenderer = $this->createMock(XmlRenderer::class);

        $this->requestMock->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/rest/V1/fedex/eprocurement');

        $actualResult = $this->plugin->afterGet($rendererFactoryMock, $resultRenderer);

        $this->assertSame($this->xmlRendererMock, $actualResult);
    }

    /**
     * @throws Exception
     */
    public function testAfterGetReturnsOriginalResultWhenPathDoesNotMatch(): void
    {
        $rendererFactoryMock = $this->createMock(RendererFactory::class);
        $resultRenderer = $this->createMock(XmlRenderer::class);

        $this->requestMock->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/rest/V1/other/endpoint');

        $actualResult = $this->plugin->afterGet($rendererFactoryMock, $resultRenderer);

        $this->assertSame($resultRenderer, $actualResult);
    }
}
