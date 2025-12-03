<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Block;

use Fedex\Company\Helper\Data;
use Fedex\WebAnalytics\Api\Data\AppDynamicsConfigInterface;
use Fedex\WebAnalytics\Block\AppDynamics;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppDynamicsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const XML_PATH_ACTIVE_APP_DYNAMICS = 'web/app_dynamics/active';
    const XML_PATH_APP_DYNAMICS_SCRIPT = 'web/app_dynamics/head_script';

    protected Context|MockObject $contextMock;
    protected Data|MockObject $companyHelper;
    protected CompanyInterface|MockObject $company;
    protected AppDynamicsConfigInterface|MockObject $appDynamicsConfigInterface;
    protected AppDynamics|MockObject $appDynamicsMock;
    protected SecureHtmlRenderer|MockObject $secureHtmlRendererMock;

    protected function setUp(): void
    {
        $this->companyHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['getCustomerCompany'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAppDynamics'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->appDynamicsConfigInterface = $this->getMockBuilder(AppDynamicsConfigInterface::class)
            ->setMethods(['getScriptCode', 'isActive'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->secureHtmlRendererMock = $this
            ->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->appDynamicsMock = $this->objectManager->getObject(
            AppDynamics::class,
            [
                'context' => $this->contextMock,
                'companyHelper' => $this->companyHelper,
                'appDynamicsConfigInterface' => $this->appDynamicsConfigInterface,
                'secureHtmlRenderer' => $this->secureHtmlRendererMock,
                'data' => []
            ]
        );
    }

    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $this->appDynamicsConfigInterface->expects($this->once())->method('isActive')->willReturn(true);
        $this->assertEquals('', $this->appDynamicsMock->_toHtml());
    }

    /**
     * Assert _toHtml.
     */
    public function testToHtmlWhenConfigDisabledCompanyDisabled()
    {
        $this->appDynamicsConfigInterface->expects($this->once())->method('isActive')->willReturn(false);
        $this->company->expects($this->once())->method('getAppDynamics')->willReturn(false);
        $this->companyHelper->expects($this->once())->method('getCustomerCompany')
            ->willReturn($this->company);
        $this->assertEquals('', $this->appDynamicsMock->_toHtml());
    }

    /**
     * Assert _toHtml.
     */
    public function testToHtmlWhenConfigDisabledCompanyEnabled()
    {
        $this->appDynamicsConfigInterface->expects($this->once())->method('isActive')->willReturn(false);
        $this->company->expects($this->once())->method('getAppDynamics')->willReturn(true);
        $this->companyHelper->expects($this->once())->method('getCustomerCompany')
            ->willReturn($this->company);
        $this->assertEquals('', $this->appDynamicsMock->_toHtml());
    }

    /**
     * Assert GetScript
     */
    public function testGetScript()
    {
        $script = '<script charset="UTF-8" type="text/javascript">
window["adrum-start-time"] = new Date().getTime();
(function(config){
    config.appKey = "AD-AAB-ABH-VSX";
    config.adrumExtUrlHttp = "http://cdn.appdynamics.com";
    config.adrumExtUrlHttps = "https://cdn.appdynamics.com";
    config.beaconUrlHttp = "http://pdx-col.eum-appdynamics.com";
    config.beaconUrlHttps = "https://pdx-col.eum-appdynamics.com";
    config.resTiming = {"bufSize":200,"clearResTimingOnBeaconSend":true};
    config.maxUrlLength = 512;
    config.longStackTrace = false;
})(window["adrum-config"] || (window["adrum-config"] = {}));
</script>
<script src="//cdn.appdynamics.com/adrum/adrum-latest.js"></script>';
        $result = '<script type="text/javascript">
window["adrum-start-time"] = new Date().getTime();
(function(config){
    config.appKey = "AD-AAB-ABH-VSX";
    config.adrumExtUrlHttp = "http://cdn.appdynamics.com";
    config.adrumExtUrlHttps = "https://cdn.appdynamics.com";
    config.beaconUrlHttp = "http://pdx-col.eum-appdynamics.com";
    config.beaconUrlHttps = "https://pdx-col.eum-appdynamics.com";
    config.resTiming = {"bufSize":200,"clearResTimingOnBeaconSend":true};
    config.maxUrlLength = 512;
    config.longStackTrace = false;
})(window["adrum-config"] || (window["adrum-config"] = {}));
</script><script type="text/javascript" src="https://cdn.appdynamics.com/adrum/adrum-latest.js"> </script>';
        $this->appDynamicsConfigInterface->expects($this->once())->method('getScriptCode')->willReturn($script);
        $this->secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);

                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );
        $this->assertEquals($result, $this->appDynamicsMock->getScript());
    }

    /**
     * Assert GetScript
     */
    public function testGetScriptEmpty()
    {
        $this->appDynamicsConfigInterface->expects($this->once())->method('getScriptCode')->willReturn('');
        $this->assertEquals('', $this->appDynamicsMock->getScript());
    }

    /**
     * Assert GetScript
     */
    public function testGetScriptFirstScriptFalse()
    {
        $result = '<scriptwrong type="text/javascript">console.log(1);</scriptwrong>';
        $this->appDynamicsConfigInterface->expects($this->once())->method('getScriptCode')->willReturn($result);
        $this->assertEquals('', $this->appDynamicsMock->getScript());
    }
}
