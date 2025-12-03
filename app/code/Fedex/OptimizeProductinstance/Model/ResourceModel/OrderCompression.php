<?php

namespace Fedex\OptimizeProductinstance\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrderCompression extends AbstractDb
{
    /**
     * OrderCompression
     */
    protected function _construct()
    {
        $this->_init('temp_order_compression', 'id');
    }
}
