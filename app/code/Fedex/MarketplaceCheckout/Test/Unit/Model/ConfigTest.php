<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\Config;
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
    private $configModel;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->configModel = new Config($this->scopeConfigMock);
    }

    /**
     * @dataProvider configValueProvider
     * @param string $method
     * @param string $xmlPath
     * @param mixed $configValue
     * @param bool $expectedResult
     */
    public function testConfigMethods(string $method, string $xmlPath, $configValue, bool $expectedResult): void
    {
        $scope = ScopeInterface::SCOPE_STORE;

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($xmlPath, $scope)
            ->willReturn($configValue);

        $this->assertSame($expectedResult, $this->configModel->$method($scope));
    }

    /**
     * @return array
     */
    public function configValueProvider(): array
    {
        return [
            'Shipping Management Refactor Enabled' => [
                'isShippingManagementRefactorEnabled',
                Config::XML_PATH_SHIPPING_MANAGEMENT_REFACTOR,
                '1',
                true
            ],
            'Shipping Management Refactor Disabled' => [
                'isShippingManagementRefactorEnabled',
                Config::XML_PATH_SHIPPING_MANAGEMENT_REFACTOR,
                '0',
                false
            ],
            'Marketplace Commercial Enabled' => [
                'isMarketplaceEnabledForCommercialSites',
                Config::XML_PATH_ENABLE_MARKETPLACE_COMMERCIAL,
                true,
                true
            ],
            'Marketplace Commercial Disabled' => [
                'isMarketplaceEnabledForCommercialSites',
                Config::XML_PATH_ENABLE_MARKETPLACE_COMMERCIAL,
                false,
                false
            ],
            'Incorrect Shipping Totals Enabled' => [
                'isIncorrectShippingTotalsToggleEnabled',
                Config::XML_PATH_INCORRECT_SHIPPING_TOTALS,
                1,
                true
            ],
            'Incorrect Shipping Totals Disabled' => [
                'isIncorrectShippingTotalsToggleEnabled',
                Config::XML_PATH_INCORRECT_SHIPPING_TOTALS,
                0,
                false
            ],
            'Incorrect Package Count Enabled' => [
                'isIncorrectPackageCountToggleEnabled',
                Config::XML_PATH_INCORRECT_PACKAGE_COUNT,
                '1',
                true
            ],
            'Incorrect Package Count Disabled by string "0"' => [
                'isIncorrectPackageCountToggleEnabled',
                Config::XML_PATH_INCORRECT_PACKAGE_COUNT,
                '0',
                false
            ],
            'Incorrect Package Count Disabled by null' => [
                'isIncorrectPackageCountToggleEnabled',
                Config::XML_PATH_INCORRECT_PACKAGE_COUNT,
                null,
                false
            ],
        ];
    }
}