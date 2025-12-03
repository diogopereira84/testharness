<?php
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use Fedex\Canva\Model\Config;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const XML_PATH_FEDEX_CANVA_DESIGN_BASE_URL = 'fedex/canva_design/base_url';
    public const XML_PATH_FEDEX_CANVA_DESIGN_PATH = 'fedex/canva_design/path';
    public const CANVA_LOGO_CONFIG_PATH = 'fedex/canva_design/canva_logo';
    public const XML_PATH_FEDEX_CANVA_DESIGN_PARTNER_ID = 'fedex/canva_design/partner_id';
    public const XML_PATH_FEDEX_CANVA_DESIGN_PARTNERSHIP_SDK_URL = 'fedex/canva_design/partnership_sdk_url';
    public const XML_PATH_FEDEX_CANVA_DESIGN_USER_TOKEN_API_URL = 'fedex/canva_design/user_token_api_url';

    protected Config $configMock;
    protected ScopeConfigInterface|MockObject $scopeConfigMock;
    protected ToggleConfig|MockObject $toggleConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->configMock = $this->objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    public function testGetBaseUrl()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_FEDEX_CANVA_DESIGN_BASE_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn('https://wwwtest.fedex.com/apps/ondemand/');

        $this->assertIsString($this->configMock->getBaseUrl());
    }

    public function testGetPath()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_FEDEX_CANVA_DESIGN_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('print-online/templates/editor');

        $this->assertIsString($this->configMock->getPath());
    }

    public function testGetCanvaLogoPath()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::CANVA_LOGO_CONFIG_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $this->assertIsString($this->configMock->getCanvaLogoPath());
    }

    public function testGetPartnerId()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_FEDEX_CANVA_DESIGN_PARTNER_ID, ScopeInterface::SCOPE_STORE)
            ->willReturn('fedex-test');

        $this->assertIsString($this->configMock->getPartnerId());
    }

    public function testGetPartnershipSdkUrl()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_FEDEX_CANVA_DESIGN_PARTNERSHIP_SDK_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn('https://sdk.canva.com/partnership.js');

        $this->assertIsString($this->configMock->getPartnershipSdkUrl());
    }

    public function testGetUserTokenApiUrl()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_FEDEX_CANVA_DESIGN_USER_TOKEN_API_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn('https://api.test.office.fedex.com/partners/fedexoffice/v1/canva/usertokens');

        $this->assertIsString($this->configMock->getUserTokenApiUrl());
    }
}
