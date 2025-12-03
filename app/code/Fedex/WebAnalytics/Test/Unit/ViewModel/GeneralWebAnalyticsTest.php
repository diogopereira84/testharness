<?php

namespace Fedex\WebAnalytics\Test\Unit\ViewModel;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\WebAnalytics\Api\Data\AppDynamicsConfigInterface;
use Fedex\WebAnalytics\Api\Data\ContentSquareInterface;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Api\Data\NewRelicInterface;
use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Fedex\WebAnalytics\ViewModel\GeneralWebAnalytics;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class GeneralWebAnalyticsTest extends TestCase
{
    private $toggleConfigMock;
    private $companyHelperMock;
    private $appDynamicsConfigMock;
    private $contentSquareInterfaceMock;
    private $GDLConfigInterfaceMock;
    private $newRelicInterfaceMock;
    private $nuanceInterfaceMock;
    /** @var object|GeneralWebAnalytics */
    private $viewModel;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->companyHelperMock = $this->createMock(CompanyHelper::class);
        $this->appDynamicsConfigMock = $this->createMock(AppDynamicsConfigInterface::class);
        $this->contentSquareInterfaceMock = $this->createMock(ContentSquareInterface::class);
        $this->GDLConfigInterfaceMock = $this->createMock(GDLConfigInterface::class);
        $this->newRelicInterfaceMock = $this->createMock(NewRelicInterface::class);
        $this->nuanceInterfaceMock = $this->createMock(NuanceInterface::class);

        $objectManager = new ObjectManager($this);
        $this->viewModel = $objectManager->getObject(
            GeneralWebAnalytics::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'companyHelper' => $this->companyHelperMock,
                'appDynamicsConfig' => $this->appDynamicsConfigMock,
                'contentSquareInterface' => $this->contentSquareInterfaceMock,
                'GDLConfigInterface' => $this->GDLConfigInterfaceMock,
                'newRelicInterface' => $this->newRelicInterfaceMock,
                'nuanceInterface' => $this->nuanceInterfaceMock,
            ]
        );
    }

    public function testIsToggleD219954Enabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(GeneralWebAnalytics::TOGGLE_D219954)
            ->willReturn(true);

        $this->assertTrue($this->viewModel->isToggleD219954Enabled());
    }

    public function testIsGDLEnabledForCurrentSessionReturnsFalseWhenGDLIsInactive()
    {
        $this->GDLConfigInterfaceMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $this->companyHelperMock->expects($this->once())
            ->method('getCustomerCompany')
            ->willReturn(null);

        $this->GDLConfigInterfaceMock->expects($this->never())
            ->method('getScriptCode');

        $this->assertFalse($this->viewModel->isGDLEnabledForCurrentSession());
    }

    public function testIsGDLEnabledForCurrentSessionReturnsFalseWhenScriptCodeIsMissing()
    {
        $this->GDLConfigInterfaceMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->companyHelperMock->expects($this->once())
            ->method('getCustomerCompany')
            ->willReturn(null);

        $this->GDLConfigInterfaceMock->expects($this->once())
            ->method('getScriptCode')
            ->willReturn(null);

        $this->assertFalse($this->viewModel->isGDLEnabledForCurrentSession());
    }

    public function testIsGDLEnabledForCurrentSessionReturnsTrue()
    {
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getAdobeAnalytics'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyMock->expects($this->once())
            ->method('getAdobeAnalytics')
            ->willReturn(true);

        $this->GDLConfigInterfaceMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->companyHelperMock->expects($this->once())
            ->method('getCustomerCompany')
            ->willReturn($companyMock);

        $this->GDLConfigInterfaceMock->expects($this->once())
            ->method('getScriptCode')
            ->willReturn('some_script_code');

        $this->assertTrue($this->viewModel->isGDLEnabledForCurrentSession());
    }
}
