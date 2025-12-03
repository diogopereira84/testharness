<?php

declare(strict_types=1);

namespace Fedex\Canva\Test\Ui\DataProvider\Product\Form\Modifier;

use Fedex\Canva\Ui\DataProvider\Product\Form\Modifier\OptionsGridModifier;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptionsGridModifierTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const CONTAINER_PREFIX = 'container_';
    public const ATTRIBUTE_CODE = 'canva_size';
    public const ELEMENT_COMPONENT = 'Fedex_Canva/js/form/element/options';
    public const ELEMENT_TEMPLATE = 'Fedex_Canva/form/element/options';

    protected OptionsGridModifier $optionsGridModifierMock;
    protected ArrayManager|MockObject $arrayManagerMock;
    protected CollectionFactory|MockObject $attributesCollectionFactoryMock;

    protected function setUp(): void
    {
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->onlyMethods(['findPath', 'merge'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributesCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->optionsGridModifierMock = $this->objectManager->getObject(
            OptionsGridModifier::class,
            [
                'arrayManager' => $this->arrayManagerMock,
                'attributesCollection' => $this->attributesCollectionFactoryMock
            ]
        );
    }

    /**
     * Test modifyData method.
     *
     * @return void
     */
    public function testModifyData(): void
    {
        $data = ['modifyData'];
        $this->assertEquals($data, $this->optionsGridModifierMock->modifyData($data));
    }

    /**
     * @return void
     */
    public function testModifyMeta(): void
    {
        $meta = ['canva' => ['children' => ['container_canva_size' => [1]]]];
        $finalMeta = ['canva' => ['children' => ['container_canva_size' => [1, ['children'  => [self::ATTRIBUTE_CODE => ['arguments' => ['data' => ['config' => ['component' => self::ELEMENT_COMPONENT,'elementTmpl' => self::ELEMENT_TEMPLATE]]]]]]]]]];
        $containerPath = 'canva/children/container_canva_size';

        $this->arrayManagerMock->expects($this->any())->method('findPath')
            ->with(
                (static::CONTAINER_PREFIX . self::ATTRIBUTE_CODE),
                $meta,
                null,
                'children'
            )
        ->willReturn($containerPath);

        $this->arrayManagerMock->expects($this->once())->method('merge')
            ->with(
                $containerPath,
                $meta,
                [
                    'children'  => [
                        self::ATTRIBUTE_CODE => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'component'     => self::ELEMENT_COMPONENT,
                                        'elementTmpl'   => self::ELEMENT_TEMPLATE,
                                    ],
                                ],
                            ],
                        ]
                    ]
                ]
            )
        ->willReturn($finalMeta);
        $return = $this->optionsGridModifierMock->modifyMeta($meta);

        $this->assertIsArray($return);
        $this->assertEquals($finalMeta, $return);
    }

    /**
     * @return void
     */
    public function testModifyMetaContainerNull(): void
    {
        $meta = [[['container_test' => [1]]]];
        $this->arrayManagerMock->expects($this->any())->method('findPath')
            ->with(
                (static::CONTAINER_PREFIX . self::ATTRIBUTE_CODE),
                $meta,
                null,
                'children'
            )
        ->willReturn(null);

        $return = $this->optionsGridModifierMock->modifyMeta($meta);
        $this->assertIsArray($return);
        $this->assertEquals([[['container_test' => [1]]]], $return);
    }
}
