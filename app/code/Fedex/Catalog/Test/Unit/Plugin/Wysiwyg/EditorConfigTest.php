<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Plugin\Wysiwyg;

use Fedex\Catalog\Model\Config;
use Fedex\Catalog\Ui\DataProvider\Product\Form\Modifier\WysiwygAttributeConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Catalog\Plugin\Wysiwyg\EditorConfig;

class EditorConfigTest extends TestCase
{
    protected EditorConfig $editorConfig;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $configInterfaceMock;

    /**
     * @var DataObject|MockObject
     */
    protected $resultMock;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var Config|MockObject|(Config&MockObject)
     */
    protected Config|MockObject $configMock;

    /**
     * Test setUp
     */
    public function setUp() : void
    {
        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultMock = $this->getMockBuilder(DataObject::class)
            ->onlyMethods(['getData', 'addData', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['wysiwygAttributeList', 'wysiwygToolbarConfig',
                'getWysiwygValidElements', 'getWysiwygExtendedValidElements', 'getWysiwygValidStyles'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->editorConfig = $this->objectManager->getObject(
            EditorConfig::class,
            [
                'catalogConfig' => $this->configMock
            ]
        );
    }

    /**
     * Test afterGetConfig
     */
    public function testAfterGetConfig() : void
    {
        $attributeCode = 'test_attribute';
        $wysiwygAttributeList = ['test_attribute'];

        $this->configMock->expects($this->once())->method('wysiwygAttributeList')
            ->willReturn($wysiwygAttributeList);
        $this->configMock->expects($this->once())->method('wysiwygToolbarConfig')
            ->willReturn('undo redo | bold italic underline | link | bullist | formatselect');
        $this->configMock->expects($this->once())->method('getWysiwygValidElements')
            ->willReturn('strong,em,span[style=text-decoration: underline;],a[class:tax-modal-config|
                    href|title|target<_blank|rel<noopener]');
        $this->configMock->expects($this->once())->method('getWysiwygExtendedValidElements')
            ->willReturn('h1,h2,h3,h4,h5,h6,li,@[style=list-style-type:circle?square;],ul');
        $this->configMock->expects($this->once())->method('getWysiwygValidStyles')
            ->willReturn('text-decoration,list-style-type');

        $this->resultMock->expects($this->any())->method('getData')
            ->withConsecutive(['current_attribute_code'], ['settings'])
            ->willReturnOnConsecutiveCalls($attributeCode, []);

        $this->resultMock->expects($this->any())->method('addData')
            ->with([ 'add_images' => false ])
            ->willReturnSelf();

        $settings['toolbar'] = 'undo redo | bold italic underline | link | bullist | formatselect';
        $settings['valid_elements'] = 'strong,em,span[style=text-decoration: underline;],a[class:tax-modal-config|
                    href|title|target<_blank|rel<noopener]';
        $settings['extended_valid_elements'] = 'h1,h2,h3,h4,h5,h6,li,@[style=list-style-type:circle?square;],ul';
        $settings['valid_styles'] = ['*' => 'text-decoration,list-style-type'];

        $this->resultMock->expects($this->any())->method('setData')
            ->with('settings', $settings)
            ->willReturnSelf();

        $this->assertNotNull($this->editorConfig->afterGetConfig($this->configInterfaceMock, $this->resultMock));
    }

    /**
     * Test afterGetConfig
     */
    public function testAfterGetConfigNullSettings() : void
    {
        $attributeCode = 'test_attribute';
        $wysiwygAttributeList = ['test_attribute'];

        $this->configMock->expects($this->once())->method('wysiwygAttributeList')
            ->willReturn($wysiwygAttributeList);
        $this->configMock->expects($this->once())->method('wysiwygToolbarConfig')
            ->willReturn('undo redo | bold italic underline | link | bullist | formatselect');
        $this->configMock->expects($this->once())->method('getWysiwygValidElements')
            ->willReturn('strong,em,span[style=text-decoration: underline;],a[class:tax-modal-config|
                    href|title|target<_blank|rel<noopener]');
        $this->configMock->expects($this->once())->method('getWysiwygExtendedValidElements')
            ->willReturn('h1,h2,h3,h4,h5,h6,li,@[style=list-style-type:circle?square;],ul');
        $this->configMock->expects($this->once())->method('getWysiwygValidStyles')
            ->willReturn('text-decoration,list-style-type');


        $this->resultMock->expects($this->any())->method('getData')
            ->withConsecutive(['current_attribute_code'], ['settings'])
            ->willReturnOnConsecutiveCalls($attributeCode, null);

        $this->resultMock->expects($this->any())->method('addData')
            ->with([ 'add_images' => false ])
            ->willReturnSelf();

        $settings['toolbar'] = 'undo redo | bold italic underline | link | bullist | formatselect';
        $settings['valid_elements'] = 'strong,em,span[style=text-decoration: underline;],a[class:tax-modal-config|
                    href|title|target<_blank|rel<noopener]';
        $settings['extended_valid_elements'] = 'h1,h2,h3,h4,h5,h6,li,@[style=list-style-type:circle?square;],ul';
        $settings['valid_styles'] = ['*' => 'text-decoration,list-style-type'];

        $this->resultMock->expects($this->any())->method('setData')
            ->with('settings', $settings)
            ->willReturnSelf();

        $this->assertNotNull($this->editorConfig->afterGetConfig($this->configInterfaceMock, $this->resultMock));
    }
}
