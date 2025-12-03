<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\DownloadLinkDTO;
use PHPUnit\Framework\TestCase;

class DownloadLinkDTOTest extends TestCase
{
    public function testConstructorAndGetter()
    {
        $link = new DownloadLinkDTO('http://example.com/file.pdf');
        $this->assertEquals('http://example.com/file.pdf', $link->getHref());
    }
}
