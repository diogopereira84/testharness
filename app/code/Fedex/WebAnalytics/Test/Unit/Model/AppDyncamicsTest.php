<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\WebAnalytics\Model\AppDynamicsConfigConfig;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class AppDyncamicsTest extends TestCase
{
    const GET_VALUE = 'getValue';
    const IS_SET_FLAG = 'isSetFlag';

    public function testIsActive(): void
    {
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_VALUE, self::IS_SET_FLAG]
        );
        $scopeConfigMock->expects($this->once())->method(self::IS_SET_FLAG)
            ->with(AppDynamicsConfigConfig::XML_PATH_ACTIVE_APP_DYNAMICS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $appDynamics = new AppDynamicsConfigConfig($scopeConfigMock);
        $this->assertEquals(true, $appDynamics->isActive());
    }

    public function testGetScriptCode(): void
    {

        $scriptCode = '<script type="text/javascript">
                    console.log(1);
                </script>';
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_VALUE, self::IS_SET_FLAG]
        );
        $scopeConfigMock->expects($this->once())->method(self::GET_VALUE)
            ->with(AppDynamicsConfigConfig::XML_PATH_APP_DYNAMICS_SCRIPT, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($scriptCode);
        $appDynamics = new AppDynamicsConfigConfig($scopeConfigMock);
        $this->assertEquals($scriptCode, $appDynamics->getScriptCode());
    }
}
