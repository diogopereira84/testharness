<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Test\Unit\Block\Product;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Fedex\CustomizedMegamenu\Block\Product\ProductsList;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Http\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\DesignInterface;
use Magento\Rule\Model\Condition\Sql\Builder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductsListTest extends TestCase
{
    protected $categoryFactory;
    protected $categoryMock;
    const PRODUCT_POSITIONS = ['620' => 2, '539' => 0, '542' => 1];
    /**
     * @var ProductsList
     */
    protected $productsList;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactory;

    /**
     * @var Visibility|MockObject
     */
    protected $visibility;

    /**
     * @var Context|MockObject
     */
    protected $httpContext;

    /**
     * @var Builder|MockObject
     */
    protected $builder;

    /**
     * @var Rule|MockObject
     */
    protected $rule;

    /**
     * @var Conditions|MockObject
     */
    protected $widgetConditionsHelper;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var DesignInterface
     */
    protected $design;

    /**
     * @var Json|MockObject
     */
    private $serializer;
    private MockObject|Collection $collectionMock;

    protected function setUp(): void
    {
        $this->collectionFactory =
            $this->getMockBuilder(CollectionFactory::class)
                ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->visibility = $this->getMockBuilder(Visibility::class)
            ->setMethods(['getVisibleInCatalogIds'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpContext = $this->createMock(Context::class);
        $this->builder = $this->createMock(Builder::class);
        $this->rule = $this->createMock(Rule::class);
        $this->serializer = $this->createMock(Json::class);
        $this->widgetConditionsHelper = $this->getMockBuilder(Conditions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->design = $this->getMockForAbstractClass(DesignInterface::class);

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $this->categoryMock = $this->getMockBuilder(Category::class)
                ->setMethods(['load', 'getProductsPosition'])
                ->disableOriginalConstructor()
                ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->productsList = $objectManagerHelper->getObject(
            ProductsList::class,
            [
                'productCollectionFactory' => $this->collectionFactory,
                'catalogProductVisibility' => $this->visibility,
                'httpContext' => $this->httpContext,
                'sqlBuilder' => $this->builder,
                'rule' => $this->rule,
                'conditionsHelper' => $this->widgetConditionsHelper,
                'storeManager' => $this->storeManager,
                'design' => $this->design,
                'json' => $this->serializer,
                'categoryFactory' => $this->categoryFactory
            ]
        );
    }

    /**
     * Test method for getMenuCategoryProductsPostions
     */
    public function testGetMenuCategoryProductsPostions()
    {
        $conditions = '^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`category_ids`,`operator`:`==`,`value`:`27`^]^]';

        $conditionsDecoded = [
                                [
                                    'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Combine',
                                    'aggregator' => 'all',
                                    'value' => 1,
                                    'new_child' => ''
                                ],
                                [
                                    'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Product',
                                    'attribute' => 'category_ids',
                                    'operator' => '==',
                                    'value' => 27
                                ]
                            ];
        $this->productsList->setData('conditions_encoded', $conditions);

        $this->widgetConditionsHelper->expects($this->any())
            ->method('decode')
            ->with($conditions)
            ->willReturn($conditionsDecoded);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getProductsPosition')->willReturn(self::PRODUCT_POSITIONS);
        $this->assertEquals(self::PRODUCT_POSITIONS, $this->productsList->getMenuCategoryProductsPostions());
    }

    /**
     * Test method for getMenuCategoryProductsPostions Without Category
     */
    public function testGetMenuCategoryProductsWithoutCategory()
    {
        $categoryId = null;
        $conditions = '^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`category_ids`,`operator`:`==`,`value`:`27`^]^]';

        $conditionsDecoded = [
                                [
                                    'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Combine',
                                    'aggregator' => 'all',
                                    'value' => 1,
                                    'new_child' => ''
                                ],
                                [
                                    'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Product',
                                    'attribute' => 'category_ids',
                                    'operator' => '==',
                                    'value' => null
                                ]
                            ];
        $this->productsList->setData('conditions_encoded', $conditions);

        $this->widgetConditionsHelper->expects($this->any())
            ->method('decode')
            ->with($conditions)
            ->willReturn($conditionsDecoded);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getProductsPosition')->willReturn(null);
        $this->assertEquals(null, $this->productsList->getMenuCategoryProductsPostions());
    }

    /**
     * Test method for getMenuCategoryProductsPostions Without Category
     */
    public function testGetMenuCategoryProductsWithoutCategoryWithDiffGetData()
    {
        $categoryId = null;
        $conditions = '^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`category_ids`,`operator`:`==`,`value`:`27`^]^]';

        $conditionsDecoded = [
                                [
                                    'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Combine',
                                    'aggregator' => 'all',
                                    'value' => 1,
                                    'new_child' => ''
                                ],
                                [
                                    'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Product',
                                    'attribute' => 'category_ids',
                                    'operator' => '==',
                                    'value' => null
                                ]
                            ];
        $this->productsList->setData('conditions', $conditions);

        $this->widgetConditionsHelper->expects($this->any())
            ->method('decode')
            ->with($conditions)
            ->willReturn($conditionsDecoded);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getProductsPosition')->willReturn(null);
        $this->assertEquals(null, $this->productsList->getMenuCategoryProductsPostions());
    }

    /**
     * Test method for getProducts
     */
    public function testGetProducts()
    {
        $productIds = [539, 542, 620];

        $this->testGetMenuCategoryProductsPostions();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
                ->setMethods(['addAttributeToSelect', 'addAttributeToFilter', 'load'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->productsList->setData('products_count', 5);
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $items = new DataObject(
                ['id' => 539, 'url' => 'certificates.html', 'name' => 'Certificates', 'position' => 0],
                ['id' => 542, 'url' => 'manuals.html', 'name' => 'Manuals', 'position' => 2],
                ['id' => 620, 'url' => 'copies.html', 'name' => 'Copies', 'position' => 1]
            );
        $this->collectionMock->addItem($items);
        $this->collectionMock->expects($this->any())->method('addAttributeToSelect')->with('*')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('addAttributeToFilter')
        ->with('entity_id', ['in' => $productIds])->willReturn($this->collectionMock);

        $this->productsList->getProducts();
    }
}
