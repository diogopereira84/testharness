<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Model;

use \Magento\Framework\Model\AbstractModel;

class ProductionLocation extends AbstractModel
{
    /**
     * 
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(\Fedex\Shipto\Model\ResourceModel\ProductionLocation::class);
    }
}
