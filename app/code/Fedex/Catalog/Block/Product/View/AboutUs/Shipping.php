<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Block\Product\View\AboutUs;

use Fedex\Catalog\Model\Config;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\View\Element\Template;

class Shipping extends Template
{
    /**
     * Shipping constructor.
     * @param Template\Context $context
     * @param CatalogHelper $catalogHelper
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private CatalogHelper $catalogHelper,
        private Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getProductShippingInfo(): string
    {
        $product = $this->catalogHelper->getProduct();
        if ($product) {
            $shipping = $this->config->formatAttribute($product, 'shipping_estimator_content_new');
            return $shipping ?? '';
        }

        return '';
    }
}
