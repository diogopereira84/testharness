<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Helper\MageWorx\Json;

use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Page\Config;
use Magento\Theme\Block\Html\Pager;
use MageWorx\SeoAll\Helper\SeoFeaturesStatusProvider;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use MageWorx\SeoMarkup\Helper\Product;

/**
 * Category Helper class
 */
class Category extends \MageWorx\SeoMarkup\Helper\Json\Category
{
    public function __construct(
        Registry $registry,
        \MageWorx\SeoMarkup\Helper\Category $helperCategory,
        \MageWorx\SeoMarkup\Helper\DataProvider\Category $dataProviderCategory,
        \MageWorx\SeoMarkup\Helper\DataProvider\Product $dataProviderProduct,
        Layout $layout,
        UrlInterface $urlBuilder,
        Config $pageConfig,
        Product $helperProduct,
        Data $helperCatalog,
        PriceCurrencyInterface $priceCurrency,
        SeoFeaturesStatusProvider $seoFeaturesStatusProvider,
        private DeliveryHelper $deliveryHelper,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig
    ) {
        parent::__construct(
            $registry,
            $helperCategory,
            $dataProviderCategory,
            $dataProviderProduct,
            $layout,
            $urlBuilder,
            $pageConfig,
            $helperProduct,
            $helperCatalog,
            $priceCurrency,
            $seoFeaturesStatusProvider
        );
    }

    /**
     *
     * @return Collection|null
     */
    protected function getProductCollection()
    {
        static $commercialCustomer = null;
        if ($commercialCustomer === null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            $commercialCustomer = $this->deliveryHelper->isCommercialCustomer();
        }
        if ($commercialCustomer
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return null;
        }

        if ($this->deliveryHelper->isCommercialCustomer()) {
            return null;
        }

        $productList = $this->layout->getBlock('category.products.list');

        if (is_object($productList) && ($productList instanceof ListProduct)) {
            return $productList->getLoadedProductCollection();
        }

        /**
         * @var Pager $pager
         */
        $pager = $this->layout->getBlock('product_list_toolbar_pager');
        if (!is_object($pager)) {
            $pager = $this->getPagerFromToolbar();
        } elseif (!$pager->getCollection()) {
            $pager = $this->getPagerFromToolbar();
        }

        if (!is_object($pager)) {
            return null;
        }

        return $pager->getCollection();
    }

}
