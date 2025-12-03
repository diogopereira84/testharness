<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Model\Config;

use Fedex\OktaMFTF\Model\Config\Credentials;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CredentialsTest extends TestCase
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

        $this->config = new Credentials($this->scopeConfigMock, $this->urlMock);
    }

    public function testGetDomain()
    {
        $domain = 'domain.com';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/credentials/domain', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($domain);
        $this->assertEquals($domain, $this->config->getDomain());
    }

    public function testGetAuthorizationServerId()
    {
        $authorizationServerId = '1234567';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/credentials/authorization_server_id', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($authorizationServerId);
        $this->assertEquals($authorizationServerId, $this->config->getAuthorizationServerId());
    }

    public function testGetGrantType()
    {
        $grantType = 'all';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/credentials/grant_type', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($grantType);
        $this->assertEquals($grantType, $this->config->getGrantType());
    }

    public function testGetScope()
    {
        $scope = 'oob';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('okta_mftf/credentials/scope', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($scope);
        $this->assertEquals($scope, $this->config->getScope());
    }
}