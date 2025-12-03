<?php

namespace Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression;

use Fedex\OptimizeProductinstance\Model\QuoteCompression as QuoteCompressionModel;
use Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression as QuoteCompressionResourceModel;
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
            QuoteCompressionModel::class,
            QuoteCompressionResourceModel::class
        );
    }
}
