<?php

namespace Fedex\Catalog\Test\Unit\Plugin\Admin;

use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Plugin\Admin\SortConfigurableAssociatedProductPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\ConfigurableProduct\Ui\DataProvider\Product\Form\Modifier\Composite;

class SortConfigurableAssociatedProductPluginTest extends TestCase
{
    private $toggleConfigMock;
    private $plugin;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->plugin = new SortConfigurableAssociatedProductPlugin($this->toggleConfigMock);
    }

    public function testAfterModifyDataReturnsUnchangedIfResultEmpty()
    {
        $subject = $this->createMock(Composite::class);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $result = [];
        $this->assertSame([], $this->plugin->afterModifyData($subject, $result));
    }

    public function testAfterModifyDataReturnsUnchangedIfToggleDisabled()
    {
        $subject = $this->createMock(Composite::class);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $result = [
            123 => [
                'configurable-matrix' => [
                    ['price' => 20],
                    ['price' => 10]
                ]
            ]
        ];
        $this->assertSame($result, $this->plugin->afterModifyData($subject, $result));
    }

    public function testAfterModifyDataSortsConfigurableMatrixByPrice()
    {
        $subject = $this->createMock(Composite::class);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $result = [
            123 => [
                'configurable-matrix' => [
                    ['price' => 20],
                    ['price' => 10],
                    ['price' => 15]
                ]
            ]
        ];
        $expected = [
            123 => [
                'configurable-matrix' => [
                    ['price' => 10],
                    ['price' => 15],
                    ['price' => 20]
                ]
            ]
        ];
        $actual = $this->plugin->afterModifyData($subject, $result);
        $this->assertSame($expected, $actual);
    }

    public function testAfterModifyDataNoConfigurableMatrix()
    {
        $subject = $this->createMock(Composite::class);
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $result = [
            123 => [
                'other-data' => []
            ]
        ];
        $this->assertSame($result, $this->plugin->afterModifyData($subject, $result));
    }
}

