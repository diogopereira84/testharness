<?php

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Categories as CoreCategories;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Backend\Model\Session as AdminSession;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Plugin\Categories;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\Category;

class CategoriesTest extends TestCase
{
    protected $helperMock;
    protected $categoryCollectionFactoryMock;
    protected $locatorMock;
    /**
     * @var (\Magento\Backend\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $adminSessionMock;
    protected $productMock;
    protected $categoryMock;
    protected $coreCategoryMock;
    protected $productInterfaceMock;
    protected $storeInterfaceMock;
    protected $categoryCollectionMock;
    protected $modelCategoryMock;
    protected $objectManager;
    protected $categoriesInstance;

    protected function setUp(): void
    {

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable', 'getAttributeSetName',
            'getRootCategoryFromStore', 'getScopeConfigValue', 'getSubCategoryByParentID','isProductPodEditAbleById','getRootCategoryDetailFromStore'])
            ->getMock();

        $this->categoryCollectionFactoryMock = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMockForAbstractClass();

        $this->adminSessionMock = $this->getMockBuilder(AdminSession::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getData'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Categories::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreCategoryMock = $this->getMockBuilder(CoreCategories::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->categoryCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter', 'addAttributeToSelect', 'setStoreId', 'getPath','getIterator'])
            ->getMock();

        $this->modelCategoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath'])
            ->getMock();    

        $objectManagerHelper = new ObjectManager($this);

        $this->categoryMock = $objectManagerHelper->getObject(
            Categories::class,
            [
                'helper' => $this->helperMock,
                'categoryCollectionFactory' => $this->categoryCollectionFactoryMock,
                'locator' => $this->locatorMock,
                'adminSession' => $this->adminSessionMock,
                'product' => $this->productMock
            ]
        );
    }

    public function testAfterModifyMeta()
    {

        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $this->productInterfaceMock->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(12);

        $this->helperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true); 

        $this->helperMock->expects($this->any())
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand'); 

        $this->helperMock->expects($this->any())
            ->method('isProductPodEditAbleById')
            ->willReturn(true);

        $this->locatorMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->helperMock->expects($this->any())
            ->method('getRootCategoryFromStore')
            ->willReturn(12);

        $this->helperMock->expects($this->any())
            ->method('getScopeConfigValue')
            ->willReturn(9);

        $this->helperMock->expects($this->any())
            ->method('getSubCategoryByParentID')
            ->willReturn([
                [
                    'label' => 'Subcategory 2',
                    'value' => 2,
                ],
                [
                    'label' => 'Subcategory 1',
                    'value' => 1,
                ],
            ]);

        $this->categoryCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->categoryCollectionMock);

        $this->categoryCollectionMock->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();

        $categoryIterator = new \ArrayIterator([0 => $this->modelCategoryMock]);
        $this->categoryCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($categoryIterator);

        $this->modelCategoryMock->expects($this->any())
            ->method('getPath')
            ->willReturn('1/2/56');

        $this->assertIsArray($this->categoryMock->afterModifyMeta($this->coreCategoryMock, []));
    }

    public function testAfterModifyMetaToggleOff()
    {

        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $this->productInterfaceMock->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(12);

        $this->helperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(false);

            $this->assertIsArray($this->categoryMock->afterModifyMeta($this->coreCategoryMock, []));
    }

    public function testAfterModifyMetaAttributeSetName()
    {

        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $this->productInterfaceMock->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(12);

        $this->helperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->helperMock->expects($this->any())
            ->method('getAttributeSetName')
            ->willReturn('');

            $this->assertIsArray($this->categoryMock->afterModifyMeta($this->coreCategoryMock, []));
    }

    public function testAfterModifyMetaCheckPodEditable()
    {

        $this->locatorMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $this->productInterfaceMock->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(12);

        $this->helperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);

        $this->helperMock->expects($this->any())
            ->method('getAttributeSetName')
            ->willReturn('PrintOnDemand');

        $this->productMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('getData')
            ->willReturn(0);

            $this->assertIsArray($this->categoryMock->afterModifyMeta($this->coreCategoryMock, []));
    }


    public function testSortCategories()
    {
        // Test data (categories with 'label' and some nested 'optgroup')
        $categories = [
            [
                'label' => 'Category B',
                'value' => 2,
                'optgroup' => [
                    [
                        'label' => 'Subcategory 2A',
                        'value' => 5,
                    ],
                    [
                        'label' => 'Subcategory 2B',
                        'value' => 6,
                    ],
                ]
            ],
            [
                'label' => 'Category A',
                'value' => 1,
                'optgroup' => [
                    [
                        'label' => 'Subcategory 1A',
                        'value' => 3,
                    ],
                    [
                        'label' => 'Subcategory 1B',
                        'value' => 4,
                    ],
                ]
            ],
            [
                'label' => 'Category C',
                'value' => 3,
            ]
        ];

        // Expected sorted order
        $expectedSortedCategories = [
            [
                'label' => 'Category A',
                'value' => 1,
                'optgroup' => [
                    [
                        'label' => 'Subcategory 1A',
                        'value' => 3,
                    ],
                    [
                        'label' => 'Subcategory 1B',
                        'value' => 4,
                    ],
                ]
            ],
            [
                'label' => 'Category B',
                'value' => 2,
                'optgroup' => [
                    [
                        'label' => 'Subcategory 2A',
                        'value' => 5,
                    ],
                    [
                        'label' => 'Subcategory 2B',
                        'value' => 6,
                    ],
                ]
            ],
            [
                'label' => 'Category C',
                'value' => 3,
            ]
        ];

        $categoriesModel = $this->getMockBuilder(Categories::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionMethod = new ReflectionMethod(Categories::class, 'sortCategories');
        $reflectionMethod->setAccessible(true);

        $sortedCategories = $reflectionMethod->invoke($categoriesModel, $categories);

        $this->assertEquals($expectedSortedCategories, $sortedCategories);
    }

    public function testSortCategoriesWithNull()
    {
        $categoriesModel = $this->getMockBuilder(Categories::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionMethod = new ReflectionMethod(Categories::class, 'sortCategories');
        $reflectionMethod->setAccessible(true);

        $sortedCategories = $reflectionMethod->invoke($categoriesModel, null);

        $this->assertEquals([], $sortedCategories);
    }


}