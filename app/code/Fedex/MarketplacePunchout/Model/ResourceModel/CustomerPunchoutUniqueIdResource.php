<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\ResourceModel;

use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @codeCoverageIgnore
 * Class CustomerPunchoutUniqueIdResource
 */
class CustomerPunchoutUniqueIdResource extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_punchout_unique_id_resource';

    /**
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('customer_punchout_unique_id', CustomerPunchoutUniqueIdInterface::CUSTOMER_ID);
        $this->_useIsObjectNew = true;
    }
}
