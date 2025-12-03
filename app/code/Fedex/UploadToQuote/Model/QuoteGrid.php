<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid as QuoteGridResourceModel;

/**
 * Class for QuoteGridResourceModel
 */
class QuoteGrid extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(QuoteGridResourceModel::class);
    }
}

