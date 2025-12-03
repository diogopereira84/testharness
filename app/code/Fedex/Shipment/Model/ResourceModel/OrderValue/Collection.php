<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model\ResourceModel\OrderValue;

/**
 * @codeCoverageIgnore
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Fedex\Shipment\Model\OrderValue::class, \Fedex\Shipment\Model\ResourceModel\OrderValue::class);
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
