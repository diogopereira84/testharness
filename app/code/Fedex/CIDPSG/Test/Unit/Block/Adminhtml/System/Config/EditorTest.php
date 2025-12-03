<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block\Adminhtml\System\Config;

use Fedex\CIDPSG\Block\Adminhtml\System\Config\Editor;
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Framework\Data\Form\Element\AbstractElement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * EditorTest unit test class
 */
class EditorTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $editorMock;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var WysiwygConfig|MockObject
     */
    protected $wysiwygConfigMock;

    /**
     * @var AbstractElement|MockObject
     */
    protected $abstractElementMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);

        $this->wysiwygConfigMock = $this->createMock(WysiwygConfig::class);

        $this->abstractElementMock = $this->createMock(AbstractElement::class);

        $this->objectManager = new ObjectManager($this);

        $this->editorMock = $this->objectManager->getObject(
            Editor::class,
            [
                'wysiwygConfig'     => $this->wysiwygConfigMock
            ]
        );
    }

    /**
     * Test testGetElementHtml
     *
     * @return void
     */
    public function testGetElementHtml()
    {
        $this->assertEquals(null, $this->editorMock->_getElementHtml($this->abstractElementMock));
    }
}
