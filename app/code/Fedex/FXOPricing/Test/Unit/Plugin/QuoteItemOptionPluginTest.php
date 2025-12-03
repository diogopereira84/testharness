<?php

namespace Fedex\FXOPricing\Test\Unit\Plugin;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Plugin\QuoteItemOptionPlugin;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QuoteItemOptionPluginTest extends TestCase
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     * JSON Serializer instance used for handling JSON encoding and decoding.
     */
    private $jsonSerializer;

    /**
     * @var mixed $toggleConfig
     * A private property used to store configuration or toggle settings
     * for the unit test. Its specific purpose and type depend on the
     * context of the test implementation.
     */
    private $toggleConfig;
    /**
     * @var \Psr\Log\LoggerInterface
     * Logger instance used for logging within the test class.
     */
    private $logger;
    /**
     * @var \Fedex\FXOPricing\Plugin\QuoteItemOptionPlugin
     * The plugin instance being tested in the unit test.
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->jsonSerializer = $this->createMock(Json::class);
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->plugin = new QuoteItemOptionPlugin(
            $this->jsonSerializer,
            $this->toggleConfig,
            $this->logger
        );
    }

    public function testBeforeBeforeSave()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();

        $value = '{"external_prod":[{"externalSkus":[{"unitPrice":0}]}]}';
        $modifiedValue = '{"external_prod":[{"externalSkus":[{}]}]}';

        $this->jsonSerializer->method('unserialize')->willReturn(json_decode($value, true));
        $this->jsonSerializer->method('serialize')->willReturn($modifiedValue);

        $option->method('getValue')->willReturn($value);
        $option->expects($this->any())->method('setValue')->with($modifiedValue);

        $this->assertEquals("", $this->plugin->beforeBeforeSave($option));
    }

    public function testBeforeBeforeSaveIfToggleisOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();

        $value = '{"external_prod":[{"externalSkus":[{"unitPrice":0}]}]}';
        $modifiedValue = '{"external_prod":[{"externalSkus":[{}]}]}';

        $option->method('getValue')->willReturn($value);
        $option->expects($this->any())->method('setValue')->with($modifiedValue);
        $this->assertEquals("", $this->plugin->beforeBeforeSave($option));
    }

    public function testBeforeBeforeSaveIfPriceIsNotZero()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();

        $value = '{"external_prod":[{"externalSkus":[{"unitPrice":1}]}]}';
        $modifiedValue = '{"external_prod":[{"externalSkus":[{"test":"test"}]}]}';

        $this->jsonSerializer->method('unserialize')->willReturn(json_decode($value, true));
        $this->jsonSerializer->method('serialize')->willReturn($modifiedValue);

        $option->method('getValue')->willReturn($value);
        $option->expects($this->any())->method('setValue')->with($modifiedValue);

        $this->assertEquals("", $this->plugin->beforeBeforeSave($option));
    }

    public function testLoggerErrorIsCalledOnException()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();

        $exceptionMessage = "An error occurred!";
        $option->method('getValue')->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage);

        $this->assertEquals("", $this->plugin->beforeBeforeSave($option));
    }

    public function testBeforeBeforeSaveIfSkippedCode()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue', 'getCode'])
            ->getMock();

        $value = '15604_22_1_37_1_67_1_70_1';
        $option->method('getValue')->willReturn($value);
        $option->method('getCode')->willReturn('bundle_identity');

        $this->assertEquals("", $this->plugin->beforeBeforeSave($option));
    }
}
