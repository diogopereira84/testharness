<?php
namespace Fedex\HttpRequestTimeout\Test\Unit\Model;

use Fedex\HttpRequestTimeout\Api\ConfigManagementInterface;
use Fedex\HttpRequestTimeout\Model\TimeoutValidator;
use PHPUnit\Framework\TestCase;

class TimeoutValidatorTest extends TestCase
{
    /**
     * @var TimeoutValidator
     */
    private $timeoutValidator;

    protected function setUp(): void
    {
        $this->timeoutValidator = new TimeoutValidator();
    }

    public function testIsSuitableForDefinedTimeout()
    {
        $urlsWithTimeout = [
            'http://example.com' => [ConfigManagementInterface::TIMEOUT_PARAMETER => 30]
        ];
        $uri = 'http://example.com';
        $this->assertTrue($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri));

        $urlsWithTimeout = [
            'http://example.com' => [ConfigManagementInterface::TIMEOUT_PARAMETER => 0]
        ];
        $this->assertFalse($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri));

        $urlsWithTimeout = [
            'http://example.com' => [ConfigManagementInterface::TIMEOUT_PARAMETER => 'invalid']
        ];
        $this->assertFalse($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri));

        $urlsWithTimeout = [];
        $this->assertFalse($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri));
    }

    public function testIsSuitableForDefaultTimeout()
    {
        $urlsWithTimeout = [];
        $uri = 'http://example.com';
        $defaultTimeoutEnabled = true;
        $this->assertTrue($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled));

        $defaultTimeoutEnabled = false;
        $this->assertFalse($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled));

        $urlsWithTimeout = [
            'http://example.com' => [ConfigManagementInterface::TIMEOUT_PARAMETER => 30]
        ];
        $this->assertFalse($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled));
    }
}
