<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Test\Unit\Block\Adminhtml;

use Fedex\InBranch\Block\Adminhtml\LocationUrl;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocationUrlTest extends TestCase
{
    private LocationUrl $locationUrl;
    private UrlInterface|MockObject $urlBuilderMock;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);

        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $contextMock->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);

        $this->locationUrl = new LocationUrl($contextMock);
    }

    public function testGetLocationUrl(): void
    {
        $urlPath = 'inbranch/location/get';
        $url = 'http://example.com/' . $urlPath;

        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($urlPath)
            ->willReturn($url);

        $this->assertEquals($url, $this->locationUrl->getLocationUrl());
    }
}
