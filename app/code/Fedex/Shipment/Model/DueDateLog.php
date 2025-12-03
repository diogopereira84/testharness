<?php

declare(strict_types=1);

namespace Fedex\Shipment\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\Shipment\Model\ResourceModel\DueDateLog as ResourceModel;

class DueDateLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
