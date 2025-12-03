<?php
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Model\Config;
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
    public const XML_PATH_MARKETING_OPT_IN_ENABLED = 'promo/marketing_opt_in/enabled';
    public const XML_PATH_MARKETING_OPT_IN_API_URL = 'fedex/general/sales_force_api_url';
    public const XML_PATH_MARKETING_OPT_IN_URL_SUCCESS_PAGE = 'promo/marketing_opt_in/url_success_page';


    protected Config $configMock;
    protected ScopeConfigInterface|MockObject $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->configMock = $this->objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    public function testIsMarketingOptInEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_MARKETING_OPT_IN_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertIsBool($this->configMock->isMarketingOptInEnabled());
    }

    public function testGetMarketingOptInApiUrl()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_MARKETING_OPT_IN_API_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn('https://page.message.fedex.com/api-subscribe-fxo');

        $this->assertIsString($this->configMock->getMarketingOptInApiUrl());
    }

    public function testGetMarketingOptInUrlSuccessPage()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::XML_PATH_MARKETING_OPT_IN_URL_SUCCESS_PAGE, ScopeInterface::SCOPE_STORE)
            ->willReturn('https://page.message.fedex.com/office_opt_in/office_opt_in');

        $this->assertIsString($this->configMock->getMarketingOptInUrlSuccessPage());
    }
}
