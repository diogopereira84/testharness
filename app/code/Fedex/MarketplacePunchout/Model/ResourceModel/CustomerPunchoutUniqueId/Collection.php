<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueId;

use Fedex\MarketplacePunchout\Model\CustomerPunchoutUniqueId;
use Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueIdResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @codeCoverageIgnore
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_punchout_unique_id_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(CustomerPunchoutUniqueId::class, CustomerPunchoutUniqueIdResource::class);
    }
}
