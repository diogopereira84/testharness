<?php

namespace Fedex\ProductCustomAtrribute\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Fedex\ProductCustomAtrribute\Ui\DataProvider\Product\Form\Modifier\DltThresholdAttribute;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Eav\Setup\EavSetup;

class DltThresholdAttributeTest extends TestCase
{
    protected $productMock;
    protected $locatorMock;
    protected $productInterfaceMock;
    protected $productRepositoryMock;
    protected $attributeInterfaceMock;
    protected $eavSetup;
    protected $dltThresholdMock;
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var LocatorInterface
     */
    protected $locatorInterface;

    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getCustomAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMockForAbstractClass();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCollection', 'getCustomShippingdata'])
            ->getMockForAbstractClass();

        $this->productRepositoryMock = $this
            ->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->onlyMethods(['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMockForAbstractClass();
        $this->eavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->dltThresholdMock = $objectManagerHelper->getObject(
            DltThresholdAttribute::class,
            [
                'locator' => $this->locatorMock,
                'productRepository' => $this->productRepositoryMock,
            ]
        );
    }

    /**
     * @test ModifyData
     */
    public function testModifyData()
    {
        $metaData = [
            'dlt_start' => 1,
            'dlt_end' => 100,
            'dlt_hours' => 2,
        ];
        $attributeValue = '[{"record_id":"0","dlt_start":"1","dlt_end":"100","dlt_hours":"2"}]';
        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->with(12)
            ->willReturn($this->productInterfaceMock);
        $this->attributeInterfaceMock->expects($this->any())->method('getValue')->willReturn($attributeValue);
        $this->productMock->expects($this->any())->method('getCustomAttribute')
            ->with('dlt')
            ->willReturn($this->productRepositoryMock);

        $this->assertIsArray($this->dltThresholdMock->modifyData($metaData));
    }

    /**
     * @test ModifyData rendening
     */
    public function testRendendingData()
    {
        $metaData = [
            'dlt_start' => 1,
            'dlt_end' => 100,
            'dlt_hours' => 2,
        ];

        $attributeValue = '{"dlt_threshold_field":
            [{"record_id":"0","dlt_start":"1","dlt_end":"100","dlt_hours":"4","initialize":"true"}]}';
        $dltUnserialized = json_decode($attributeValue, true);
        $this->attributeInterfaceMock->expects($this->any())->method('getValue')
            ->with(true)
            ->willReturn($dltUnserialized);
        $this->productMock->expects($this->atMost(4))->method('getCustomAttribute')
            ->with('dlt')
            ->willReturn($this->attributeInterfaceMock);
        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);
        $this->assertIsArray($this->dltThresholdMock->modifyData($metaData));
    }

    /**
     * @test decode in json 
     */
    public function testJsonCoverted()
    {
        $metaData = [
            'dlt_threshold_field' => [
                'record_id' => 0,
                'dlt_start' => 1,
                'dlt_end' => 100,
                'dlt_hours' => 2,
                'initialize' => true,

            ],
        ];
        $array = [
            'dlt_threshold_field' => [
                'record_id' => '0',
                'dlt_start' => '1',
                'dlt_end' => '100',
                'dlt_hours' => '2',
                'initialize' => 'true',
            ],
        ];
        $this->attributeInterfaceMock->expects($this->any())->method('getValue')->willReturn(1);
        $this->productMock->expects($this->atMost(4))->method('getCustomAttribute')
            ->with('dlt')
            ->willReturn($this->attributeInterfaceMock);
        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);
        $this->assertEquals($array, $this->dltThresholdMock->modifyData($metaData));
    }

    /**
     * @test modifyMeta
     */
    public function testModifyMeta()
    {
        $printondemandAttributeSetId = 12;
        $this->locatorMock->expects($this->any())
        ->method('getProduct')
        ->willReturn($this->productMock);
        $this->productInterfaceMock->expects($this->any())
        ->method('getId')
        ->willReturn(123);
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterfaceMock);
        $this->eavSetup->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn($this->productRepositoryMock);
        $meta = [
            'product-details' => [
                'children' => [
                    'dlt_start' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => 'field',
                                    'formElement' => 'checkbox',
                                    'dataScope' => 'dlt_start',
                                    'prefer' => 'toggle',
                                    'dataType' => 'boolean',
                                    'sortOrder' => 60,
                                    'valueMap' => [
                                        'true' => 1,
                                        'false' => 0,
                                    ],
                                ],

                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertIsArray($this->dltThresholdMock->modifyMeta($meta));
    }

    /**
     * test getSelectTypeGridConfig
     */
    public function testGetSelectTypeGridConfig()
    {
        $sortOrder = 0 ;
        $this->dltThresholdMock->getSelectTypeGridConfig($sortOrder);
    }

}
