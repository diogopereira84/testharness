<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model\ResourceModel\PsgCustomerFields;

use Fedex\CIDPSG\Model\ResourceModel\PsgCustomerFields as PsgCustomerFieldsResourceModel;
use Fedex\CIDPSG\Model\PsgCustomerFields as PsgCustomerFieldsModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * collection class for PsgCustomerFieldsTest
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
        $this->_init(PsgCustomerFieldsModel::class, PsgCustomerFieldsResourceModel::class);
    }
}
