<?php

namespace Fedex\CustomizedMegamenu\Test\Unit\Block\Adminhtml\Widget;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CustomizedMegamenu\Block\Adminhtml\Widget\TextField;

class TextFieldTest extends TestCase
{
    protected $abstractElement;
    protected $elementFactory;
    /**
     * @var (\Magento\Backend\Block\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $textField;
    protected function setUp(): void
    {
        $this->abstractElement = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods(["getData", "getId", "getForm", "getRequired", "setData"])
            ->getMockForAbstractClass();

        $this->elementFactory = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create", "setId", "setForm", "setClass", "addClass", "getElementHtml"])
            ->getMock();
        
        $this->contextMock = $this->getMockBuilder(\Magento\Backend\Block\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->textField = $objectManager->getObject(
            TextField::class,
            [
                'context' => $this->contextMock,
                'elementFactory' => $this->elementFactory,
                'element' => $this->abstractElement
            ]
        );
    }
    /**
     * Prepare chooser element HTML
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function testPrepareElementHtml()
    {
        $this->abstractElement->expects($this->any())->method('getData')->willReturn('ABC');
        $this->elementFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->abstractElement->expects($this->any())->method('getId')->willReturn("1");
        $this->elementFactory->expects($this->any())->method('setId')->willReturnSelf();
        
        $this->abstractElement->expects($this->any())->method('getRequired')->willReturnSelf();

        $this->assertNotNull($this->textField->prepareElementHtml($this->abstractElement));
    }
}
