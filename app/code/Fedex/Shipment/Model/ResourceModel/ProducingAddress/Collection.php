<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model\ResourceModel\ProducingAddress;

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
        $this->_init(
            \Fedex\Shipment\Model\ProducingAddress::class,
            \Fedex\Shipment\Model\ResourceModel\ProducingAddress::class
        );
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
