<?php

namespace Fedex\Recaptcha\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Recaptcha\Model\CaptchaTypeResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CaptchaTypeResolverTest extends TestCase
{
    private $scopeConfig;
    private $captchaTypeResolver;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->captchaTypeResolver = new CaptchaTypeResolver($this->scopeConfig);
    }

    public function testGetCaptchaTypeFor()
    {
        $key = 'some_key';
        $expectedType = 'some_type';

        $this->scopeConfig->method('getValue')
            ->with(CaptchaTypeResolver::XML_PATH_TYPE_FOR . $key, ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedType);

        $this->assertEquals($expectedType, $this->captchaTypeResolver->getCaptchaTypeFor($key));
    }

    public function testGetCaptchaTypeForReturnsNull()
    {
        $key = 'some_key';

        $this->scopeConfig->method('getValue')
            ->with(CaptchaTypeResolver::XML_PATH_TYPE_FOR . $key, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);

        $this->assertNull($this->captchaTypeResolver->getCaptchaTypeFor($key));
    }
}
