<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model\UrlProcessor;

use Fedex\WebAnalytics\Model\UrlProcessor\SearchPage;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class SearchPageTest extends TestCase
{
    protected $httpMock;
    /**
     * Name of method to get current page full action name
     */
    private const GET_FULL_ACTION_NAME_METHOD = 'getFullActionName';

    /**
     * Page type value
     */
    private const PAGE_TYPE = 'FXOSearchpage';

    /**
     * @var SearchPage
     */
    private SearchPage $searchPage;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->httpMock = $this->createMock(Http::class);
        $this->searchPage = new SearchPage($this->httpMock);
    }

    /**
     * Test isSearchPage method with valid page
     *
     * @return void
     */
    public function testIsSearchPageValid(): void
    {
        $this->httpMock->expects($this->once())
            ->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('catalogsearch_result_index');
        $this->assertTrue($this->searchPage->isSearchPage());
    }

    /**
     * Test isSearchPage method with invalid page
     *
     * @return void
     */
    public function testIsSearchPageInvalid(): void
    {
        $this->httpMock->expects($this->once())
            ->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('cms_index_index');
        $this->assertFalse($this->searchPage->isSearchPage());
    }

    /**
     * Test getType method
     *
     * @return void
     */
    public function testGetType(): void
    {
        $this->assertEquals(self::PAGE_TYPE, $this->searchPage->getType());
    }
}
