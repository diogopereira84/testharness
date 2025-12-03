<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\Shipto\Model\ResourceModel\ProductionLocation;

use Fedex\Shipto\Model\ProductionLocation as Model;
use Fedex\Shipto\Model\ResourceModel\ProductionLocation as ResourceModel;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    
    /**
     * Define resource model
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
