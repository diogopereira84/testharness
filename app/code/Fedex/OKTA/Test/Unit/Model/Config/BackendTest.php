<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Config;

use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\OKTA\Model\Config\Backend;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    protected $config;
    private const METHOD_GET_VALUE = 'getValue';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var JsonValidator|MockObject
     */
    private $jsonValidatorMock;

    /**
     * @var PublicCookieMetadata|MockObject
     */
    private $publicCookieMetadata;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookieMetadataFactoryMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookieManagerMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            ['getValue', 'isSetFlag']
        );
        $this->jsonMock = $this->createMock(Json::class);
        $this->jsonValidatorMock = $this->createMock(JsonValidator::class);
        $this->publicCookieMetadata = $this->getMockBuilder(PublicCookieMetadata::class)
            ->setMethods(['setDuration', 'setPath'])->getMock();
        $this->cookieMetadataFactoryMock = $this->createMock(CookieMetadataFactory::class);
        $this->cookieManagerMock = $this->getMockForAbstractClass(CookieManagerInterface::class);
        $this->urlMock = $this->getMockBuilder(Url::class)->disableOriginalConstructor()
            ->setMethods(['getUrl', 'setNoSecret'])->getMock();

        $this->config = new Backend(
            $this->jsonMock,
            $this->jsonValidatorMock,
            $this->scopeConfigMock,
            $this->cookieMetadataFactoryMock,
            $this->cookieManagerMock,
            $this->urlMock
        );
    }

    public function testIsEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertTrue($this->config->isEnabled());
    }

    public function testGetClientId()
    {
        $clientId = '123';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/client_id', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($clientId);
        $this->assertEquals($clientId, $this->config->getClientId());
    }

    public function testGetClientSecret()
    {
        $clientSecret = '9718728712';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/client_secret', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($clientSecret);
        $this->assertEquals($clientSecret, $this->config->getClientSecret());
    }

    public function testGetRedirectUrl()
    {
        $domain = 'http://domain.com/admin/auth/login/';
        $this->urlMock->expects($this->once())->method('setNoSecret');
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/redirect_uri', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('oauth2/code/okta/');
        $this->urlMock->expects($this->once())->method('getUrl')
            ->with('admin/auth/login')
            ->willReturn($domain);
        $this->assertEquals($domain . 'oauth2/code/okta/', $this->config->getRedirectUrl());
    }

    public function testGetDomain()
    {
        $domain = 'domain.com';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/domain', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($domain);
        $this->assertEquals($domain, $this->config->getDomain());
    }

    public function testGetRoles()
    {
        $roles = '[{"value":"0","label":"None"},{"value":"1","label":"Test 01"},{"value":"2","label":"Test 02"}]';
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/roles', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($roles);
        $this->jsonValidatorMock->expects($this->once())->method('isValid')
            ->with($roles)
            ->willReturn(true);
        $this->assertEquals(json_decode($roles, true), $this->config->getRoles());
    }

    public function testGetRolesInvalid()
    {
        $roles = null;
        $this->scopeConfigMock->expects($this->once())->method(self::METHOD_GET_VALUE)
            ->with('fedex_okta/backend/roles', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($roles);
        $this->jsonValidatorMock->expects($this->once())->method('isValid')
            ->with($roles)
            ->willReturn(false);
        $this->assertEquals([], $this->config->getRoles());
    }

    public function testGetNonce()
    {
        $nonce = '637b9a826fc06';
        $this->cookieManagerMock->expects($this->once())->method('getCookie')->willReturn($nonce);
        $this->assertEquals($nonce, $this->config->getNonce());
    }

    public function testGetNonceFailed()
    {
        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->expects($this->once())
            ->method('setDuration')
            ->willReturn($this->publicCookieMetadata);
        $this->assertEquals(13, strlen($this->config->getNonce()));
    }
}
