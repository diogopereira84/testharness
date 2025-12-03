<?php

namespace Fedex\OptimizeProductinstance\Model;

use Fedex\OptimizeProductinstance\Model\ResourceModel\OrderCompression as OrderCompressionResourceModel;
use Magento\Framework\Model\AbstractModel;

class OrderCompression extends AbstractModel
{
    /**
     * OrderCompression
     */
    protected function _construct()
    {
        $this->_init(OrderCompressionResourceModel::class);
    }
}
