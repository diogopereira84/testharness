<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model\UrlProcessor;

use Fedex\WebAnalytics\Model\UrlProcessor\IframePage;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class IframePageTest extends TestCase
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
     * @var IframePage
     */
    private IframePage $iframe;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->httpMock = $this->createMock(Http::class);
        $this->iframe = new IframePage($this->httpMock);
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
            ->willReturn('canva_index_index');
        $this->assertTrue($this->iframe->isCurrentPage());
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
        $this->assertFalse($this->iframe->isCurrentPage());
    }

    /**
     * Test getType method
     *
     * @return void
     */
    public function testGetType(): void
    {
        $this->assertEquals(self::PAGE_TYPE, $this->iframe->getType());
    }
}
