<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\OrderApprovalB2b\Model\ResourceModel\OrderGrid as OrderGridResourceModel;

/**
 * Class for OrderGrid Model
 */
class OrderGrid extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderGridResourceModel::class);
    }
}
