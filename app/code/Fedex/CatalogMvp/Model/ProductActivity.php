<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class for ProductActivity
 * @codeCoverageIgnore
 */
class ProductActivity extends AbstractModel
{
    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Fedex\CatalogMvp\Model\ResourceModel\ProductActivity::class);
    }
}