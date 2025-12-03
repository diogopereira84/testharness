<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Test\Unit\Block\Adminhtml\Widget;


use Magento\Backend\Block\Template\Context;
use Fedex\CustomizedMegamenu\Block\Adminhtml\Widget\ImageChooser;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Data\Form\AbstractForm;

/**
 * @covers \Fedex\CustomizedMegamenu\Block\Adminhtml\Widget\ImageChooser
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImageChooserTest extends TestCase
{
    /**
     * @var (\Fedex\CustomizedMegamenu\Test\Unit\Block\Adminhtml\Widget\Block & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $modelBlockMock;
    protected $elementFactoryMock;
    /**
     * @var ImageChooser
     */
    protected $this;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layoutMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var AbstractElement|MockObject
     */
    protected $elementMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $chooserMock;

    /**
     * @var Factory|MockObject
     */
    protected $blockFactoryMock;


    protected $objectManager;

    protected function setUp(): void
    {
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
      
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->elementMock = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getId',
                    'getValue',
                    'setData',
                    '_getData'
                ]
            )
            ->getMockForAbstractClass();
        $this->modelBlockMock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getTitle',
                    'load',
                    'getId',
                ]
            )
            ->getMock();

        $this->chooserMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setType',
                    'setLabel',
                    'setOnClick',
                    'setDisabled',
                    'toHtml',
                    'setClass'
                ]
            )
            ->getMockForAbstractClass();

        $this->elementFactoryMock = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);
    

        $this->objectManager = new ObjectManager($this);
       
        
    }

    /**
     * @covers \Fedex\CustomizedMegamenu\Block\Adminhtml\Widget\ImageChooser::prepareElementHtml
     * @param string $elementValue
     *
     * 
     */
    public function testPrepareElementHtml()
    {
        $elementId    = 1;
        $sourceUrl    = 'cms/wysiwyg_images/index/1';
        $label       = 'Choose icon Image...';
        $fieldsetId   = 2;
        $html         = '<input id="options_fieldset97fcc7f8277f341d2f533ad7d10fbea5b040c09cde78aa0474663720ab125586_megabuttonicon" name="parameters[megabuttonicon]"  data-ui-id="wysiwyg-widget-options-element-text-parameters-megabuttonicon"  value="" class="widget-option input-text admin__control-text" type="text" formelementhookid="elemIdQMvjf93Ty5"/><button id="id_3h1CAdahv1syFFfnBrKcuIfNRkFTzTAz" title="Choose icon Image..." type="button" class="action-default scalable btn-chooser" backend-button-widget-hook-id="buttonIdNMtbiasV2i"  data-ui-id="widget-button-0" >\n
        <span>Choose icon Image...</span>\n
    </button>\n
    ';
        $className    = 'widget-option input-text admin__control-text';  
        $textHtml = '<input id="options_fieldset97fcc7f8277f341d2f533ad7d10fbea5b040c09cde78aa0474663720ab125586_megabuttonicon" name="parameters[megabuttonicon]"  data-ui-id="wysiwyg-widget-options-element-text-parameters-megabuttonicon"  value="" class="widget-option input-text admin__control-text" type="text" formelementhookid="elemIdQMvjf93Ty5"/>';
        $elementData = [];

        $data = '<input id="options_fieldset97fcc7f8277f341d2f533ad7d10fbea5b040c09cde78aa0474663720ab125586_megabuttonicon" name="parameters[megabuttonicon]" data-ui-id="wysiwyg-widget-options-element-text-parameters-megabuttonicon" value="" class="widget-option input-text admin__control-text" type="text" formelementhookid="elemId6Nk9bgxGn3"><button id="id_eHKOjC5cQUxciGqBHmLduMLWpp0ca8up" title="Choose icon Image..." type="button" class="action-default scalable btn-chooser" backend-button-widget-hook-id="buttonIdCop2uLXree" data-ui-id="widget-button-0">
        <span>Choose icon Image...</span>
    </button>';

        $this->context = $this->objectManager->getObject(
            Context::class,
            [
                'layout'     => $this->layoutMock,
                'urlBuilder' => $this->urlBuilderMock,
            ]
        );

        $this->this = $this->objectManager->getObject(
            ImageChooser::class,
            [
                'context'      => $this->context,
                'elementFactory' => $this->elementFactoryMock
            ]
        );

        $this->elementMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($elementId);
        $this->urlBuilderMock->expects($this->atLeastOnce())
            ->method('getUrl')
            ->with('cms/wysiwyg_images/index', ['target_element_id' => $elementId, 'type' => 'file'])
            ->willReturn($sourceUrl);

        $this->layoutMock->expects($this->atLeastOnce())
            ->method('createBlock')
            ->with('Magento\Backend\Block\Widget\Button')
            ->willReturn($this->chooserMock);

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setType')
            ->with('button')
            ->willReturnSelf();
        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setClass')
            ->with('btn-chooser')
            ->willReturnSelf();
        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setOnClick')
            ->with('MediabrowserUtility.openDialog(\''. $sourceUrl .'\')')
            ->willReturnSelf();

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setDisabled')
            ->with(null)
            ->willReturnSelf();

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setLabel')
            ->with($label)
            ->willReturnSelf();

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('toHtml')
            ->willReturn($html);

        /** @var AbstractForm|MockObject $formMock */
        $formMock = $this->getMockForAbstractClass(AbstractForm::class, [], '', false);

        /** @var Hidden|MockObject $textMock */
        $textMock = $this->createMock(Text::class);
        $textMock->expects($this->once())
            ->method('setId')
            ->with($elementId)
            ->willReturnSelf();
        $textMock->expects($this->once())
            ->method('setForm')
            ->with(null)
            ->willReturnSelf();
    
        $textMock->expects($this->once())
            ->method('getElementHtml')
            ->willReturn($textHtml);

        $this->elementFactoryMock->expects($this->once())
            ->method('create')
            ->with('text', ['data' => $elementData])
            ->willReturn($textMock);

        $this->assertEquals($this->elementMock, $this->this->prepareElementHtml($this->elementMock));
    }
}
