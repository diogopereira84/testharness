<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Adminhtml\Ui\Component\Listing\Column\Order;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Fedex\MarketplaceAdmin\Model\Config;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Flag extends \Mirakl\Adminhtml\Ui\Component\Listing\Column\Order\Flag
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderHelper $orderHelper
     * @param OrderFactory $orderFactory
     * @param OrderResourceFactory $orderResourceFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        private Config $config,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderHelper $orderHelper,
        OrderFactory $orderFactory,
        OrderResourceFactory $orderResourceFactory,
        readonly private ToggleConfig $toggleConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $orderHelper, $orderFactory, $orderResourceFactory, $components, $data);
    }

    /**
     * @ingeritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if(!$this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')){
            if (!$this->config->isMktSelfregEnabled()) {
                return parent::prepareDataSource($dataSource);
            }

            if (isset($dataSource['data']['items'])) {
                foreach ($dataSource['data']['items'] as &$item) {
                    $item[$this->getData('name')] = $this->decorationFlag($item);
                }
            }

            return $dataSource;
        }
        return parent::prepareDataSource($dataSource);
    }

    /**
     * Handles decoration based on flag column of sales_order_grid.
     *
     * @param   array   $item
     * @return  string
     */
    public function decorationFlag(array $item): string
    {
        $class = Config::OPERATOR_CLASS;
        $label = __(Config::OPERATOR_LABEL);
        if (isset($item['flag']) && $item['flag'] == Config::ORIGIN_MARKETPLACE) {
            $class = Config::MARKETPLACE_CLASS;
            $label = __(Config::MARKETPLACE_LABEL);
        } elseif (isset($item['flag']) && $item['flag'] == Config::ORIGIN_MIXED) {
            $class = Config::MIXED_CLASS;
            $label = __(Config::MIXED_LABEL);
        }
        return sprintf('<span class="%s">%s</span>', $class, $label);
    }
}
