<?php

namespace Fedex\EnvironmentManager\Test\Unit\Block\System\Config;

use Fedex\EnvironmentManager\Block\System\Config\CsvExportButton;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Layout;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CsvExportButtonTest extends TestCase
{
    /** @var Context|MockObject */
    private $contextMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var CsvExportButton|MockObject */
    private $csvExportButton;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->csvExportButton = $this->getMockBuilder(CsvExportButton::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_toHtml', 'getLayout', '__construct'])
            ->getMock();

        // Use reflection on the real class, not the mock class
        $reflection = new \ReflectionClass(CsvExportButton::class);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue($this->csvExportButton, $this->loggerMock);
    }

    public function testConstructorInitializesLogger()
    {
        $reflection = new \ReflectionClass(CsvExportButton::class);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);
        $this->assertSame($this->loggerMock, $property->getValue($this->csvExportButton));
    }

    public function testGetElementHtmlReturnsToHtml()
    {
        $elementMock = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->csvExportButton->expects($this->once())
            ->method('_toHtml')
            ->willReturn('html');

        // Call protected method via reflection
        $reflection = new \ReflectionClass($this->csvExportButton);
        $method = $reflection->getMethod('_getElementHtml');
        $method->setAccessible(true);
        $result = $method->invoke($this->csvExportButton, $elementMock);

        $this->assertEquals('html', $result);
    }

    public function testGetButtonHtmlReturnsButtonHtml()
    {
        $layoutMock = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buttonMock = $this->getMockBuilder(WidgetButton::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buttonMock->expects($this->once())
            ->method('setData')
            ->with([
                'id' => 'export_toggle_report_csv',
                'label' => __('Export')
            ])
            ->willReturnSelf();

        $buttonMock->expects($this->once())
            ->method('toHtml')
            ->willReturn('button_html');

        $layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(WidgetButton::class)
            ->willReturn($buttonMock);

        $this->csvExportButton->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $result = $this->csvExportButton->getButtonHtml();
        $this->assertEquals('button_html', $result);
    }

    public function testGetButtonHtmlReturnsEmptyStringOnException()
    {
        $layoutMock = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buttonMock = $this->getMockBuilder(WidgetButton::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buttonMock->expects($this->once())
            ->method('setData')
            ->with([
                'id' => 'export_toggle_report_csv',
                'label' => __('Export')
            ])
            ->willReturn(null);

        $layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(WidgetButton::class)
            ->willReturn($buttonMock);

        $this->csvExportButton->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to create toggle export CSV button block.'));

        $result = $this->csvExportButton->getButtonHtml();
        $this->assertSame('', $result);
    }
}
