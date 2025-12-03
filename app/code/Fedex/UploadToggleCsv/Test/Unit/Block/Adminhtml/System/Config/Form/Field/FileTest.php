<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToggleCsv\Test\Unit\Block\Adminhtml\System\Config\Form\Field;

use Fedex\UploadToggleCsv\Block\Adminhtml\System\Config\Form\Field\File;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Escaper;

class FileTest extends TestCase
{
    /** @var Context&MockObject */
    private $context;

    /** @var ObjectManager&MockObject */
    protected ObjectManager $objectManager;

    /** @var File&MockObject */
    protected $file;

    /** @var Escaper&MockObject */
    protected $escaper;

    /**
     * Set up method for test case.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->setMethods(['escapeHtmlAttr'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->file = $this->objectManager->getObject(
            File::class,
            [
                'context' => $this->context,
                'escaper' => $this->escaper
            ]
        );
    }

    /**
     * Test case to verify that the getElementHtml method builds the expected HTML markup.
     *
     * @return void
     */
    public function testGetElementHtmlBuildsExpectedMarkup(): void
    {
        $elementId   = 'toggle_csv';
        $elementName = 'groups[environment_toggle][fields][upload_csv][value]';

        /** @var AbstractElement&MockObject $element */
        $element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHtmlId', 'getName'])
            ->getMockForAbstractClass();
        $element->method('getHtmlId')->willReturn($elementId);
        $element->method('getName')->willReturn($elementName);

        /** @var File&MockObject $block */
        $block = $this->getMockBuilder(File::class)
            ->setConstructorArgs([$this->context,$this->escaper])
            ->onlyMethods(['getUrl', 'getFormKey'])
            ->getMock();

        $block->method('getFormKey')->willReturn('FORM_KEY_123');

        $block->method('getUrl')->willReturnMap([
            ['uploadtogglecsv/featuretoggle/apply', [], 'https://example.com/admin/upload/apply']
        ]);

        $method = new \ReflectionMethod(File::class, '_getElementHtml');
        $method->setAccessible(true);

        /** @var string $html */
        $html = $method->invoke($block, $element);

        $this->assertNotEmpty($html, 'HTML output should not be empty');

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('accept=".csv"', $html);
        $this->assertStringContainsString('onclick="applyCsv()"', $html);
        $this->assertStringContainsString('Apply List', $html);
        $this->assertStringContainsString('onclick="removeCsv()"', $html);
        $this->assertStringContainsString('Remove List', $html);
        $this->assertStringContainsString('onclick="downloadCsv()"', $html);
        $this->assertStringContainsString('Download List', $html);
        $this->assertStringContainsString('window.formKey = "FORM_KEY_123"', $html);
        $this->assertStringContainsString('window.applyUrl = "https://example.com/admin/upload/apply"', $html);
        $this->assertStringContainsString('window.toggleSelector = "tr[id*=\'row_environment_toggle\']"', $html);
        $this->assertStringContainsString('$("#' . $elementId . '").on("change"', $html);
        $this->assertStringContainsString('window.uploadCsvHandler(this.files[0])', $html);
    }
}
