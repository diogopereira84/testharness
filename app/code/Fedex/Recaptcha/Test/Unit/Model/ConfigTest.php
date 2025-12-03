<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Test\Unit\Model;

use Fedex\Recaptcha\Model\Config;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private const XML_PATH_PUBLIC_KEY = 'recaptcha_frontend/type_recaptcha_v3/public_key';

    protected Config $configMock;
    protected ToggleConfig|MockObject $toggleConfigMock;
    protected ScopeConfigInterface|MockObject $scopeConfig;

    protected function setUp(): void
    {

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->configMock = $this->objectManager->getObject(
            Config::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    public function testGetPublicKey()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_WEBSITE)
            ->willReturn('123');

        $result = $this->configMock->getPublicKey();
        $this->assertIsString($result);
        $this->assertEquals('123', $result);
    }
}
