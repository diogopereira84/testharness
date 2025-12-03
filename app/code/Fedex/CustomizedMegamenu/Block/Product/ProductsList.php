<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Block\Product;

use Fedex\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product;
use Magento\CatalogWidget\Block\Product\ProductsList as WidgetProductList;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;

/**
 * Custimized class for ProductsList widget
 */
class ProductsList extends WidgetProductList
{
    const ALLOWED_UNIT_COST_BLOCKS = ['product_most_popular_image', 'shop_by_type_grid'];
    const PRICE_BOX_CLASS = 'price-box price-final_price';

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param HttpContext $httpContext
     * @param SqlBuilder $sqlBuilder
     * @param Rule $rule
     * @param Conditions $conditionsHelper
     * @param CategoryFactory $categoryFactory
     * @param protectedCatalogConfig $catalogConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @param Json|null $json
     * @param LayoutFactory|null $layoutFactory
     * @param EncoderInterface|null $urlEncoder
     * @param CategoryRepositoryInterface|null $categoryRepository
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        protected Context                     $context,
        CollectionFactory           $productCollectionFactory,
        Visibility                  $catalogProductVisibility,
        HttpContext                 $httpContext,
        SqlBuilder                  $sqlBuilder,
        Rule                        $rule,
        Conditions                  $conditionsHelper,
        protected CategoryFactory             $categoryFactory,
        protected CatalogConfig     $catalogConfig,
        protected PriceCurrencyInterface    $priceCurrency,
        array                       $data = [],
        Json                        $json = null,
        LayoutFactory               $layoutFactory = null,
        EncoderInterface            $urlEncoder = null,
        CategoryRepositoryInterface $categoryRepository = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;

        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $httpContext,
            $sqlBuilder,
            $rule,
            $conditionsHelper,
            $data,
            $json ?: ObjectManager::getInstance()->get(Json::class),
            $layoutFactory ?: ObjectManager::getInstance()->get(LayoutFactory::class),
            $urlEncoder ?: ObjectManager::getInstance()->get(EncoderInterface::class),
            $categoryRepository ?? ObjectManager::getInstance()
                ->get(CategoryRepositoryInterface::class)
        );
    }

    /**
     * Check conditions and get products with postions
     *
     * @return array
     */
    public function getMenuCategoryProductsPostions()
    {
        $conditions = $this->getData('conditions_encoded')
            ? $this->getData('conditions_encoded')
            : $this->getData('conditions');

        if ($conditions) {
            $conditions = $this->conditionsHelper->decode($conditions);
        }

        $categoryId = '';
        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute']) && $condition['attribute'] == 'category_ids' &&
            array_key_exists('value', $condition)) {
                $categoryId = $condition['value'];
            }
        }
        if (isset($categoryId)) {
            $category = $this->categoryFactory->create();
            $category->load($categoryId);

            return $category->getProductsPosition();
        } else {
            return null;
        }
    }

    /**
     * Get Products sorted according to category positions
     *
     * @return array $products
     */
    public function getProducts()
    {
        $productPositions = $this->getMenuCategoryProductsPostions();
        asort($productPositions);
        $productsCountLimit = $this->getData('products_count');
        $productCount = $count = 0;
        $products = $productIds = [];
        foreach ($productPositions as $key => $val) {
            if ($count < $productsCountLimit) {
                $productIds[] = $key;
                $count++;
            }
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);

        foreach ($collection as $product) {
            $products[$productCount]['id'] = $product->getId();
            $products[$productCount]['url'] = $product->getProductUrl();
            $products[$productCount]['name'] = $product->getName();
            $products[$productCount]['position'] = $productPositions[$product->getId()];
            $productCount++;
        }

        return $products;
    }

    /**
     * @return bool
     */
    public function getTigerDisplayUnitCost3P1PProducts()
    {
        return $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts();
    }

    /**
     * Check if Toggle for 3P Unit Cost is enabled + If product has unit cost filled + if it's shop by type
     *
     * @param $_item
     * @return bool
     */
    public function isNewUnitCostAvailable($_item, $productImageAttribute): bool
    {
        return $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts()
            && in_array($productImageAttribute, self::ALLOWED_UNIT_COST_BLOCKS)
            && $_item->getData('unit_cost');
    }

    /**
     * Return Unit Cost formatted with container
     * @param $_item
     * @return string
     */
    public function getProductUnitCost($_item): string
    {
        $unitCost = $_item->getData('unit_cost');

        return $this->priceCurrency->convertAndFormat($unitCost);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
        array $arguments = [],
        $productImageAttribute = null
    ) {
        $price = parent::getProductPriceHtml($product, $priceType, $renderZone, $arguments);

        if ($this->catalogConfig->getTigerDisplayUnitCost3P1PProducts()
            && in_array($productImageAttribute, self::ALLOWED_UNIT_COST_BLOCKS)) {
            return str_replace(self::PRICE_BOX_CLASS, self::PRICE_BOX_CLASS.' unit-cost', $price);
        }

        return $price;
    }
}
