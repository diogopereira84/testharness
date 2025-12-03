<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model\ResourceModel\Customer;

use Fedex\CIDPSG\Model\ResourceModel\Customer as PsgResourceModel;
use Fedex\CIDPSG\Model\Customer as PsgModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initilize resource model and model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(PsgModel::class, PsgResourceModel::class);
    }
}
