<?php

namespace Fedex\WebAnalylics\Test\Unit\Model\Widget\Instance;

use Fedex\WebAnalytics\Plugin\Model\Widget\Instance\AfterGetWidgetParameters;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Helper\Conditions;
use Magento\Widget\Model\Widget;
use Magento\Widget\Model\Widget\Instance;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AfterGetWidgetParametersTest extends TestCase
{
    protected $subject;
    protected $serializerMock;
    /**
     * @var AfterGetWidgetParameters
     */
    protected AfterGetWidgetParameters $afterGetWidgetParameters;

    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->setMethods(["getInstanceCode"])
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(["serialize"])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->afterGetWidgetParameters = $objectManager->getObject(
            AfterGetWidgetParameters::class,
            [
                'serializer' => $this->serializerMock
            ]
        );
    }
    /**
     * Prepare chooser element HTML
     *
     * @return AbstractElement
     */
    public function testAfterGetWidgetParameters()
    {
        $params = [
            'tracking_parameters_from_url' => [
                '_1701107329037_37' => ['request_param' => 'gclid','parameter_to_url' => 'gclid'],
                '__empty' => ''
            ],
            'tracking_parameters_static_value' => [
                '_1701107329037_37' => ['request_param' => 'gclid','parameter_to_url' => 'gclid'],
                '__empty' => ''
            ]
        ];
        $resultParams = [
            'tracking_parameters_from_url' => [
                '_1701107329037_37' => '{"request_param":"gclid","parameter_to_url":"gclid"}',
                '__empty' => ''
            ],
            'tracking_parameters_static_value' => [
                '_1701107329037_37' => '{"request_param":"gclid","parameter_to_url":"gclid"}',
                '__empty' => ''
            ]
        ];

        $this->serializerMock->expects($this->atMost(2))->method('serialize')
            ->withConsecutive(
                [$params['tracking_parameters_from_url']['_1701107329037_37']],
                [$params['tracking_parameters_static_value']['_1701107329037_37']]
            )
            ->willReturn(
                $resultParams['tracking_parameters_from_url']['_1701107329037_37'],
                $resultParams['tracking_parameters_static_value']['_1701107329037_37']
            );

        $this->subject->expects($this->once())->method('getInstanceCode')
            ->willReturn('conversion_tracking_widget');

        $this->assertNotNull($this->afterGetWidgetParameters->afterGetWidgetParameters($this->subject, $params));
    }
}
