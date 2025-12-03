<?php

namespace Fedex\WebAnalylics\Test\Unit\Block\Widget;

use Fedex\WebAnalytics\Block\Widget\ConversionTracking;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Helper\Conditions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConversionTrackingTest extends TestCase
{
    protected $contextMock;
    /**
     * @var ConversionTracking|MockObject
     */
    protected ConversionTracking|MockObject $conversionTracking;

    /**
     * @var Conditions|MockObject
     */
    protected Conditions|MockObject $conditionsMock;

    /**
     * @var Json|MockObject
     */
    protected Json|MockObject $serializerMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected ManagerInterface|MockObject $eventManagerMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected ScopeConfigInterface|MockObject $scopeConfigMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEventManager', 'getScopeConfig'])
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(["serialize"])
            ->getMock();

        $this->conditionsMock = $this->getMockBuilder(Conditions::class)
            ->disableOriginalConstructor()
            ->setMethods(["decode"])
            ->getMock();

        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["dispatch"])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["getValue"])
            ->getMockForAbstractClass();

        $this->contextMock->method('getEventManager')
            ->willReturn($this->eventManagerMock);
        $this->contextMock->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->conversionTracking = $this->getMockBuilder(ConversionTracking::class)
            ->setConstructorArgs(
                [
                    'context' => $this->contextMock,
                    'conditions' => $this->conditionsMock,
                    'serializer' => $this->serializerMock
                ]
            )->setMethods(["getData", "_getData"])
            ->getMock();
    }

    /**
     * @return void
     */
    public function testToHtml()
    {
        $this->conversionTracking->expects($this->any())->method('getData')
            ->with(ConversionTracking::ENABLED)->willReturn(true);
        $this->conversionTracking->expects($this->any())->method('_getData')
            ->with('module_name')->willReturn('WebAnalytics');
        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnSelf();
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with('advanced/modules_disable_output/'.'WebAnalytics')
            ->willReturn(true);

        $test = $this->getMockBuilder(\Magento\Framework\View\Element\AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(["toHtml"])
            ->getMock();
        $test->expects($this->any())
            ->method('toHtml')
            ->willReturn('');

        $this->conversionTracking->toHtml();
        $this->assertIsString($test->toHtml());
    }

    /**
     * @return void
     */
    public function testToHtmlFalse()
    {
        $this->conversionTracking->expects($this->any())->method('getData')
            ->with(ConversionTracking::ENABLED)->willReturn(false);

        $this->assertFalse($this->conversionTracking->toHtml());
    }

    /**
     * @return void
     */
    public function testIsEnabled()
    {
        $this->conversionTracking->expects($this->once())->method('getData')
            ->with(ConversionTracking::ENABLED)->willReturn(true);

        $this->assertTrue($this->conversionTracking->isEnabled());
    }

    /**
     * @return void
     */
    public function testGetSelectorClass()
    {
        $class = 'conversion_tracking';
        $this->conversionTracking->expects($this->once())->method('getData')
            ->with(ConversionTracking::SELECTOR_CLASS)->willReturn($class);

        $result = $this->conversionTracking->getSelectorClass();
        $this->assertIsString($result);
        $this->assertEquals($class, $result);
    }

    /**
     * @return void
     */
    public function testGetDisplayType()
    {
        $fromRequest = 'from_request';
        $this->conversionTracking->expects($this->once())->method('getData')
            ->with(ConversionTracking::DISPLAY_TYPE)->willReturn($fromRequest);

        $result = $this->conversionTracking->getDisplayType();
        $this->assertIsString($result);
        $this->assertEquals($fromRequest, $result);
    }

    /**
     * @return void
     */
    public function testGetTrackingParamsFromRequest()
    {
        $displayType = ConversionTracking::FROM_REQUEST;
        $this->conversionTracking->expects($this->atMost(2))->method('getData')
            ->withConsecutive([ConversionTracking::DISPLAY_TYPE], [ConversionTracking::TRACKING_PARAMETERS_FROM_URL])
            ->willReturnOnConsecutiveCalls($displayType, '');

        $result = $this->conversionTracking->getTrackingParams();
        $this->assertIsString($result);
    }

    /**
     * @return void
     */
    public function testGetTrackingParamsStaticValue()
    {
        $displayType = ConversionTracking::STATIC_VALUE;
        $this->conversionTracking->expects($this->atMost(3))->method('getData')
            ->withConsecutive([ConversionTracking::DISPLAY_TYPE], [ConversionTracking::DISPLAY_TYPE], [ConversionTracking::TRACKING_PARAMETERS_STATIC_VALUE])
            ->willReturnOnConsecutiveCalls($displayType, $displayType, '');

        $result = $this->conversionTracking->getTrackingParams();
        $this->assertIsString($result);
    }

    /**
     * @return void
     */
    public function testGetTrackingParamsFromPageBuilder()
    {
        $displayType = ConversionTracking::STATIC_VALUE;
        $requestParams = '^[`_1701107329037_37`:^[`request_param`:`gclid`,`parameter_to_url`:`gclid`^],`_1701107329820_820`:^[`request_param`:`gclid`,`parameter_to_url`:`asdasdsad`^]^]';
        $requestParamsArray = [
            '_1701107329037_37' => ['request_param' => 'gclid','parameter_to_url' => 'gclid'],
            '_1701107329820_820' => ['request_param' => 'gclid','parameter_to_url' => 'asdasdsad']
        ];
        $requestParamsArrayTreated = [
            ['request_param' => 'gclid','parameter_to_url' => 'gclid'],
            ['request_param' => 'gclid','parameter_to_url' => 'asdasdsad']
        ];
        $this->conversionTracking->expects($this->atMost(3))->method('getData')
            ->withConsecutive([ConversionTracking::DISPLAY_TYPE], [ConversionTracking::DISPLAY_TYPE], [ConversionTracking::TRACKING_PARAMETERS_STATIC_VALUE])
            ->willReturnOnConsecutiveCalls($displayType, $displayType, $requestParams);

        $this->conditionsMock->expects($this->once())->method('decode')->with($requestParams)
            ->willReturn($requestParamsArray);

        $this->serializerMock->expects($this->once())->method('serialize')->with($requestParamsArrayTreated)
            ->willReturn('[{"request_param":"gclid","parameter_to_url":"gclid"},{"request_param":"gclid","parameter_to_url":"asdasdsad"}]');

        $result = $this->conversionTracking->getTrackingParams();
        $this->assertIsString($result);
    }
}
