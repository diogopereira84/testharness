<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Block\Adminhtml\Attribute\Edit\Options;

use Fedex\ProductEngine\Block\Adminhtml\Attribute\Edit\Options\Options;
use Magento\Backend\Block\Template\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Store\Api\Data\StoreInterface as StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    protected Options $optionsMock;
    protected Context|MockObject $contextMock;
    protected Registry|MockObject $registryMock;
    protected CollectionFactory|MockObject $attrOptionCollectionFactoryMock;
    protected Option|MockObject $optionModelMock;
    protected UniversalFactory|MockObject $universalFactory;
    protected StoreInterface|MockObject $storeInterfaceMock;

    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attrOptionCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->optionModelMock = $this->getMockBuilder(Option::class)
            ->addMethods(['getId', 'getSortOrder', 'getChoiceId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->universalFactory = $this->getMockBuilder(UniversalFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->optionsMock = $this->getMockBuilder(Options::class)
            ->onlyMethods(['getStores', 'getStoreOptionValues', 'escapeHtml'])
            ->disableOriginalConstructor()
            ->setConstructorArgs(
                [
                    $this->contextMock,
                    $this->registryMock,
                    $this->attrOptionCollectionFactoryMock,
                    $this->universalFactory,
                    []
                ])
            ->getMockForAbstractClass();
    }

    public function testPrepareUserDefinedAttributeOptionValues(): void
    {
        $storeId = 1;
        $this->storeInterfaceMock->expects($this->once())->method('getId')->willReturn($storeId);
        $this->optionsMock->expects($this->once())->method('getStores')->willReturn([$this->storeInterfaceMock]);
        $this->optionsMock->expects($this->once())->method('getStoreOptionValues')->with($storeId)->willReturn([1 => 'label for store']);
        $this->optionsMock->expects($this->once())->method('escapeHtml')->with('label for store')
            ->willReturn(htmlspecialchars('label for store', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false));

        $this->optionModelMock->expects($this->once())->method('getId')->willReturn(1);
        $this->optionModelMock->expects($this->once())->method('getSortOrder')->willReturn('1');
        $this->optionModelMock->expects($this->once())->method('getChoiceId')->willReturn(15);

        $reflectionMethod = new \ReflectionMethod($this->optionsMock, '_prepareUserDefinedAttributeOptionValues');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->optionsMock, $this->optionModelMock, 'field', [1]);

        $expected = [
            'store1' => 'label for store',
            'checked' => 'checked="checked"',
            'intype' => 'field',
            'id' => 1,
            'sort_order' => '1',
            'choice_id' => 15
        ];

        $this->assertEquals([$expected], $actual);
    }

    public function testPrepareUserDefinedAttributeOptionValuesEmpty(): void
    {
        $storeId = 1;
        $this->storeInterfaceMock->expects($this->once())->method('getId')->willReturn($storeId);
        $this->optionsMock->expects($this->once())->method('getStores')->willReturn([$this->storeInterfaceMock]);
        $this->optionsMock->expects($this->once())->method('getStoreOptionValues')->with($storeId)->willReturn([]);

        $this->optionModelMock->expects($this->once())->method('getId')->willReturn(1);
        $this->optionModelMock->expects($this->once())->method('getSortOrder')->willReturn('1');
        $this->optionModelMock->expects($this->once())->method('getChoiceId')->willReturn(15);

        $reflectionMethod = new \ReflectionMethod($this->optionsMock, '_prepareUserDefinedAttributeOptionValues');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->optionsMock, $this->optionModelMock, 'field', [1]);

        $expected = [
            'store1' => '',
            'checked' => 'checked="checked"',
            'intype' => 'field',
            'id' => 1,
            'sort_order' => '1',
            'choice_id' => 15
        ];

        $this->assertEquals([$expected], $actual);
    }
}
