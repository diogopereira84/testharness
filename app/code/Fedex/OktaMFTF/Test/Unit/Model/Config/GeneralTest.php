<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Model\Config;

use Fedex\OktaMFTF\Model\Config\General;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeneralTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Url & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlMock;
    protected $config;
    private const METHOD_GET_VALUE = 'getValue';
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            ['getValue', 'isSetFlag']
        );
        $this->urlMock = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = new General($this->scopeConfigMock, $this->urlMock);
    }

    public function testIsEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/general/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertEquals(true, $this->config->isEnabled());
    }

    public function testIsLogEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/general/log_enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertEquals(true, $this->config->isLogEnabled());
    }

    public function testGetAdminUser()
    {
        $user = '65435';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/general/admin_user', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($user);
        $this->assertEquals($user, $this->config->getAdminUser());
    }
}