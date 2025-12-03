<?php

namespace Fedex\WebAnalylics\Test\Unit\Model\Widget;

use Fedex\WebAnalytics\Plugin\Model\Widget\BeforeGetWidgetDeclaration;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Widget\Helper\Conditions;
use Magento\Widget\Model\Widget;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class BeforeGetWidgetDeclarationTest extends TestCase
{
    protected $subject;
    protected $conditionsMock;
    /**
     * @var BeforeGetWidgetDeclaration
     */
    protected BeforeGetWidgetDeclaration $beforeGetWidgetDeclaration;

    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(Widget::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionsMock = $this->getMockBuilder(Conditions::class)
            ->disableOriginalConstructor()
            ->setMethods(["encode"])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->beforeGetWidgetDeclaration = $objectManager->getObject(
            BeforeGetWidgetDeclaration::class,
            [
                'conditions' => $this->conditionsMock
            ]
        );
    }
    /**
     * Prepare chooser element HTML
     *
     * @return AbstractElement
     */
    public function testBeforeGetWidgetDeclaration()
    {
        $type = \Fedex\WebAnalytics\Block\Widget\ConversionTracking::class;
        $requestParams = '^[`_1701107329037_37`:^[`request_param`:`gclid`,`parameter_to_url`:`gclid`^],`_1701107329820_820`:^[`request_param`:`gclid`,`parameter_to_url`:`asdasdsad`^]^]';
        $params = [
            'tracking_parameters_from_url' => [
                '_1701107329037_37' => ['request_param' => 'gclid','parameter_to_url' => 'gclid'],
                '_1701107329820_820' => ['request_param' => 'gclid','parameter_to_url' => 'asdasdsad'],
                '__empty' => ''
            ]
        ];
        $paramsNoEmpty = [
            'tracking_parameters_from_url' => [
                '_1701107329037_37' => ['request_param' => 'gclid','parameter_to_url' => 'gclid'],
                '_1701107329820_820' => ['request_param' => 'gclid','parameter_to_url' => 'asdasdsad']
            ]
        ];

        $this->conditionsMock->expects($this->once())->method('encode')
            ->with($paramsNoEmpty['tracking_parameters_from_url'])->willReturn($requestParams);

        $this->assertNotNull($this->beforeGetWidgetDeclaration->beforeGetWidgetDeclaration($this->subject, $type, $params, true));
    }
}
