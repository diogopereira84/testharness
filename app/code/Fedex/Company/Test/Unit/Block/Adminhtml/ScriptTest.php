<?php

namespace Fedex\Company\Test\Unit\Block;

use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Eav\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Block\Adminhtml\Script;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ScriptTest extends TestCase
{

    /**
     * @var (Http & MockObject)
     */
    protected $requestMock;
    protected $toggleConfigMock;
    /**
     * @var (Config & MockObject)
     */
    protected $eavConfigMock;
    /**
     * @var (ConfigInterface & MockObject)
     */
    protected $configInterfaceMock;
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    protected $blockScript;
    public const FEDEX_ACCOUNT_CC_TOGGLE = 'explorers_enable_disable_fedex_account_cc_commercial';

    private const TECHTITANS_ERROR_ON_CHANGE_COMPANY_EMAIL = "techtitans_D171411_error_on_change_company_email";

    /**
     * @var
     */
    private $contextMock;


    /**
     * @var
     */
    private $scopeConfigInterfaceMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                                                ->setMethods(['getValue'])
                                                ->disableOriginalConstructor()
                                                ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
                                        ->setMethods(['getToggleConfigValue'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->eavConfigMock = $this->getMockBuilder(Config::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
                                        ->disableOriginalConstructor()
                                        ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->objectManager->getObject(
            Context::class,
            [
                'request' => $this->requestMock,
            ]
        );

        $this->blockScript = $this->objectManager->getObject(
            Script::class,
            [
                'context' => $this->contextMock,
                'scopeConfig' => $this->scopeConfigInterfaceMock,
                'toggleConfig' => $this->toggleConfigMock,
                'eavConfig' => $this->eavConfigMock,
                'configInterface' => $this->configInterfaceMock
            ]
        );
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for testGetApiKey method.
     * @return void
     */
    public function testGetApiKey()
    {
        $testDomainName = 'https://google.com';
        $expected = 'https://google.com';
        $headerToDomain = $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->with("fedex/general/google_maps_api_url")->willReturn($testDomainName);
        $result = $this->blockScript->getApiKey();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for fedexAccountCCToggleEnable method.
     * @return void
     */
    public function testFedexAccountCCToggleEnable()
    {
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')->with(self::FEDEX_ACCOUNT_CC_TOGGLE)->willReturn(true);
        $result = $this->blockScript->fedexAccountCCToggleEnable();

        $this->assertEquals(true, $result);
    }

}
