<?php

declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Model;

use Fedex\Catalog\Model\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Fedex\Catalog\Model\ToggleConfig
 */
class ToggleConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ToggleConfig
     */
    private ToggleConfig $toggleConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->toggleConfig = new ToggleConfig(
            $this->scopeConfigMock
        );
    }

    /**
     * Data provider for isEssendantToggleEnabled test cases.
     *
     * @return array
     */
    public function toggleDataProvider(): array
    {
        return [
            'enabled with string 1' => ['1', true],
            'disabled with string 0' => ['0', false],
            'enabled with integer 1' => [1, true],
            'disabled with integer 0' => [0, false],
            'disabled with null' => [null, false],
            'disabled with empty string' => ['', false],
        ];
    }

    /**
     * Test isEssendantToggleEnabled method with various config values.
     *
     * @dataProvider toggleDataProvider
     * @param mixed $configValue The mocked configuration value.
     * @param bool $expectedResult The expected boolean result.
     */
    public function testIsEssendantToggleEnabled(mixed $configValue, bool $expectedResult): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ToggleConfig::XML_PATH_ESSENDANT_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($configValue);

        $this->assertSame($expectedResult, $this->toggleConfig->isEssendantToggleEnabled());
    }

    /**
     * Test isEssendantToggleEnabled method with a custom scope.
     */
    public function testIsEssendantToggleEnabledWithCustomScope(): void
    {
        $customScope = 'websites';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ToggleConfig::XML_PATH_ESSENDANT_TOGGLE,
                $customScope
            )
            ->willReturn('1');

        $this->assertTrue($this->toggleConfig->isEssendantToggleEnabled($customScope));
    }
}