<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Api\ToggleConfigInterface;
use Fedex\Customer\Model\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ToggleConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ToggleConfig
     */
    private $toggleConfig;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = new ToggleConfig($this->scopeConfigMock);
    }

    /**
     * @param bool $configValue
     * @param bool $expectedResult
     * @dataProvider isAdminResetCardUpdateToggleEnabledDataProvider
     */
    public function testIsAdminResetCardUpdateToggleEnabled(bool $configValue, bool $expectedResult): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ToggleConfigInterface::XML_PATH_ADMIN_RESET_CART_UPDATE_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($configValue);

        $this->assertEquals(
            $expectedResult,
            $this->toggleConfig->isAdminResetCardUpdateToggleEnabled(ScopeInterface::SCOPE_STORE)
        );
    }

    /**
     * @return array
     */
    public function isAdminResetCardUpdateToggleEnabledDataProvider(): array
    {
        return [
            'Enabled' => [
                'configValue' => true,
                'expectedResult' => true,
            ],
            'Disabled' => [
                'configValue' => false,
                'expectedResult' => false,
            ],
        ];
    }
}