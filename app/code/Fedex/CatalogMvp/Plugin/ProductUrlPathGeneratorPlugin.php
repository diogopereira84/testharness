<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Plugin;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;

class ProductUrlPathGeneratorPlugin
{
    /**
     * Constructor.
     *
     * @param CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        protected CatalogMvp $catalogMvpHelper
    )
    {
    }

    /**
     * Get URL Key
     *
     * @param ProductUrlPathGenerator $subject
     * @param $result
     * @param $product
     * @return string
     */
    public function afterGetUrlKey(ProductUrlPathGenerator $subject, $result, $product)
    {
            $isAttributeSetPrintOnDemand = $this->catalogMvpHelper->isAttributeSetPrintOnDemand(
                $product->getAttributeSetId()
            );
            $urlKey = $product->getUrlKey();
            if ($isAttributeSetPrintOnDemand && ($urlKey === '' || $urlKey === null)) {
                $result = $product->getSku();
            }
           return $result;
    }
}