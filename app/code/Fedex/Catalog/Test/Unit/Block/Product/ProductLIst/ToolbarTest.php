<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Block\Product\ProductList;

use Fedex\Catalog\Block\Product\ProductList\Toolbar as ToolbarBlock;
use Magento\Catalog\Block\Product\ProductList\Toolbar as ParentToolbar;
use Magento\Framework\Data\Collection;
use Magento\Search\Helper\Data as SearchHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Fedex\CatalogMvp\Helper\CatalogMvp;
/* B-1573026 */
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;

class ToolbarTest extends TestCase
{

    protected $searchHelperMock;
    protected $listProductMock;
    protected $productCollection;
    protected $productListHelper;
    protected $catalogConfig;
    /**
     * @var (\Magento\Catalog\Block\Product\ProductList\Toolbar & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $parentToolbarMock;
    protected $catalogMvpMock;
    protected $model;
    protected $memorizer;
    protected $ToolbarBlock;
    protected function setUp(): void
    {

        $this->searchHelperMock = $this
            ->getMockBuilder(SearchHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listProductMock = $this
            ->getMockBuilder(ListProduct::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLoadedProductCollection'])
            ->getMock();

        /* B-1573026 */
        $this->productCollection = $this
            ->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCurPage', 'setPageSize', 'addAttributeToSort', 'setOrder'])
            ->getMock();

        $this->productListHelper = $this->createMock(ProductList::class);
        $this->catalogConfig = $this->createPartialMock(
            Config::class,
            ['getAttributeUsedForSortByArray']
        );

        $this->parentToolbarMock = $this
            ->getMockBuilder(ParentToolbar::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','count'])
            ->getMock();

            $this->catalogMvpMock = $this
            ->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable','getChildCategoryCount', 'getToggleStatusForNewProductUpdatedAtToggle'])
            ->getMock();

        /* B-1573026 */
        $this->model = $this->createPartialMock(\Magento\Catalog\Model\Product\ProductList\Toolbar::class, [
            'getDirection',
            'getOrder',
            'getMode',
            'getLimit',
            'getCurrentPage',
        ]);

        $this->memorizer = $this->createPartialMock(
            ToolbarMemorizer::class,
            [
                'getDirection',
                'getOrder',
                'getMode',
                'getLimit',
                'isMemorizingAllowed',
            ]
        );

        $objectManagerHelper = new ObjectManager($this);

        $this->ToolbarBlock = $objectManagerHelper->getObject(
            ToolbarBlock::class,
            [
                'searchHelper' => $this->searchHelperMock,
                'listProduct' => $this->listProductMock,
                'catalogMvp' => $this->catalogMvpMock,
                'toolbarModel' => $this->model,
                'toolbarMemorizer' => $this->memorizer,
                'productListHelper' => $this->productListHelper,
                'catalogConfig' => $this->catalogConfig
            ]
        );
    }

    /**
     * testGetSearchHelper
     *
     */
    public function testGetSearchHelper(){
    	$this->searchHelperMock;
    	$this->assertEquals(
            $this->searchHelperMock,
            $this->ToolbarBlock->getSearchHelper()
        );
    }

     /**
     * testGetLoadedProductCollectionCount
     *
     */
    public function testGetLoadedProductCollectionCount() {
        $collection = $this->createMock(ProductCollection::class);
        $this->listProductMock->expects($this->any())->method('getLoadedProductCollection')->willReturn($collection);
        $this->ToolbarBlock->getLoadedProductCollectionCount();
    }
    /**
     * testisMvpCatalogEnabled
     *
     */
    public function testisMvpCatalogEnabled()
    {
        $this->catalogMvpMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->assertNotNull( $this->ToolbarBlock->isMvpCatalogEnabled());
    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * Test case for setCollection
     */
    public function testSetCollection()
    {
        $order = 'position';
        $this->setCollection($order);
        $this->ToolbarBlock->setCollection($this->productCollection);

    }

     /**
      * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * Test case for setCollection with Most Recent
     */
    public function testSetCollectionWithMostRecent()
    {
        $order = 'most_recent';
        $this->setCollection($order);
        $this->ToolbarBlock->setCollection($this->productCollection);

    }
    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * Test case for setCollection with Name ASC
     */
    public function testSetCollectionWithNameAsc()
    {
        $order = 'name_asc';
        $this->setCollection($order);
        $this->ToolbarBlock->setCollection($this->productCollection);

    }
    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * Test case for setCollection with Name DESC
     */
    public function testSetCollectionWithNameDesc()
    {
        $order = 'name_desc';
        $this->setCollection($order);
        $this->ToolbarBlock->setCollection($this->productCollection);

    }
    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * Test case for setCollection with Price
     */
    public function testSetCollectionWithPrice()
    {
        $order = 'price';
        $this->setCollection($order);
        $this->ToolbarBlock->setCollection($this->productCollection);

    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * case for setCollection
     */
    public function setCollection($order)
    {
        $page = 3;
        $this->model->expects($this->once())
            ->method('getCurrentPage')
            ->willReturn($page);

        $this->productCollection->expects($this->any())->method('setCurPage')->willReturnSelf();

        $mode = 'list';
        $limit = 10;

        $this->memorizer->expects($this->any())
            ->method('getMode')
            ->willReturn($mode);

        $data = ['name' => [], 'price' => [],'position'=>[],'most_recent'=>[],'name_asc'=>[],'name_desc'=>[]];
        $this->catalogConfig->expects($this->once())
            ->method('getAttributeUsedForSortByArray')
            ->willReturn($data);

        $this->memorizer->expects($this->any())
            ->method('getLimit')
            ->willReturn($limit);
        $this->productListHelper->expects($this->any())
            ->method('getAvailableLimit')
            ->willReturn([10 => 10, 20 => 20]);
        $this->productListHelper->expects($this->any())
            ->method('getDefaultLimitPerPageValue')
            ->with('list')
            ->willReturn(10);
        $this->productListHelper->expects($this->any())
            ->method('getAvailableViewMode')
            ->willReturn(['list' => 'List']);

        $this->memorizer->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $this->catalogConfig->expects($this->once())
            ->method('getAttributeUsedForSortByArray')
            ->willReturn(['name' => [], 'price' => []]);
    }

   /**
    * Test case for getChildCategoryCount
     */
    public function testgetChildCategoryCount()
    {
        $this->catalogMvpMock->expects($this->any())->method('getChildCategoryCount')->willReturn(12);
        $this->assertNotNull( $this->ToolbarBlock->getChildCategoryCount());
    }

    /**
     * Test Toggle B-2193925 Product updated at toggle
     *
     */
    public function testGetToggleStatusForNewProductUpdatedAtToggle()
    {
        $this->catalogMvpMock->expects($this->any())->method('getToggleStatusForNewProductUpdatedAtToggle')->willReturn(true);
        $this->assertNotNull( $this->ToolbarBlock->getToggleStatusForNewProductUpdatedAtToggle());
    }

}
