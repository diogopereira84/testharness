<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 *  PsgCustomerFields ResourceModel class
 */
class PsgCustomerFields extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('psg_customer_fields', 'entity_id');
    }
}
