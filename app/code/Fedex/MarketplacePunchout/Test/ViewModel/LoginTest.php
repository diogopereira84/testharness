<?php
namespace Fedex\MarketplacePunchout\Test\ViewModel;

use Fedex\MarketplacePunchout\Model\FclLogin;
use Fedex\MarketplacePunchout\ViewModel\Login;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\SSO\ViewModel\SsoConfiguration;

class LoginTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplacePunchout\Model\Config\Marketplace & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketplaceConfigMock;
    /**
     * @var (\Fedex\SSO\ViewModel\SsoConfiguration & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ssoConfigurationMock;
    /** @var FclLogin|MockObject */
    private $fclLoginMock;

    /** @var Login */
    private $loginViewModel;

    protected function setUp(): void
    {
        $this->fclLoginMock = $this->createMock(FclLogin::class);
        $this->marketplaceConfigMock = $this->createMock(Marketplace::class);
        $this->ssoConfigurationMock = $this->createMock(SsoConfiguration::class);

        $this->loginViewModel = new Login(
            $this->fclLoginMock,
            $this->marketplaceConfigMock,
            $this->ssoConfigurationMock
        );
    }

    public function testGetCustomData()
    {
        $expectedData = ['key' => 'value', 'another_key' => 'another_value'];

        $this->fclLoginMock->expects($this->once())
            ->method('getData')
            ->willReturn($expectedData);

        $result = $this->loginViewModel->getCustomData();

        $this->assertEquals($expectedData, $result);
    }
}
