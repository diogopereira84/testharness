<?php
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Block\SearchResult;

use Magento\Catalog\Block\Product\ListProduct as ListProductCore;
use Magento\Catalog\Model\Layer\Search;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class ListProduct
{
    /**
     * @param ListProductCore $subject
     * @param AbstractCollection $result
     * @return AbstractCollection
     */
    public function afterGetLoadedProductCollection(ListProductCore $subject, $result)
    {
        // Check if layer is the one used on the search page to refresh size of collection
        if ($subject->getLayer() instanceof Search) {

            //Clear collection to reset $this->_totalRecords
            $result->clear();
            //Call getSize to set $this->_totalRecords
            $result->getSize();
        }

        return $result;
    }
}
