<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceAdmin
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Plugin\Ui\Component\Listing\Column\Order;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceAdmin\Model\Config;
use Fedex\MarketplaceAdmin\Plugin\Ui\Component\Listing\Column\Order\FlagPlugin;
use Mirakl\Adminhtml\Ui\Component\Listing\Column\Order\Flag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlagPluginTest extends TestCase
{
    private FlagPlugin $flagPlugin;

    private ToggleConfig|MockObject $toggleConfigMock;

    private Config|MockObject $configMock;

    private Flag|MockObject $subjectMock;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->configMock = $this->createMock(Config::class);
        $this->subjectMock = $this->createMock(Flag::class);

        $this->flagPlugin = new FlagPlugin(
            $this->toggleConfigMock,
            $this->configMock
        );
    }

    public function testAroundPrepareDataSourceWithoutToggleConfig(): void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(false);
        $dataSource = ['data' => ['items' => []]];

        $result = $this->flagPlugin->aroundPrepareDataSource(
            $this->subjectMock,
            function ($dataSource) {
                return $dataSource;
            },
            $dataSource
        );
        $this->assertSame($dataSource, $result);
    }

    public function testAroundPrepareDataSourceWithDisabledMktSelfreg(): void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(true);
        $this->configMock->method('isMktSelfregEnabled')->willReturn(false);
        $dataSource = ['data' => ['items' => []]];

        $result = $this->flagPlugin->aroundPrepareDataSource(
            $this->subjectMock,
            function ($dataSource) {
                return $dataSource;
            },
            $dataSource
        );
        $this->assertSame($dataSource, $result);
    }

    public function testAroundPrepareDataSourceWithEnabledMktSelfreg(): void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(true);
        $this->configMock->method('isMktSelfregEnabled')->willReturn(true);
        $dataSource = ['data' => ['items' => [['flag' => Config::ORIGIN_MIXED]]]];
        $this->subjectMock->method('getData')->willReturn('name');
        $result = $this->flagPlugin->aroundPrepareDataSource(
            $this->subjectMock,
            function ($dataSource) {
                return $dataSource;
            },
            $dataSource
        );
        $expectedResult = [
            'data' =>
                ['items' =>
                    [
                        [
                            'flag' => Config::ORIGIN_MIXED,
                            'name' => sprintf('<span class="%s">%s</span>', Config::MIXED_CLASS, __(Config::MIXED_LABEL))
                        ]
                    ]
                ]
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testDecorationFlagWithMixedFlag(): void
    {
        $item = ['flag' => Config::ORIGIN_MIXED];
        $result = $this->flagPlugin->decorationFlag($item);
        $this->assertSame(sprintf('<span class="%s">%s</span>', Config::MIXED_CLASS, __(Config::MIXED_LABEL)), $result);
    }

    public function testDecorationFlagWithInvalidFlag(): void
    {
        $item = ['flag' => 'invalid_flag'];
        $result = $this->flagPlugin->decorationFlag($item);
        $this->assertSame(sprintf('<span class="%s">%s</span>', Config::OPERATOR_CLASS, __(Config::OPERATOR_LABEL)), $result);
    }

}
