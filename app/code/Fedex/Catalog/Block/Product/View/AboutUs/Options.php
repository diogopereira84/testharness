<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Block\Product\View\AboutUs;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Fedex\Catalog\Model\Config;

class Options extends Template
{
    /**
     * Options constructor.
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
    public function getProductOptions(): string
    {
        $product = $this->catalogHelper->getProduct();
        if ($product) {
            return $this->config->formatAttribute($product, 'product_options') ?? '';
        }

        return '';
    }
}
