<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\HTTP\Client;

use Fedex\CatalogMvp\HTTP\Client\Curl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{
    /**
     * @var Curl|MockObject
     */
    private $curlMock;

    protected function setUp(): void
    {
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['makeRequest'])
            ->getMock();
    }

    /**
     * Tests that the delete method calls makeRequest with the correct verb and URI.
     */
    public function testDeleteCallsMakeRequestWithCorrectParameters(): void
    {
        $uri = 'https://api.example.com/v1/resource/123';

        $this->curlMock->expects($this->once())
            ->method('makeRequest')
            ->with($this->equalTo('DELETE'), $this->equalTo($uri));

        $result = $this->curlMock->delete($uri);

        $this->assertNull($result);
    }
}