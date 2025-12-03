<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\App\Request\Http;

class ProductListHelper
{
    /**
     * @param CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected Http $request
    )
    {
    }

    public function afterGetAvailableViewMode(
        \Magento\Catalog\Helper\Product\ProductList $subject,
        $result
    ) {
        if (!$this->catalogMvpHelper->isMvpSharedCatalogEnable()) {
            return $result;
        } else {
            $actionName = $this->request->getFullActionName();
            if ($actionName == 'catalogsearch_result_index'){
                return $result;
            } else {
                return ['list' => __('List'), 'grid' => __('Grid')];
            }
        }
        
    }
}
