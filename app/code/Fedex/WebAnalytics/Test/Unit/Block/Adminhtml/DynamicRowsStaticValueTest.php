<?php

namespace Fedex\WebAnalylics\Test\Unit\Block\Adminhtml;

use Fedex\WebAnalytics\Block\Adminhtml\DynamicRowsStaticValue;
use Fedex\WebAnalytics\Block\Adminhtml\Form\Field\Widget\DynamicRowsStaticValue as DynamicRowsStaticValueField;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DynamicRowsStaticValueTest extends TestCase
{
    protected $layout;
    protected $blockInterface;
    protected $abstractElement;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonMock;
    protected $mathRandomMock;
    protected $contextMock;
    /**
     * @var DynamicRowsStaticValue
     */
    protected DynamicRowsStaticValue $dynamicRowsStaticValue;
    protected function setUp(): void
    {
        $this->layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['createBlock', "setElement", "setFieldsetId", "setUniqId"])
            ->getMockForAbstractClass();

        $this->blockInterface = $this->getMockBuilder(DynamicRowsStaticValueField::class)
            ->disableOriginalConstructor()
            ->setMethods(["toHtml"])
            ->getMock();

        $this->abstractElement = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods(["getValue", "getId", "getForm", "getRequired", "setData"])
            ->getMockForAbstractClass();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(["unserialize"])
            ->getMock();

        $this->mathRandomMock = $this->getMockBuilder(Random::class)
            ->disableOriginalConstructor()
            ->setMethods(["getUniqueHash"])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMathRandom'])
            ->getMock();

        $this->contextMock->expects($this->once())->method('getMathRandom')->willReturn($this->mathRandomMock);

        $objectManager = new ObjectManager($this);
        $this->dynamicRowsStaticValue = $objectManager->getObject(
            DynamicRowsStaticValue::class,
            [
                'context' => $this->contextMock,
                'jsonSerializer' => $this->jsonMock,
                'element' => $this->abstractElement,
                '_layout' => $this->layout
            ]
        );
    }
    /**
     * Prepare chooser element HTML
     *
     * @return AbstractElement
     */
    public function testPrepareElementHtml()
    {
        $value = '{"_1701107692345_345":{"value":"123","parameter_to_url":"gclid"},"_1701107692822_822":{"value":"456","parameter_to_url":"external_id"},"_1701107693188_188":{"value":"789","parameter_to_url":"wtf"},"__empty":""}';
        $this->layout->expects($this->once())->method('createBlock')->willReturn($this->blockInterface);
        $this->abstractElement->expects($this->any())->method('getId')->willReturn("1");
        $this->abstractElement->expects($this->any())->method('getValue')->willReturn(json_decode($value, true));

        $this->assertNotNull($this->dynamicRowsStaticValue->prepareElementHtml($this->abstractElement));
    }
}
