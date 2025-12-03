<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model\UrlProcessor;

use Fedex\WebAnalytics\Model\UrlProcessor\NotFoundPage;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class NotFoundPageTest extends TestCase
{
    protected $httpMock;
    /**
     * Name of method to get current page full action name
     */
    private const GET_FULL_ACTION_NAME_METHOD = 'getFullActionName';

    /**
     * Page type value
     */
    private const PAGE_TYPE = 'application';

    /**
     * @var NotFoundPage
     */
    private NotFoundPage $notFoundPage;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->httpMock = $this->createMock(Http::class);
        $this->notFoundPage = new NotFoundPage($this->httpMock);
    }

    /**
     * Test isCurrentPage method with valid page
     *
     * @return void
     */
    public function testisCurrentPageValid(): void
    {
        $this->httpMock->expects($this->once())
            ->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('cms_noroute_index');
        $this->assertTrue($this->notFoundPage->isCurrentPage());
    }

    /**
     * Test isCurrentPage method with invalid page
     *
     * @return void
     */
    public function testisCurrentPageInvalid(): void
    {
        $this->httpMock->expects($this->once())
            ->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('cms_index_index');
        $this->assertFalse($this->notFoundPage->isCurrentPage());
    }

    /**
     * Test getType method
     *
     * @return void
     */
    public function testGetType(): void
    {
        $this->assertEquals(self::PAGE_TYPE, $this->notFoundPage->getType());
    }
}
