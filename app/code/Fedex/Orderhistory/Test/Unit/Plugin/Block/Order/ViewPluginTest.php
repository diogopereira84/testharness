<?php

namespace Fedex\Orderhistory\Test\Unit\Plugin\Block\Order;

use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Sales\Block\Order\View as OrderViewBlock;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\Orderhistory\Plugin\Block\Order\ViewPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ViewPluginTest extends TestCase
{
    /**
     * @var ViewPlugin
     */
    private $viewPlugin;

    /**
     * @var OrderHistoryHelper|MockObject
     */
    private $orderHistoryDataHelper;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfig;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        $this->orderHistoryDataHelper = $this->getMockBuilder(OrderHistoryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPrintReceiptRetail'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->viewPlugin = $objectManager->getObject(
            ViewPlugin::class,
            [
                'orderHistoryDataHelper' => $this->orderHistoryDataHelper,
                'toggleConfig' => $this->toggleConfig,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Test the afterGetTemplate method with coreOverrideToggle enabled and print receipt retail condition met.
     */
    public function testAfterGetTemplateWithOverrides(): void
    {
        $coreOverrideToggleValue = true;
        $printReceiptRetailValue = true;

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn($coreOverrideToggleValue);

        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isPrintReceiptRetail')
            ->willReturn($printReceiptRetailValue);

        $orderViewBlock = $this->createMock(OrderViewBlock::class);
        $orderViewBlock->method('getTemplate')
            ->willReturn('Magento_Sales::order/view.phtml');

        $result = $this->viewPlugin->afterGetTemplate($orderViewBlock, 'Magento_Sales::order/view.phtml');
        $this->assertEmpty($result, 'Magento_Sales::order/view.phtml');
    }

    /**
     * Test the afterGetTemplate method with coreOverrideToggle enabled and print receipt retail condition met.
     */
    public function testAfterGetTemplateWithOverridesAndPintReciptDisabled(): void
    {
        $coreOverrideToggleValue = true;
        $printReceiptRetailValue = true;
        $testMethod = new \ReflectionMethod(ViewPlugin::class, 'afterGetTemplate');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn($coreOverrideToggleValue);

        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isPrintReceiptRetail')
            ->willReturn($printReceiptRetailValue);
        $orderViewBlock = $this->createMock(OrderViewBlock::class);
        $orderViewBlock->method('getTemplate')
        ->willReturn('');
        $expectedResult = $testMethod->invoke($this->viewPlugin, $orderViewBlock, '');
        $this->assertEquals('', $expectedResult);
    }

    /**
     * Test the afterGetTemplate method without overrides.
     */
    public function testAfterGetTemplateWithoutOverrides(): void
    {
        $coreOverrideToggleValue = false;
        $printReceiptRetailValue = false;

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn($coreOverrideToggleValue);

        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isPrintReceiptRetail')
            ->willReturn($printReceiptRetailValue);

        $orderViewBlock = $this->createMock(OrderViewBlock::class);
        $orderViewBlock->method('getTemplate')
            ->willReturn('Magento_Sales::order/view.phtml');

        $result = $this->viewPlugin->afterGetTemplate($orderViewBlock, 'Magento_Sales::order/view.phtml');
        $this->assertEquals('Magento_Sales::order/view.phtml', $result);
    }
}
