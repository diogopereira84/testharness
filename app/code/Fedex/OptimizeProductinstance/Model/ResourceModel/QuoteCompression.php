<?php

namespace Fedex\OptimizeProductinstance\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuoteCompression extends AbstractDb
{
    /**
     * QuoteCompression
     */
    protected function _construct()
    {
        $this->_init('temp_quote_compression', 'id');
    }
}
