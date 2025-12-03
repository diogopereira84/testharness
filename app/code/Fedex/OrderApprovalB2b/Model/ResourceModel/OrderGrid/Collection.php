<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Model\ResourceModel\OrderGrid;

use Fedex\OrderApprovalB2b\Model\ResourceModel\OrderGrid as OrderGridResourceModel;
use Fedex\OrderApprovalB2b\Model\OrderGrid as OrderGridModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * collection class for order grid
 */
class Collection extends AbstractCollection
{
    /**
     * Initilize resource model and model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderGridModel::class, OrderGridResourceModel::class);
    }
}
