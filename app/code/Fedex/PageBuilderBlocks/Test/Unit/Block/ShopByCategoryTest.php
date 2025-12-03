<?php
/**
 * @category Fedex
 * @package Fedex_PageBuilderBlocks
 * @copyright Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Test\Unit\Block;

use Fedex\PageBuilderBlocks\Block\ShopByCategory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DB\Select;

class ShopByCategoryTest extends TestCase
{
    private const PARENT_CATEGORY_URL_KEY_PARAM = 'parent-category-url-key';
    private const CATEGORY_URL_KEY_PARAM = 'category-url-key';
    private const CATEGORY_URL = 'http://example.com/parent-category';

    private ShopByCategory $block;
    private $contextMock;
    private $categoryCollectionFactoryMock;
    private $productCollectionFactoryMock;
    private $categoryCollectionMock;
    private $productCollectionMock;
    private $imageHelperMock;
    private $selectMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->categoryCollectionFactoryMock = $this->createMock(CategoryCollectionFactory::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);

        $this->categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $this->productCollectionMock = $this->createMock(ProductCollection::class);
        $this->imageHelperMock = $this->createMock(ImageHelper::class);

        $this->selectMock = $this->createMock(Select::class);

        $this->categoryCollectionFactoryMock
            ->method('create')
            ->willReturn($this->categoryCollectionMock);

        $this->productCollectionFactoryMock
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->block = new ShopByCategory(
            $this->contextMock,
            $this->categoryCollectionFactoryMock,
            $this->productCollectionFactoryMock,
            $this->imageHelperMock,
            []
        );
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testGetCategories(): void
    {
        $this->block->setData('category_ids', '1,2,3');

        $this->categoryCollectionMock
            ->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['name', 'url_key', 'image'])
            ->willReturnSelf();

        $this->categoryCollectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', ['in' => ['1', '2', '3']])
            ->willReturnSelf();

        $this->categoryCollectionMock
            ->expects($this->once())
            ->method('addIsActiveFilter')
            ->willReturnSelf();

        $this->selectMock
            ->expects($this->once())
            ->method('reset')
            ->willReturnSelf();

        $this->selectMock
            ->expects($this->once())
            ->method('order')
            ->willReturnSelf();

        $this->categoryCollectionMock
            ->expects($this->once())
            ->method('getSelect')
            ->willReturn($this->selectMock);

        $result = $this->block->getCategories();
        $this->assertInstanceOf(CategoryCollection::class, $result);
    }

    /**
     * @return void
     */
    public function testGetProducts(): void
    {
        $this->block->setData('product_skus', 'sku1,sku2,sku3');

        $this->productCollectionMock
            ->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(['name', 'sku', 'image', 'url_key'])
            ->willReturnSelf();

        $this->productCollectionMock
            ->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('sku', ['in' => ['sku1', 'sku2', 'sku3']])
            ->willReturnSelf();

        $this->productCollectionMock
            ->expects($this->once())
            ->method('getSelect')
            ->willReturn($this->selectMock);

        $this->selectMock
            ->expects($this->once())
            ->method('reset')
            ->willReturnSelf();

        $this->selectMock
            ->expects($this->once())
            ->method('order')
            ->willReturnSelf();

        $result = $this->block->getProducts();
        $this->assertInstanceOf(ProductCollection::class, $result);
    }

    /**
     * @return void
     */
    public function testShouldShowCategories(): void
    {
        $this->block->setData('category_ids', '1,2,3');
        $result = $this->block->shouldShowCategories();
        $this->assertTrue($result);

        $this->block->unsetData('category_ids');
        $result = $this->block->shouldShowCategories();
        $this->assertFalse($result);
    }

    /**
     * @return void
     */
    public function testShouldShowProducts(): void
    {
        $this->block->setData('product_skus', 'sku1,sku2');
        $result = $this->block->shouldShowProducts();
        $this->assertTrue($result);

        $this->block->setData('category_ids', '1,2,3');
        $result = $this->block->shouldShowProducts();
        $this->assertFalse($result);
    }

    /**
     * @return void
     */
    public function testGetBlockTitle(): void
    {
        $result = $this->block->getBlockTitle();
        $this->assertEquals('Shop by type', $result);
    }

    /**
     * @return void
     */
    public function testGetNoCategoriesOrProductsToDisplay(): void
    {
        $result = $this->block->getNoCategoriesOrProductsToDisplay();
        $this->assertEquals('No categories or products to display.', (string)$result);
    }

    /**
     * @return void
     */
    public function testGetAdaptedCategoryLink(): void
    {
        $categoryMock = $this->createMock(Category::class);
        $parentCategoryMock = $this->createMock(Category::class);
        $categoryMock
            ->expects($this->once())
            ->method('getParentCategory')
            ->willReturn($parentCategoryMock);

        $parentCategoryMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $parentCategoryMock
            ->expects($this->once())
            ->method('getUrl')
            ->willReturn(self::CATEGORY_URL);

        $parentCategoryMock
            ->expects($this->once())
            ->method('getUrlKey')
            ->willReturn(self::PARENT_CATEGORY_URL_KEY_PARAM);

        $categoryMock
            ->expects($this->once())
            ->method('getUrlKey')
            ->willReturn(self::CATEGORY_URL_KEY_PARAM);

        $result = $this->block->getAdaptedCategoryLink($categoryMock);
        $this->assertEquals(
            self::CATEGORY_URL . "?categories=" . self::PARENT_CATEGORY_URL_KEY_PARAM . "%2F"
            . self::CATEGORY_URL_KEY_PARAM,
            $result
        );
    }
}