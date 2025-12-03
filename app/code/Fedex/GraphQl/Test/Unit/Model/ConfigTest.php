<?php

declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model;

use Fedex\GraphQl\Api\ToggleConfigInterface;
use Fedex\GraphQl\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Config
     */
    private $model;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->model = new Config($this->scopeConfigMock);
    }

    /**
     * @dataProvider configValuesProvider
     * @param mixed $configValue
     * @param bool $expectedResult
     * @param string|null $scope
     * @param string $expectedScope
     */
    public function testIsGraphqlRequestErrorLogsEnabled($configValue, bool $expectedResult, ?string $scope, string $expectedScope): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ToggleConfigInterface::XML_PATH_D235836_LOGS_ENABLED, $expectedScope)
            ->willReturn($configValue);

        $this->assertSame($expectedResult, $this->model->isGraphqlRequestErrorLogsEnabled());
    }

    /**
     * @return array
     */
    public function configValuesProvider(): array
    {
        return [
            'Enabled with default scope' => [true, true, null, ScopeInterface::SCOPE_STORE],
            'Disabled with default scope' => [false, false, null, ScopeInterface::SCOPE_STORE]
        ];
    }
}