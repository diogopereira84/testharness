<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Test\Unit\Block\Adminhtml;


use Magento\Backend\Block\Template\Context;
use Fedex\CustomizedMegamenu\Block\Adminhtml\Chooser;
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
 * @covers \Fedex\CustomizedMegamenu\Block\Adminhtml\Chooser
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChooserTest extends TestCase
{
    /**
     * @var (\Fedex\CustomizedMegamenu\Test\Unit\Block\Adminhtml\Block & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $modelBlockMock;
    protected $elementFactoryMock;
    /**
     * @var Chooser
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
        $html         = '<input id="options_fieldset97fcc7f8277f341d2f533ad7d10fbea5b040c0
        9cde78aa0474663720ab125586_megabuttonicon" name="parameters[megabuttonicon]"
        data-ui-id="wysiwyg-widget-options-element-text-parameters-megabuttonicon"
         value="" class="widget-option input-text admin__control-text" type="text"
         formelementhookid="elemIdQMvjf93Ty5"/><button id="id_3h1CAdahv1syFFfnBrKcuIfNRkFTzTAz"
         title="Choose icon Image..." type="button" class="action-default scalable btn-chooser"
         backend-button-widget-hook-id="buttonIdNMtbiasV2i"  data-ui-id="widget-button-0" >\n
        <span>Choose icon Image...</span>\n
        </button>\n';

        $textHtml = '<input id="options_fieldset97fcc7f8277f341d2f533ad7d10fbea5b040c0
        9cde78aa0474663720ab125586_megabuttonicon" name="parameters[megabuttonicon]"
        data-ui-id="wysiwyg-widget-options-element-text-parameters-megabuttonicon"
         value="" class="widget-option input-text admin__control-text" type="text"
         formelementhookid="elemIdQMvjf93Ty5"/><button id="id_3h1CAdahv1syFFfnBrKcuIfNRkFTzTAz"
         title="Choose icon Image..." type="button" class="action-default scalable btn-chooser"
         backend-button-widget-hook-id="buttonIdNMtbiasV2i"  data-ui-id="widget-button-0" >\n
        <span>Choose icon Image...</span>\n
        </button>\n';

        $this->context = $this->objectManager->getObject(
            Context::class,
            [
                'layout'     => $this->layoutMock,
                'urlBuilder' => $this->urlBuilderMock,
            ]
        );

        $this->this = $this->objectManager->getObject(
            Chooser::class,
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
            ->willReturn($sourceUrl);

        $this->layoutMock->expects($this->atLeastOnce())
            ->method('createBlock')
            ->willReturn($this->chooserMock);

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setType')
            ->willReturnSelf();
        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setClass')
            ->willReturnSelf();
        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setOnClick')
            ->willReturnSelf();

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setDisabled')
            ->willReturnSelf();

        $this->chooserMock->expects($this->atLeastOnce())
            ->method('setLabel')
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
            ->willReturnSelf();
        $textMock->expects($this->once())
            ->method('setForm')
            ->willReturnSelf();
    
        $textMock->expects($this->once())
            ->method('getElementHtml')
            ->willReturn($textHtml);

        $this->elementFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($textMock);

        $this->assertNotNull($this->this->prepareElementHtml($this->elementMock));
    }
}
