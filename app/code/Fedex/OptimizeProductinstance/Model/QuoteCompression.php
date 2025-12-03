<?php

namespace Fedex\OptimizeProductinstance\Model;

use Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression as QuoteCompressionResourceModel;
use Magento\Framework\Model\AbstractModel;

class QuoteCompression extends AbstractModel
{
    /**
     * QuoteCompression
     */
    protected function _construct()
    {
        $this->_init(QuoteCompressionResourceModel::class);
    }
}
