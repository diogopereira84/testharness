<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogSearch\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CatalogSearchResult implements ArgumentInterface
{
    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $_toggleConfig;

    /**
     * CatalogSearchResult constructor
     *
     * @param Session $customerSession
     * @param ToggleConfig $toggleConfig
     * @return void
     */
    public function __construct(
        Session $customerSession,
        ToggleConfig $toggleConfig
    ) {
        $this->_customerSession = $customerSession;
        $this->_toggleConfig = $toggleConfig;
    }

    /**
     * Get customer group product search result collection
     *
     * @param object $productCollection
     * @return object $productCollection
     */
    public function searchProductCollection($productCollection)
    {
        $customerGroup = $this->_customerSession->getCustomer()->getGroupId();
        
        return $productCollection->getSelect()
                                    ->join(
                                        'shared_catalog_product_item',
                                        'e.sku = shared_catalog_product_item.sku',
                                        []
                                    )->where("shared_catalog_product_item.customer_group_id=?", $customerGroup);
    }
}
