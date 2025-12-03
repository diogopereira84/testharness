<?php

declare(strict_types=1);

namespace Fedex\Shipment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DueDateLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('fedex_due_date_log', 'log_id');
    }
}
