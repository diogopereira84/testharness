<?php

namespace Fedex\OptimizeProductinstance\Model\ResourceModel\OrderCompression;

use Fedex\OptimizeProductinstance\Model\OrderCompression as OrderCompressionModel;
use Fedex\OptimizeProductinstance\Model\ResourceModel\OrderCompression as OrderCompressionResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    /**
     * Collection
     */
    protected function _construct()
    {
        $this->_init(
            OrderCompressionModel::class,
            OrderCompressionResourceModel::class
        );
    }
}
