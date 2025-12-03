<?php

declare(strict_types=1);

namespace Fedex\Shipment\Model\ResourceModel\DueDateLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fedex\Shipment\Model\DueDateLog as Model;
use Fedex\Shipment\Model\ResourceModel\DueDateLog as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
