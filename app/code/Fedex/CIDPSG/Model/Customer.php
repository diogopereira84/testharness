<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\CIDPSG\Model\ResourceModel\Customer as ResourceModel;

/**
 * Customer Model
 */
class Customer extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
