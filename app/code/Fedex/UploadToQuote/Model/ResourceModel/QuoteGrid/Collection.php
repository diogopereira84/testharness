<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model\ResourceModel\QuoteGrid;

use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid as QuoteGridResourceModel;
use Fedex\UploadToQuote\Model\QuoteGrid as QuoteGridModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * collection class for negotialble quote grid
 */
class Collection extends AbstractCollection
{
    /**
     * Initilize resource model and model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(QuoteGridModel::class, QuoteGridResourceModel::class);
    }
}
