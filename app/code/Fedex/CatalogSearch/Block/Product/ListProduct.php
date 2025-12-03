<?php
/**
 * @category Fedex
 * @package Fedex_CatalogSearch
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CatalogSearch\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\UrlFactory;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    protected UrlFactory $_urlFactory;

    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        UrlFactory $urlFactory,
        array $data = [],
        ?OutputHelper $outputHelper = null
    ) {
        $this->_urlFactory = $urlFactory;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data, $outputHelper);
    }

    /**
     * Get form URL.
     *
     * @return string
     */
    public function getFormUrl()
    {
        return $this->_urlFactory->create()->addQueryParams(
            $this->getRequest()->getQueryValue()
        )->getUrl(
            '*/*/',
            ['_escape' => true]
        );
    }
}
