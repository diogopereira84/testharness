<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\LiveSearchFacets\Unit\Test\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab;

use Fedex\LiveSearchFacets\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab\Advanced;
use Magento\Framework\Registry;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\Form;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Advanced as catalogAdvanced;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

class AdvancedTest extends TestCase
{

    private Registry $coreRegistryMock;
    private Form $formMock;
    private Fieldset $fieldsetMock;
    private Advanced $advancedMock;
    private Advanced $advanceForm;
    private ObjectManager $objectManager;
    private MockObject|catalogAdvanced $catalogAdvancedMock;

    public function testAroundGetFormHtml() {
         $this->catalogAdvancedMock = $this->createMock(catalogAdvanced::class);
         $this->coreRegistryMock = $this->createMock(Registry::class);
         $this->formMock = $this->createMock(Form::class);
         $this->fieldsetMock = $this->createMock(Fieldset::class);
         $this->advancedMock= $this->createMock(Advanced::class);
         $attributeModel = $this->getMockBuilder(Attribute::class)
            ->onlyMethods(
                [
                    'getData',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
         $this->objectManager = new ObjectManager($this);
         $this->advancedMock= $this->objectManager->getObject(
            Advanced::class,
            [
                'coreRegistry' =>  $this->coreRegistryMock,
            ]
        );
         $this->coreRegistryMock->expects($this->any())->method('registry')->willReturn($attributeModel);
         $attributeModel->expects($this->once())->method('getData')
            ->willReturn([]);
        $this->formMock->expects($this->once())->method('getElement')->with('advanced_fieldset')
            ->willReturn($this->fieldsetMock);
         $this->fieldsetMock->expects($this->any())->method('addField')->willReturnSelf();
        $this->catalogAdvancedMock->expects($this->once())->method('getForm')->willReturn($this->formMock);
        $this->advancedMock->aroundGetFormHtml($this->catalogAdvancedMock, function () {});
    }
}
