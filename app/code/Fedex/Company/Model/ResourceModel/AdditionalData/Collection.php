<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\ResourceModel\AdditionalData;

use Fedex\Company\Model\AdditionalData as AdditionalDataModel;
use Fedex\Company\Model\ResourceModel\AdditionalData as AdditionalDataResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize collection
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AdditionalDataModel::class, AdditionalDataResourceModel::class);
    }
}
