<?php

namespace Fedex\CatalogMvp\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier\AbstractModifierTest;
use Fedex\CatalogMvp\Ui\DataProvider\Product\Form\Modifier\Pod2Editable;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class Pod2EditableTest extends TestCase
{
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Product\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productCollectionMock;
    protected $catalogMvpHelperMock;
    protected $locatorMock;
    /**
     * @var (\Magento\Catalog\Model\Product & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productMock;
    protected $productInterfaceMock;
    protected $pod20EditableMock;
    protected $objectManager;
    protected $locatorInterface;
    protected $catalogMvpHelper;

    protected function setUp(): void
    {

        $this->productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getFirstItem','getData'])
            ->getMock();
        
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProductPodEditAbleById','getFxoMenuId'])
            ->getMock();

        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMockForAbstractClass();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        
        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getCollection'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->pod20EditableMock = $objectManagerHelper->getObject(
            Pod2Editable::class,
            [
                'productCollection' => $this->productCollectionMock,
                'locator' => $this->locatorMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'product' => $this->productMock
            ]
        );
    }

    public function testModifyData()
    {
        $productData = [
            'entity_id' => '69',
            'attribute_set_id' => '12',
            'type_id' => 'simple',
            'sku' => 'Demo1',
            'has_options' => '0',
            'required_options' => '0',
            'created_at' => '2020-10-26 22:25:49',
            'updated_at' => '2023-09-21 08:37:03',
            'row_id' => '69',
            'created_in' => '1',
            'updated_in' => '2147483647',
            'mirakl_images_status' => '3',
            'mirakl_mcm_product_id' => '',
            'mirakl_mcm_variant_group_code' => '',
            'pod2_0_editble' => '1',
            'pod2_0_editable' => '1',
            'store_id' => '0',
        ];

        $metaData = [
            '69' => [
                'product' => [
                        'status' => '2',
                        'name' => 'Metal Signs',
                        'sku' => 'Demo1',
                        'price' => '39.99',
                        'start_date_pod' => '2023 - 09 - 21 10: 12: 00',
                        'end_date_pod' => '2023 - 09 - 22 10: 12: 00',
                        'published' => 0,
                        'tax_class_id' => 2,
                        'quantity_and_stock_status' => [
                        'is_in_stock' => 1,
                        'qty' => 999
                        ],
                        'category_ids' => [
                        '0' => 2,
                        '1' => 24
                        ],
                        'external_prod' => 'test_123',
                        'visibility' => 4,
                        'customizable' => 0,
                        'admin_user_id' => 515,
                        'description' => "",
                        'image' => '/c/h / chilicookoff_metalsign_525x525.jpg',
                        'small_image' => 'no_selection',
                        'thumbnail' => '/c/h / chilicookoff_metalsign_525x525.jpg',
                        'url_key' => 'metal - signs',
                        'meta_keyword' => 'Metal Signs',
                        'tier_price' => '',
                        'attribute_set_id' => 12,
                        'stock_data' => [
                            'item_id' => 75,
                            'product_id' => 69,
                            'stock_id' => 1,
                            'qty' => 999
                        ],
                        'links_title' => 'Links',
                        'links_purchased_separately' => 0,
                        'samples_title' => 'Samples',
                        'current_product_id' => 69,
                        'affect_product_custom_options' => 1,
                        'options' => [],
                        'pod2_0_editable' => 1,
                        'is_downloadable' => 0,
                        'downloadable' => [
                            'link' => [],
                            'sample' => []
                        ]
                    ],
                'config' => [
                'submit_url' => 'https://test.com',
                'validate_url' => 'https://test.com',
                'reloadUrl' => 'https://test.com'
                ]
            ]
        ];

        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(1);
        $this->catalogMvpHelperMock->expects($this->any())
            ->method('getFxoMenuId')
            ->willReturn('1582146604697-4');
        
        $this->assertIsArray($this->pod20EditableMock->modifyData($metaData));
    }

    public function testModifyMeta()
    {
        $meta = [
            'product-details' => [
                'children' => [
                    'pod2_0_editable' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => 'field',
                                    'formElement' => 'checkbox',
                                    'dataScope' => 'pod2_0_editable',
                                    'prefer' => 'toggle',
                                    'dataType' => 'boolean',
                                    'sortOrder' => 60,
                                    'valueMap' => [
                                        'true' => 1,
                                        'false' => 0
                                    ]
                                ]
        
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->pod20EditableMock->getPod20EditableField();
        
        $this->assertIsArray($this->pod20EditableMock->modifyMeta($meta));
    }

    public function testGetPod20EditableField()
    {
        $data = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'field',
                        'formElement' => 'checkbox',
                        'dataScope' => 'pod2_0_editable',
                        'prefer' => 'toggle',
                        'dataType' => 'boolean',
                        'sortOrder' => 60,
                        'valueMap' => [
                            'true' => 1,
                            'false' => 0
                        ]
                    ]

                ]
            ]
        ];
        
        $this->assertIsArray($this->pod20EditableMock->getPod20EditableField($data));
    }
}
