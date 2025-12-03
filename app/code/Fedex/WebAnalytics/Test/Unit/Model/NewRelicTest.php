<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2024.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\WebAnalytics\Model\NewRelic;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class NewRelicTest extends TestCase
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
            ->with(NewRelic::XML_PATH_FEDEX_NEWRELIC_ACTIVE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $newRelic = new NewRelic($scopeConfigMock);
        $this->assertEquals(true, $newRelic->isActive());
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
            ->with(NewRelic::XML_PATH_FEDEX_NEWRELIC_SCRIPT_CODE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($scriptCode);
        $newRelic = new NewRelic($scopeConfigMock);
        $this->assertEquals($scriptCode, $newRelic->getScriptCode());
    }
}
