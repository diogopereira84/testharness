<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\Canva\Model\LoginConfig;
use Fedex\SSO\Model\Config as SSOConfig;
use Fedex\Canva\ViewModel\ConfigAdapter;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ConfigAdapterTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private const TOGGLE_FEATURE_KEY = 'enable_canva_buttons_new_flow';

    private ConfigAdapter $configAdapterMock;
    private UrlInterface|MockObject $urlMock;
    private MockObject|SSOConfig $ssoConfigMock;
    private LoginConfig|MockObject $loginConfigMock;
    private MockObject|ToggleConfig $toggleConfigMock;
    private MockObject|Repository $assetRepository;

    protected function setUp(): void
    {
        $this->urlMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->ssoConfigMock = $this->getMockBuilder(SSOConfig::class)
            ->onlyMethods(['isEnabled', 'getRegisterUrl', 'getRegisterUrlParameter', 'getLoginPageURL', 'getQueryParameter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loginConfigMock = $this->getMockBuilder(LoginConfig::class)
            ->onlyMethods(['getTitle', 'getDescription', 'getRegisterButtonLabel', 'getLoginButtonLabel', 'getContinueButtonLabel'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
             ->getMock();
        $this->assetRepository = $this->createMock(Repository::class);

        $this->objectManager = new ObjectManager($this);
        $this->configAdapterMock = $this->objectManager->getObject(
            ConfigAdapter::class,
            [
                'url' => $this->urlMock,
                'ssoConfig' => $this->ssoConfigMock,
                'loginConfig' => $this->loginConfigMock,
                'toggleConfig' => $this->toggleConfigMock,
                'assetRepository' => $this->assetRepository
            ]
        );
    }

    public function testIsEnabled()
    {
        $this->ssoConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals(true, $configAdapter->isEnabled());
    }

    public function testGetTitle()
    {
        $title = 'title';
        $this->loginConfigMock->expects($this->once())->method('getTitle')->willReturn($title);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals($title, $configAdapter->getTitle());
    }

    public function testGetDescription()
    {
        $description = 'desc';
        $this->loginConfigMock->expects($this->once())->method('getDescription')->willReturn($description);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals($description, $configAdapter->getDescription());
    }

    public function testGetRegisterButtonLabel()
    {
        $label = 'register';
        $this->loginConfigMock->expects($this->once())->method('getRegisterButtonLabel')->willReturn($label);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals($label, $configAdapter->getRegisterButtonLabel());
    }

    public function testGetRegisterButtonHrefWithToggleOn()
    {
        $loginUrl = 'https://some-url.com';
        $queryParam = 'redirectUrl';
        $baseUrl = 'https://some-url.com/';
        $rcParam = 'oauth/index/index/rc/aHR0cHM6Ly9zb21lLXVybC5jb20v';
        $this->ssoConfigMock->expects($this->once())->method('getRegisterUrl')->willReturn($loginUrl);
        $this->ssoConfigMock->expects($this->once())->method('getRegisterUrlParameter')->willReturn($queryParam);
        $this->urlMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->urlMock->expects($this->once())->method('getUrl')->willReturn($baseUrl);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals(
            $loginUrl . '?' . $queryParam . '=' . $baseUrl . $rcParam,
            $configAdapter->getRegisterButtonHref()
        );
    }

    public function testGetLoginButtonLabel()
    {
        $label = 'login';
        $this->loginConfigMock->expects($this->once())->method('getLoginButtonLabel')->willReturn($label);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals($label, $configAdapter->getLoginButtonLabel());
    }

    public function testGetLoginButtonHrefWithToggleOn(): void
    {
        $loginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $canvaUrl = 'https://office.fedex.com/canva/index/login';
        $baseUrl = 'https://some-url.com/';
        $rcParam = 'oauth/index/index/rc/aHR0cHM6Ly9zb21lLXVybC5jb20v';
        $this->ssoConfigMock->expects($this->once())->method('getLoginPageURL')->willReturn($loginUrl);
        $this->ssoConfigMock->expects($this->once())->method('getQueryParameter')->willReturn('redirectUrl');
        $this->urlMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->urlMock->expects($this->once())->method('getUrl')->willReturn($baseUrl);

        $finalUrl = $loginUrl.'?redirectUrl='.$baseUrl.$rcParam;
        $this->assertEquals($finalUrl, $this->configAdapterMock->getLoginButtonHref());
    }

    public function testGetContinueButtonLabel()
    {
        $label = 'continue';
        $this->loginConfigMock->expects($this->once())->method('getContinueButtonLabel')->willReturn($label);
        $configAdapter = new ConfigAdapter(
            $this->urlMock,
            $this->ssoConfigMock,
            $this->loginConfigMock,
            $this->toggleConfigMock,
            $this->assetRepository
        );
        $this->assertEquals($label, $configAdapter->getContinueButtonLabel());
    }

    public function testGetFeatureToggle(): void
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')
            ->with(self::TOGGLE_FEATURE_KEY)->willReturn(true);

        $this->assertIsBool($this->configAdapterMock->getFeatureToggle());
    }

    public function testGetFeatureToggleFalse(): void
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')
            ->with(self::TOGGLE_FEATURE_KEY)->willReturn(false);

        $this->assertIsBool($this->configAdapterMock->getFeatureToggle());
    }
}
