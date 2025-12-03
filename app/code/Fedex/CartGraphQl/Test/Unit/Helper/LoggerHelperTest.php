<?php

namespace Fedex\CartGraphQl\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\NewRelicReporting\Model\NewRelicWrapper;
use Magento\Framework\Session\SessionManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class LoggerHelperTest extends TestCase
{
    /**
     * @var LoggerHelper
     */
    protected LoggerHelper $instance;

    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @var LoggerHelper
     */
    protected LoggerHelper $loggerObject;
    protected NewRelicWrapper $newRelicWrapper;
    protected SessionManagerInterface $sessionManager;
    protected ToggleConfig $toggleConfig;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->newRelicWrapper = $this->createMock(NewRelicWrapper::class);
        $this->sessionManager = $this->createMock(SessionManagerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->context->method('getLogger')->willReturn($this->logger);
        $this->sessionManager->method('getSessionId')->willReturn('test-session-id');
        $this->loggerObject = new LoggerHelper($this->context, $this->logger, $this->newRelicWrapper, $this->sessionManager, $this->toggleConfig);
    }

    /**
     * Test info method logs a message with context
     *
     * @return void
     */
    public function testinfo()
    {
        $message = "Test info message";
        $context = ['key' => 'value'];
        $this->assertEquals('', $this->loggerObject->info($message, $context));
    }

    /**
     * Test info method logs a message with context
     *
     * @return void
     */
    public function testInfoElse()
    {
        $message = "Test info message";
        $context = [];
        $this->assertEquals('', $this->loggerObject->info($message, $context));
    }

    /**
     * Test error method logs a message with context
     *
     * @return void
     */
    public function testerror()
    {
        $message = "Test error message";
        $context = ['key' => 'value'];
        $this->loggerObject->error($message, $context);
    }

    /**
     * Test error method logs a message with context
     *
     * @return void
     */
    public function testErrorElse()
    {
        $message = "Test error message";
        $context = [];
        $this->assertEquals('', $this->loggerObject->error($message, $context));
    }

    /**
     * Test critical method logs a message with context
     *
     * @return void
     */
    public function testcritical()
    {
        $message = "Test critical message";
        $context = ['key' => 'value'];
        $this->loggerObject->critical($message, $context);
    }

    /**
     * Test critical method logs a message with context
     *
     * @return void
     */
    public function testCriticalElse()
    {
        $message = "Test critical message";
        $context = [];
        $this->assertEquals('', $this->loggerObject->critical($message, $context));
    }

    /**
     * Test setExtraDataInContext method adds message and timestamp to context
     *
     * @return void
     */
    protected function testSetExtraDataInContext()
    {
        $context = ['key' => 'value'];
        $message = "Test message";
        $result = $this->loggerObject->setExtraDataInContext($context, $message);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals($message, $result['message']);
    }

    /**
     * Test setExtraDataInContext method returns empty array when context is not an array
     *
     * @return void
     */
    protected function testSetExtraDataInContextElse()
    {
        $context = 'tet';
        $message = "Test message";
        $result = $this->loggerObject->setExtraDataInContext($context, $message);
        $this->assertEmpty($result);
    }

    /**
     * Test setExtraDataInContext method returns empty array when context is not an array
     *
     * @dataProvider nonArrayContextProvider
     * @return void
     */
    public function testSetExtraDataInContextReturnsEmptyArrayWhenContextIsNotArray($nonArrayContext)
    {
        $message = "Test message";
        $reflectionClass = new \ReflectionClass(LoggerHelper::class);
        $method = $reflectionClass->getMethod('setExtraDataInContext');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->loggerObject, [$nonArrayContext, $message]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Provides non-array values for testing
     *
     * @return array
     */
    public function nonArrayContextProvider(): array
    {
        return [
            'string' => ['test string'],
            'integer' => [123],
            'float' => [123.45],
            'boolean' => [true],
            'null' => [null],
            'object' => [new \stdClass()]
        ];
    }
}
