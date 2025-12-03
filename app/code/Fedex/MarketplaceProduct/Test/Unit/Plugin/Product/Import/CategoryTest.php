<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Plugin\Product\Import;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Mirakl\Mci\Helper\Config;
use Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product\ModelPopup;
use Magento\Catalog\Model\Product as ProductModel;
use Fedex\MarketplaceAdmin\Model\Config as ToggleSelfReg;
use Fedex\MarketplaceProduct\Plugin\Product\Import\Category;

class CategoryTest extends TestCase
{
    /**
     * @var Category
     */
    private $category;

    /**
     * @var ToggleSelfReg
     */
    private $toggleSelfReg;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var CategoryResourceFactory
     */
    private $categoryResourceFactory;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var AttributeSetCollectionFactory
     */
    private $attrSetCollectionFactory;

    /**
     * @var ModelPopup
     */
    private $modelPopup;

    /**
     * @var ProductModel
     */
    private $product;

    /**
     * @inheirtdoc
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleSelfReg = $this->createMock(ToggleSelfReg::class);
        $this->config = $this->createMock(Config::class);
        $this->categoryFactory = $this->createMock(CategoryFactory::class);
        $this->categoryResourceFactory = $this->createMock(CategoryResourceFactory::class);
        $this->categoryCollectionFactory = $this->createMock(CategoryCollectionFactory::class);
        $this->attrSetCollectionFactory = $this->createMock(AttributeSetCollectionFactory::class);
        $this->modelPopup = $this->createMock(ModelPopup::class);
        $this->product = $this->getMockBuilder(ProductModel::class)
            ->setMethods(['getCategoryIds','setCategoryIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->category = new Category(
            $this->toggleSelfReg,
            $this->config,
            $this->categoryFactory,
            $this->categoryResourceFactory,
            $this->categoryCollectionFactory,
            $this->attrSetCollectionFactory,
            $this->modelPopup
        );
    }

    /**
     * Test afterAddCategoryToProduct with toggle enabled.
     *
     * @return void
     */
    public function testAfterAddCategoryToProductWithEnabledSelfReg(): void
    {
        $this->toggleSelfReg->expects($this->once())
            ->method('isMktSelfregEnabled')
            ->willReturn(true);

        $this->modelPopup->expects($this->once())
            ->method('getPrintProductCategory')
            ->willReturn(1);

        $this->product->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn([2, 3]);

        $this->product->expects($this->once())
            ->method('setCategoryIds')
            ->with([2, 3, 1]);

        $result = $this->category->afterAddCategoryToProduct($this->category, $this->product);

        $this->assertSame($this->product, $result);
    }

    /**
     * Test afterAddCategoryToProduct with toggle disabled.
     *
     * @return void
     */
    public function testAfterAddCategoryToProductWithDisabledSelfReg(): void
    {
        $this->toggleSelfReg->expects($this->once())
            ->method('isMktSelfregEnabled')
            ->willReturn(false);

        $this->modelPopup->expects($this->never())
            ->method('getPrintProductCategory');

        $this->product->expects($this->never())
            ->method('getCategoryIds');

        $this->product->expects($this->never())
            ->method('setCategoryIds');

        $result = $this->category->afterAddCategoryToProduct($this->category, $this->product);

        $this->assertSame($this->product, $result);
    }
}
