<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Tax\Test\Unit\Block\Adminhtml\System\Config;

use Fedex\Tax\Block\Adminhtml\System\Config\Editor;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class MockEditor extends Editor {

    public function _getElementHtml(AbstractElement $element)
    {
        $element->setWysiwyg(true);

        $element->setConfig($this->wysiwygConfig->getConfig([
            'add_variables' => false,
            'add_widgets'   => false,
            'height'        => '200px',
            'isModalEditor'   => true
        ]));

        return '<div>Tax Exempt Editor</div>';
    }
}

class EditorTest extends TestCase
{
    /**
     * @var object
     */
    protected $editor;
    protected $mockEditor;
    /**
     * @var WysiwygConfig|MockObject
     */
    protected $wysiwygConfigMock;

    /**
     * @var AbstractElement|MockObject
     */
    protected $elementMock;

    /**
     * @var DataObject|MockObject
     */
    protected $dataObject;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->wysiwygConfigMock = $this->getMockBuilder(WysiwygConfig::class)
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->elementMock = $this->getMockBuilder(AbstractElement::class)
            ->addMethods(['setWysiwyg', 'setConfig'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->objectManager = new ObjectManager($this);

        $this->editor = $this->objectManager->getObject(
            Editor::class,
            [
                'wysiwygConfig' => $this->wysiwygConfigMock
            ]
        );
        $this->mockEditor = $this->objectManager->getObject(
            MockEditor::class,
            [
                'wysiwygConfig' => $this->wysiwygConfigMock
            ]
        );
    }

    /**
     * Test _getElementHtml.
     * 
     * @return void
     */
    public function testGetElementHtml(): void
    {
        $wysiwygConfigData = [
            'add_variables' => false,
            'add_widgets'   => false,
            'height'        => '200px',
            'isModalEditor'   => true
        ];

        $elementHtmlString = '<div>Tax Exempt Editor</div>';

        $this->elementMock->expects($this->once())->method('setWysiwyg')
            ->with(true);

        $this->wysiwygConfigMock->expects($this->once())->method('getConfig')
            ->with($wysiwygConfigData)
            ->willReturn($this->dataObject);

        $this->elementMock->expects($this->once())->method('setConfig')
            ->with($this->dataObject);

        $this->assertEquals($elementHtmlString, $this->mockEditor->_getElementHtml($this->elementMock));
    }
}
