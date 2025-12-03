<?php
declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Ui\DataProvider\Product\Form\Modifier;

use Fedex\ProductEngine\Ui\DataProvider\Product\Form\Modifier\VisibleAttributesModifier;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Framework\Stdlib\ArrayManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class VisibleAttributesModifierTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const IS_MULTISELECT = 'multiselect';
    const IS_PRODUCT_LEVEL_DEFAULT_ENABLED = 1;

    protected VisibleAttributesModifier $visibleAttributesModifierMock;
    protected ArrayManager|MockObject $arrayManagerMock;
    protected CollectionFactory|MockObject $attributesCollectionMock;
    protected Collection|MockObject $collectionMock;
    protected ProductAttributeInterface|MockObject $attributeMock;

    protected function setUp(): void
    {
        $this->arrayManagerMock = $this
            ->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributesCollectionMock = $this->getMockBuilder(CollectionFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['addFieldToFilter', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeMock = $this->getMockBuilder(ProductAttributeInterface::class)
            ->onlyMethods(['getAttributeCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->visibleAttributesModifierMock = $this->objectManager->getObject(VisibleAttributesModifier::class,
            [
                'arrayManager' => $this->arrayManagerMock,
                'attributesCollection' => $this->attributesCollectionMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testModifyMeta(): void
    {
        $attributeCode = 'test_attribute';
        $this->attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $this->collectionMock->expects($this->atMost(2))->method('addFieldToFilter')
            ->withConsecutive(['frontend_input', self::IS_MULTISELECT], ['is_product_level_default', self::IS_PRODUCT_LEVEL_DEFAULT_ENABLED])->willReturnSelf();
        $this->collectionMock->expects($this->once())->method('getItems')->willReturn([$this->attributeMock]);
        $this->attributesCollectionMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $meta = ['meta' => ['children' => ['container_'.$attributeCode => ['children' => [$attributeCode => [1]]]]]];
        $componentsToMerge = [
            'children'  => [
                $attributeCode => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'component'     => 'Fedex_ProductEngine/js/form/element/multiselect-with-default-select',
                                'elementTmpl'   => 'Fedex_ProductEngine/form/element/multiselect-with-default-select',
                            ],
                        ],
                    ],
                ]
            ]
        ];
        $elementPath = 'meta/children/container_'.$attributeCode;
        $containerPath = 'meta/children/container_'.$attributeCode.'/children/'.$attributeCode;
        $finalMeta = ['meta' => [
            'children' => [
                'container_'.$attributeCode => [
                    1,
                    ['children'  => [
                        $attributeCode => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'component'     => 'Fedex_ProductEngine/js/form/element/multiselect-with-default-select',
                                        'elementTmpl'   => 'Fedex_ProductEngine/form/element/multiselect-with-default-select',
                                    ]
                                ]
                            ]
                        ]
                    ]
                    ]
                ]
            ]
        ]];

        $this->arrayManagerMock->expects($this->atMost(2))->method('findPath')
            ->withConsecutive([$attributeCode, $meta, null, 'children'], ['container_'.$attributeCode, $meta, null, 'children'])
            ->willReturnOnConsecutiveCalls($elementPath, $containerPath);

        $this->arrayManagerMock->expects($this->once())->method('merge')
            ->with($containerPath, $meta, $componentsToMerge)->willReturn($finalMeta);

        $this->assertEquals($finalMeta, $this->visibleAttributesModifierMock->modifyMeta($meta));
    }

    /**
     * @return void
     */
    public function testModifyMetaNoElementPath(): void
    {
        $attributeCode = 'test_attribute';
        $this->attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $this->collectionMock->expects($this->atMost(2))->method('addFieldToFilter')
            ->withConsecutive(['frontend_input', self::IS_MULTISELECT], ['is_product_level_default', self::IS_PRODUCT_LEVEL_DEFAULT_ENABLED])->willReturnSelf();
        $this->collectionMock->expects($this->once())->method('getItems')->willReturn([$this->attributeMock]);
        $this->attributesCollectionMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $meta = ['meta' => ['children' => ['container_' => ['children' => []]]]];

        $this->arrayManagerMock->expects($this->atMost(2))->method('findPath')
            ->withConsecutive([$attributeCode, $meta, null, 'children'], ['container_'.$attributeCode, $meta, null, 'children'])
            ->willReturnOnConsecutiveCalls('', '');

        $this->visibleAttributesModifierMock->modifyMeta($meta);
    }

    /**
     * @return void
     */
    public function testModifyData(): void
    {
        $data = ['modifyData'];
        $this->assertEquals($data, $this->visibleAttributesModifierMock->modifyData($data));
    }
}
