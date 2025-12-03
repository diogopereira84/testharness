<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model\CmsPage;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Cms\Model\Page;
use Fedex\WebAnalytics\Model\CmsPage\PageTypeResolver;

class PageTypeResolverTest extends TestCase
{
    protected $loggerMock;
    protected $httpMock;
    protected $pageMock;
    protected $pageTypeResolver;
    private const PAGE_TYPE_METHOD = 'getPageType';
    private const ID_METHOD = 'getId';

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->httpMock = $this->createMock(Http::class);
        $this->httpMock->expects($this->once())
            ->method('getFullActionName')->willReturn('cms_page_view');
        $this->pageMock = $this->getMockBuilder(Page::class)
            ->setMethods(
                [
                    self::PAGE_TYPE_METHOD,
                    self::ID_METHOD,
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTypeResolver = new PageTypeResolver(
            $this->httpMock,
            $this->loggerMock,
            $this->pageMock
        );
    }

    /**
     * Test resolve method from Db configuration
     *
     * @return void
     */
    public function testResolve(): void
    {
        $this->pageMock->expects($this->once())->method(self::ID_METHOD)
            ->willReturn(1);
        $this->pageMock
            ->expects($this->once())
            ->method(self::PAGE_TYPE_METHOD)
            ->willReturn('content');
        $this->pageTypeResolver->resolve();
    }

    /**
     * Test resolve method from Db configuration
     *
     * @return void
     */
    public function testResolveException(): void
    {
        $this->httpMock->expects($this->once())
            ->method('getFullActionName')
            ->willThrowException(new \Exception());
        $this->loggerMock->expects($this->once())->method('warning');
        $this->pageTypeResolver->resolve();
    }
}
