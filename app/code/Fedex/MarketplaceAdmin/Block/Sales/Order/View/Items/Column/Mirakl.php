<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Block\Sales\Order\View\Items\Column;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceRates\Helper\Data;

class Mirakl extends \Mirakl\Adminhtml\Block\Sales\Order\View\Items\Column\Mirakl
{
    /**
     * @param ToggleConfig $toggleConfig
     * @param Data $helper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\Product\OptionFactory $optionFactory
     * @param array $data
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private Data         $helper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        array $data = []
    )
    {
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $optionFactory, $data);
    }

    /**
     * @param $order
     * @param $item
     * @return mixed
     */
    public function getCustomShippingTypeLabel($order, $item): mixed
    {
        if (!$this->getMiraklShipping($order,$item)) {
            return $item->getMiraklShippingTypeLabel();
        }
        return $this->getMiraklShipping($order,$item);
    }

    /**
     * @param $order
     * @param $item
     * @return mixed|null
     */
    public function getMiraklShipping($order,$item): mixed
    {
        $shipping = $this->helper->getMktShipping($order,$item);

        if (!$shipping) {
            return null;
        }
        return $shipping['method_title'];
    }
}
