<?php
/**
 * @category Fedex
 * @package  Fedex_SubmitOrderSidebar
 * @copyright  Copyright (c) 2022 Fedex
 * @author  Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Plugin;

use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use Magento\NewRelicReporting\Model\NewRelicWrapper;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Plugin\NewRelicCustomAttributeOrderFlowOptimized;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class NewRelicCustomAttributeOrderFlowOptimizedTest extends TestCase
{
    protected $newRelicWrapperMock;
    protected $submitOrderApiMock;
    protected $pluginMock;
    public const ERROR_MESSAGE = "test message";

    public const ERROR_TEXT = "error";

    public const RESPONSE_TEXT = "response";

    public const RESPONSE_MESSAGE = "test";
    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->newRelicWrapperMock = $this->createMock(newRelicWrapper::class);
        $this->submitOrderApiMock = $this->createMock(SubmitOrderApi::class);

        $this->pluginMock = (new ObjectManager($this))->getObject(
            NewRelicCustomAttributeOrderFlowOptimized::class,
            [
                'newRelicWrapper' => $this->newRelicWrapperMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testAfterCallFujitsuRateQuoteApi(): void
    {
        $result=[self::ERROR_TEXT=>1,'msg'=>self::ERROR_MESSAGE,self::RESPONSE_TEXT=>self::RESPONSE_MESSAGE];
        $this->newRelicWrapperMock->expects($this->any())->method('isExtensionInstalled')->willReturn($result);
        $this->newRelicWrapperMock->expects($this->exactly(4))->method('addCustomParameter')->willReturn(true);
        $this->assertEquals($result, $this->pluginMock->afterCallFujitsuRateQuoteApi($this->submitOrderApiMock,$result));
    }

    /**
     * @return void
     */
    public function testAfterCallFujitsuRateQuoteApiTwoError(): void
    {
        $result=[self::ERROR_TEXT=>2,'msg'=>self::ERROR_MESSAGE,self::RESPONSE_TEXT=>self::RESPONSE_MESSAGE];
        $this->newRelicWrapperMock->expects($this->any())->method('isExtensionInstalled')->willReturn($result);
        $this->newRelicWrapperMock->expects($this->exactly(4))->method('addCustomParameter')->willReturn(true);
        $this->assertEquals($result, $this->pluginMock->afterCallFujitsuRateQuoteApi($this->submitOrderApiMock,$result));
    }

    /**
     * @return void
     */
    public function testAfterCallFujitsuRateQuoteApiDefaultError(): void
    {
        $result=[self::ERROR_TEXT=>0,'msg'=>self::ERROR_MESSAGE,self::RESPONSE_TEXT=>self::RESPONSE_MESSAGE];
        $this->newRelicWrapperMock->expects($this->any())->method('isExtensionInstalled')->willReturn($result);
        $this->newRelicWrapperMock->expects($this->exactly(4))->method('addCustomParameter')->willReturn(true);
        $this->assertEquals($result, $this->pluginMock->afterCallFujitsuRateQuoteApi($this->submitOrderApiMock,$result));
    }
}
