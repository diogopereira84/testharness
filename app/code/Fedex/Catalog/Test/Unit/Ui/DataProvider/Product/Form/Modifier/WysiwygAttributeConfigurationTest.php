<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Ui\DataProvider\Product\Form\Modifier;

use Fedex\Catalog\Model\Config;
use Fedex\Catalog\Ui\DataProvider\Product\Form\Modifier\WysiwygAttributeConfiguration;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Framework\Stdlib\ArrayManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class WysiwygAttributeConfigurationTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const IS_MULTISELECT = 'multiselect';
    const IS_PRODUCT_LEVEL_DEFAULT_ENABLED = 1;

    protected WysiwygAttributeConfiguration $wysiwygAttributeConfigurationMock;
    protected ArrayManager|MockObject $arrayManagerMock;
    protected Config|MockObject $configMock;

    protected function setUp(): void
    {
        $this->arrayManagerMock = $this
            ->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['wysiwygAttributeList'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->wysiwygAttributeConfigurationMock = $this->objectManager->getObject(
            WysiwygAttributeConfiguration::class,
            [
                'arrayManager' => $this->arrayManagerMock,
                'catalogConfig' => $this->configMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testModifyMeta(): void
    {
        $attributeCode = 'test_attribute';
        $wysiwygAttributeList = ['test_attribute'];
        $this->configMock->expects($this->once())->method('wysiwygAttributeList')->willReturn($wysiwygAttributeList);

        $meta = ['meta' => ['children' => ['container_' . $attributeCode => ['children' => [$attributeCode => [1]]]]]];
        $componentsToMerge = [
            'children' => [
                $attributeCode => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'wysiwygConfigData' => [
                                    'current_attribute_code' => $attributeCode
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
        $elementPath = 'meta/children/container_' . $attributeCode;
        $containerPath = 'meta/children/container_' . $attributeCode . '/children/' . $attributeCode;
        $finalMeta = ['meta' => [
            'children' => [
                'container_' . $attributeCode => [
                    1,
                    ['children' => [
                        $attributeCode => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'wysiwygConfigData' => [
                                            'current_attribute_code' => $attributeCode
                                        ],
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
            ->withConsecutive([$attributeCode, $meta, null, 'children'], ['container_' . $attributeCode, $meta, null, 'children'])
            ->willReturnOnConsecutiveCalls($elementPath, $containerPath);

        $this->arrayManagerMock->expects($this->once())->method('merge')
            ->with($containerPath, $meta, $componentsToMerge)->willReturn($finalMeta);

        $this->assertEquals($finalMeta, $this->wysiwygAttributeConfigurationMock->modifyMeta($meta));
    }

    /**
     * @return void
     */
    public function testModifyMetaNoElementPath(): void
    {
        $attributeCode = 'test_attribute';
        $wysiwygAttributeList = ['test_attribute'];
        $this->configMock->expects($this->once())->method('wysiwygAttributeList')->willReturn($wysiwygAttributeList);

        $meta = ['meta' => ['children' => ['container_' => ['children' => []]]]];

        $this->arrayManagerMock->expects($this->atMost(2))->method('findPath')
            ->withConsecutive([$attributeCode, $meta, null, 'children'], ['container_' . $attributeCode, $meta, null, 'children'])
            ->willReturnOnConsecutiveCalls('', '');

        $this->wysiwygAttributeConfigurationMock->modifyMeta($meta);
    }

    /**
     * @return void
     */
    public function testModifyData(): void
    {
        $data = ['modifyData'];
        $this->assertEquals($data, $this->wysiwygAttributeConfigurationMock->modifyData($data));
    }
}
