<?php
declare(strict_types=1);

namespace Fedex\Catalog\Block\Product\ProductList;

use Fedex\Catalog\Model\Config;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Related as ParentRelated;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Checkout\Model\ResourceModel\Cart as CartResourceModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Module\Manager;

class Related extends ParentRelated
{
    public function __construct(
        Context $context,
        CartResourceModel $checkoutCart,
        ProductVisibility $catalogProductVisibility,
        CheckoutSession $checkoutSession,
        Manager $moduleManager,
        protected Config $config,
        array $data = []
    ) {
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager, $data);
    }

    /**
     * Prepare data
     *
     * @return $this
     */
    protected function _prepareData()
    {
        /* @var $product Product */
        $product = $this->getProduct();

        $this->_itemCollection = $product->getRelatedProductCollection()->addAttributeToSelect(
            'required_options'
        )->setPositionOrder()->addStoreFilter();

        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * Prepare data
     *
     * @return $this
     */
    protected function newPrepareData()
    {
        /* @var $product Product */
        $product = $this->getProduct();

        $this->_itemCollection = $product->getRelatedProductCollection()->addAttributeToSelect(
            'required_options'
        )->setPositionOrder()->addStoreFilter();

        $this->_itemCollection->addAttributeToSelect($this->_catalogConfig->getProductAttributes());
        $this->_itemCollection->addAttributeToFilter(
            'visibility',
            $this->_catalogProductVisibility->getVisibleInCatalogIds()
        );

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * Before to html handler
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->newPrepareData();
        return $this;
    }

    /**
     * Get collection items
     *
     * @return Collection
     */
    public function getItems()
    {
        if ($this->_itemCollection === null) {
            $this->newPrepareData();
        }
        return $this->_itemCollection;
    }

}
