<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class DisableProductDuplicate
{

    public function __construct(
        private CatalogMvp $helper
    )
    {
    }

    public function afterIsDuplicable(
        Product $subject,
        $result
    ) {
        $isMvp = $this->helper->isMvpCtcAdminEnable();
        $sku = $subject->getSku();
        if ($sku) {
            $isLegacy = $this->helper->getIsLegacyItemBySku($sku);
            if($isMvp && $isLegacy){
                return false;
            }
        }
        return $result;
    }
}
