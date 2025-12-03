<?php
/**
 * @category Fedex
 * @package Fedex_PageBuilderBlocks
 * @copyright Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Block;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Catalog\Helper\Image;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ShopByCategory extends Template
{
    /**
     * Constant for block title
     */
    private const BLOCK_TITLE = 'Shop by type';

    /**
     * Constructor
     *
     * @param Context $context
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Image $imageHelper
     * @param array $data
     */
    public function __construct(
        private readonly Context $context,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly Image $imageHelper,
        array $data = []
    ) {
        parent::__construct($this->context, $data);
    }
    /**
     * Get categories by IDs
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws LocalizedException
     */
    public function getCategories(): \Magento\Catalog\Model\ResourceModel\Category\Collection
    {
        $categoryIdsString = $this->getData('category_ids');
        $categoryIds = array_map('trim', explode(',', $categoryIdsString));

        $categoryCollection = $this->categoryCollectionFactory->create();
        
        if (empty($categoryIds)) {
            return $categoryCollection;
        }

        $categoryCollection->addAttributeToSelect(['name', 'url_key', 'image'])
            ->addFieldToFilter('entity_id', ['in' => $categoryIds])
            ->addIsActiveFilter();

        $categoryCollection->getSelect()
            ->reset(\Magento\Framework\DB\Select::ORDER)
            ->order(
                new \Zend_Db_Expr("FIELD(e.entity_id, {$categoryIdsString})")
            );

        return $categoryCollection;
    }

    /**
     * Get products by SKUs
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProducts(): \Magento\Catalog\Model\ResourceModel\Product\Collection
    {
        $productSkusString = $this->getData('product_skus');
        $productSkus = array_map('trim', explode(',', $productSkusString));

        $productCollection = $this->productCollectionFactory->create();
        
        if (empty($productSkus)) {
            return $productCollection;
        }

        $productCollection->addAttributeToSelect(['name', 'sku', 'image', 'url_key'])
            ->addAttributeToFilter('sku', ['in' => $productSkus]);

        $quotedSkus = array_map(fn($sku) => "'$sku'", $productSkus);
        $skusString = implode(',', $quotedSkus);

        $productCollection->getSelect()
            ->reset(\Magento\Framework\DB\Select::ORDER)
            ->order(
                new \Zend_Db_Expr("FIELD(e.sku, $skusString)")
            );

        return $productCollection;
    }

    /**
     * Get the image URL of the product
     *
     * @param Product $product
     * @return string
     */
    public function getProductImgUrl(Product $product): string
    {
        return $this->imageHelper->init($product, 'product_base_image')->getUrl();
    }

    /**
     * Get the adapted category link with parent category URL
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    public function getAdaptedCategoryLink(\Magento\Catalog\Model\Category $category): string
    {
        $parentCategory = $category->getParentCategory();
        
        if ($parentCategory && $parentCategory->getId()) {
            
            $parentCategoryUrl = $parentCategory->getUrl();
            $parentCategoryUrlKey = $parentCategory->getUrlKey();
            $categoryUrlKey = $category->getUrlKey();
            
            return $parentCategoryUrl . '?categories=' . $parentCategoryUrlKey . '%2F' . $categoryUrlKey;
        }

        return $category->getUrl();
    }

    /**
     * Determine if categories should be shown
     *
     * @return bool
     */
    public function shouldShowCategories(): bool
    {
        return (bool) $this->getData('category_ids');
    }

    /**
     * Determine if products should be shown
     *
     * @return bool
     */
    public function shouldShowProducts(): bool
    {
        return $this->getData('product_skus') && !$this->getData('category_ids');
    }

    /**
     * Get block title
     *
     * @return string
     */
    public function getBlockTitle(): string
    {
        return self::BLOCK_TITLE;
    }

    /**
     * Get no categories or products to display message
     *
     * @return Phrase
     */
    public function getNoCategoriesOrProductsToDisplay(): Phrase
    {
        return __('No categories or products to display.');
    }
}
