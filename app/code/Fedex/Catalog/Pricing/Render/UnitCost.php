<?php
declare(strict_types=1);

namespace Fedex\Catalog\Pricing\Render;

use Magento\Framework\Pricing\Render\PriceBox as BasePriceBox;
use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\App\ObjectManager;

/**
 * Class for final_price rendering
 *
 * @method bool getUseLinkForAsLowAs()
 * @method bool getDisplayMinimalPrice()
 */
class UnitCost extends BasePriceBox
{
    /**
     * @var SalableResolverInterface
     */
    private $salableResolver;

    /**
     * @param Context $context
     * @param SaleableInterface $saleableItem
     * @param PriceInterface $price
     * @param RendererPool $rendererPool
     * @param array $data
     * @param SalableResolverInterface $salableResolver
     */
    public function __construct(
        Context $context,
        SaleableInterface $saleableItem,
        PriceInterface $price,
        RendererPool $rendererPool,
        array $data = [],
        SalableResolverInterface $salableResolver = null
    ) {
        parent::__construct($context, $saleableItem, $price, $rendererPool, $data);
        $this->salableResolver = $salableResolver ?: ObjectManager::getInstance()->get(SalableResolverInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        if (!$this->salableResolver->isSalable($this->getSaleableItem())) {
            return '';
        }

        $result = parent::_toHtml();

        return $this->wrapResult($result);
    }

    /**
     * Wrap with standard required container
     *
     * @param string $html
     * @return string
     */
    protected function wrapResult($html)
    {
        return '<div class="price-box ' . $this->getData('css_classes') . '" ' .
            'data-role="priceBox" ' .
            'data-product-id="' . $this->getSaleableItem()->getId() . '" ' .
            'data-price-box="product-id-' . $this->getSaleableItem()->getId() . '"' .
            '>' . $html . '</div>';
    }

    /**
     * Define if the special price should be shown
     *
     * @return bool
     */
    public function hasUnitCost()
    {
        return $this->getPriceType(\Fedex\Catalog\Pricing\Price\UnitCost::PRICE_CODE)->getAmount()->getValue() >= 0;
    }

    /**
     * Get Key for caching block content
     *
     * @return string
     */
    public function getCacheKey()
    {
        return parent::getCacheKey()
            . ($this->getData('list_category_page') ? '-list-category-page': '')
            . ($this->getSaleableItem()->getCustomerGroupId() ?? '');
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo()
    {
        $cacheKeys = parent::getCacheKeyInfo();
        $cacheKeys['display_minimal_price'] =  false;
        $cacheKeys['is_product_list'] = false;
        $cacheKeys['customer_group_id'] = $this->getSaleableItem()->getCustomerGroupId();
        $cacheKeys['zone'] = $this->getZone();
        return $cacheKeys;
    }
}
