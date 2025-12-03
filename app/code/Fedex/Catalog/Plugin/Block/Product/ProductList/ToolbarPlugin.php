<?php

namespace Fedex\Catalog\Plugin\Block\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Fedex\Catalog\Block\Product\ProductLIst\Toolbar as ProductListToolbar;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class ToolbarPlugin
{
    /**
     * ToolbarPlugin constructor.
     *
     * @param catalogMvp $catalogMvp
     */
    public function __construct(
        protected catalogMvp $catalogMvp
    )
    {
    }

    /**
     * @param  Toolbar|ProductListToolbar  $subject
     * @param  callable $proceed
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetAvailableOrders(Toolbar|ProductListToolbar $subject, callable $proceed)
    {
        $result = $proceed();
        $currentCategory = $this->catalogMvp->getCurrentCategory();
        if (empty($currentCategory) && array_key_exists('price', $result)) {
            unset($result['price']);
        }
        if (!empty($result['product_updated_date'])) {
            unset($result['product_updated_date']);
        }
        /* B-1573026 */
        if ($subject->isMvpCatalogEnabled()) {
            $isAllowedOptions = [
                'price',
                'name',
                'position',
                'updated_at',
                'relevance'
            ];
            foreach ($isAllowedOptions as $isAllowedOption) {
                unset($result[$isAllowedOption]);
            }

            $result['name_asc'] = 'Name (A to Z)';
            $result['name_desc'] = 'Name (Z to A)';
            $result['most_recent'] = 'Date Modified';
           
        }
        return $result;
    }

    /**
     * @param  Toolbar $subject
     * @param  $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsEnabledViewSwitcher(Toolbar $subject, $result) : bool
    {
        if (empty($this->catalogMvp->getCurrentCategory())) {
            return false;
        }
        return $result;
    }
}
