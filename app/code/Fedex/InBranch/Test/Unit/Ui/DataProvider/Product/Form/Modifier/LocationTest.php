<?php

declare(strict_types=1);

namespace Fedex\InBranch\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\InBranch\Ui\DataProvider\Product\Form\Modifier\Location;
use Fedex\InBranch\Model\Config\E366082DocumentLevelRouting;

class LocationTest extends TestCase
{
    private Location $locationModifier;
    private MockObject|LocatorInterface $locatorMock;
    private UrlInterface|MockObject $urlBuilderMock;
    private ArrayManager|MockObject $arrayManagerMock;
    private E366082DocumentLevelRouting|MockObject $toggleMock;

    const MODIFY_TEST_DATA = [
        "product-details" => [
            "children" => [
                "container_product_location_branch_number" => [
                    "arguments" => [
                        "data" => [
                            "config" => [
                                "formElement" => "container",
                                "componentType" => "container",
                                "breakLine" => false,
                                "label" => "Production Location",
                                "required" => "0",
                                "sortOrder" => 160
                            ]
                        ]
                    ],
                    "children" => [
                        "product_location_branch_number" => [
                            "arguments" => [
                                "data" => [
                                    "config" => [
                                        "dataType" => "text",
                                        "formElement" => "input",
                                        "visible" => "1",
                                        "required" => "0",
                                        "notice" => null,
                                        "default" => null,
                                        "label" => "Production Location",
                                        "code" => "product_location_branch_number",
                                        "source" => "product-details",
                                        "scopeLabel" => "[GLOBAL]",
                                        "globalScope" => true,
                                        "sortOrder" => 160,
                                        "componentType" => "field"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    const MODIFY_MERGED_TEST_DATA = [
        "product-details" => [
            "children" => [
                "container_product_location_branch_number" => [
                    "arguments" => [
                        "data" => [
                            "config" => [
                                "formElement" => "container",
                                "componentType" => "container",
                                "breakLine" => false,
                                "label" => "Production Location",
                                "required" => "0",
                                "sortOrder" => 160
                            ]
                        ]
                    ],
                    "children" => [
                        "product_location_branch_number" => [
                            "arguments" => [
                                "data" => [
                                    "config" => [
                                        "dataType" => "text",
                                        "formElement" => "input",
                                        "visible" => "1",
                                        "required" => "0",
                                        "notice" => null,
                                        "default" => null,
                                        "label" => "Production Location",
                                        "code" => "product_location_branch_number",
                                        "source" => "product-details",
                                        "scopeLabel" => "[GLOBAL]",
                                        "globalScope" => true,
                                        "sortOrder" => 160,
                                        "componentType" => "field",
                                        'tooltip' => [
                                            'description' => 'Blah blah blah'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    protected function setUp(): void
    {
        $this->locatorMock = $this->createMock(LocatorInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->toggleMock = $this->createMock(E366082DocumentLevelRouting::class);

        $this->locationModifier = new Location(
            $this->locatorMock,
            $this->urlBuilderMock,
            $this->arrayManagerMock,
            $this->toggleMock
        );
    }

    public function testModifyData()
    {
        $data = ['key' => 'value'];
        $result = $this->locationModifier->modifyData($data);
        $this->assertIsArray($result);
        $this->assertSame($data, $result);
        $this->assertInstanceOf(AbstractModifier::class, $this->locationModifier);
    }

    public function testModifyMetaActive()
    {
        $this->toggleMock->expects($this->any())
            ->method('isActive')
            ->willReturn(true);

        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('findPath')
            ->willReturnOnConsecutiveCalls(
                'product-details/children/container_product_location_branch_number/children/product_location_branch_number',
                'product-details/children/container_product_location_branch_number'
            );

        $this->arrayManagerMock->expects($this->once())
            ->method('merge')
            ->willReturn(self::MODIFY_MERGED_TEST_DATA);

        $result = $this->locationModifier->modifyMeta(self::MODIFY_TEST_DATA);
        $this->assertArrayHasKey(
            'tooltip',
            $result['product-details']['children']['container_product_location_branch_number']
            ['children']['product_location_branch_number']['arguments']['data']['config']
        );
        $this->assertIsArray($result);
        $this->assertInstanceOf(AbstractModifier::class, $this->locationModifier);
    }

    public function testModifyMetaInactive()
    {
        $this->toggleMock->expects($this->any())->method('isActive')->willReturn(false);
        $result = $this->locationModifier->modifyMeta(self::MODIFY_TEST_DATA);
        $this->assertArrayNotHasKey(
            'tooltip',
            $result['product-details']['children']['container_product_location_branch_number']
            ['children']['product_location_branch_number']['arguments']['data']['config']
        );
        $this->assertIsArray($result);
        $this->assertInstanceOf(AbstractModifier::class, $this->locationModifier);
    }
}
