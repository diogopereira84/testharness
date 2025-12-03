<?php

namespace Fedex\Recaptcha\Test\Unit\Model\Frontend;

use PHPUnit\Framework\TestCase;
use Fedex\Recaptcha\Model\Frontend\ValidationConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigExtensionFactory;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterfaceFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

class ValidationConfigProviderTest extends TestCase
{
    private $scopeConfig;
    private $remoteAddress;
    private $validationConfigFactory;
    private $validationConfigExtensionFactory;
    private $validationConfigProvider;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->validationConfigFactory = $this->createMock(ValidationConfigInterfaceFactory::class);
        $this->validationConfigExtensionFactory = $this->createMock(ValidationConfigExtensionFactory::class);

        $this->validationConfigProvider = new ValidationConfigProvider(
            $this->scopeConfig,
            $this->remoteAddress,
            $this->validationConfigFactory,
            $this->validationConfigExtensionFactory
        );
    }

    public function testGetPrivateKey()
    {
        $reflection = new \ReflectionClass(ValidationConfigProvider::class);
        $method = $reflection->getMethod('getPrivateKey');
        $method->setAccessible(true);

        $this->scopeConfig->method('getValue')
            ->with(ValidationConfigProvider::XML_PATH_PRIVATE_KEY, ScopeInterface::SCOPE_STORE)
            ->willReturn('private_key');

        $result = $method->invoke($this->validationConfigProvider);

        $this->assertEquals('private_key', $result);
    }

    public function testGetValidationFailureMessage()
    {
        $reflection = new \ReflectionClass(ValidationConfigProvider::class);
        $method = $reflection->getMethod('getValidationFailureMessage');
        $method->setAccessible(true);

        $this->scopeConfig->method('getValue')
            ->with(ValidationConfigProvider::XML_PATH_VALIDATION_FAILURE, ScopeInterface::SCOPE_STORE)
            ->willReturn('Validation failed');

        $result = $method->invoke($this->validationConfigProvider);

        $this->assertEquals('Validation failed', $result);
    }

    public function testGetScoreThreshold()
    {
        $reflection = new \ReflectionClass(ValidationConfigProvider::class);
        $method = $reflection->getMethod('getScoreThreshold');
        $method->setAccessible(true);

        $this->scopeConfig->method('getValue')
            ->with(ValidationConfigProvider::XML_PATH_SCORE_THRESHOLD, ScopeInterface::SCOPE_STORE)
            ->willReturn(0.5);

        $result = $method->invoke($this->validationConfigProvider);

        $this->assertEquals(0.5, $result);
    }

    public function testGetScoreThresholdMin()
    {
        $reflection = new \ReflectionClass(ValidationConfigProvider::class);
        $method = $reflection->getMethod('getScoreThreshold');
        $method->setAccessible(true);

        $this->scopeConfig->method('getValue')
            ->with(ValidationConfigProvider::XML_PATH_SCORE_THRESHOLD, ScopeInterface::SCOPE_STORE)
            ->willReturn(0.05);

        $result = $method->invoke($this->validationConfigProvider);

        $this->assertEquals(0.1, $result);
    }

    public function testGetScoreThresholdMax()
    {
        $reflection = new \ReflectionClass(ValidationConfigProvider::class);
        $method = $reflection->getMethod('getScoreThreshold');
        $method->setAccessible(true);

        $this->scopeConfig->method('getValue')
            ->with(ValidationConfigProvider::XML_PATH_SCORE_THRESHOLD, ScopeInterface::SCOPE_STORE)
            ->willReturn(1.5);

        $result = $method->invoke($this->validationConfigProvider);

        $this->assertEquals(1.0, $result);
    }

    public function testGet()
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [ValidationConfigProvider::XML_PATH_PRIVATE_KEY, ScopeInterface::SCOPE_STORE, null, 'private_key'],
                [ValidationConfigProvider::XML_PATH_VALIDATION_FAILURE, ScopeInterface::SCOPE_STORE, null, 'Validation failed'],
                [ValidationConfigProvider::XML_PATH_SCORE_THRESHOLD, ScopeInterface::SCOPE_STORE, null, 0.5]
            ]);

        $this->remoteAddress->method('getRemoteAddress')
            ->willReturn('127.0.0.1');

        $extensionAttributes = $this->getMockBuilder(\Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigExtensionInterface::class)
            ->addMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes
            ->method('setData')
            ->with('scoreThreshold', 0.5);

        $this->validationConfigExtensionFactory->method('create')
            ->willReturn($extensionAttributes);

        $validationConfig = $this->createMock(ValidationConfigInterface::class);
        $this->validationConfigFactory->method('create')
            ->with([
                'privateKey' => 'private_key',
                'remoteIp' => '127.0.0.1',
                'validationFailureMessage' => 'Validation failed',
                'extensionAttributes' => $extensionAttributes,
            ])
            ->willReturn($validationConfig);

        $this->assertSame($validationConfig, $this->validationConfigProvider->get());
    }
}
