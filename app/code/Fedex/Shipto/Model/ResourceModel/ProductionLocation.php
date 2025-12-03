<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\Shipto\Model\ResourceModel;

class ProductionLocation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * {​​​​​@inheritdoc}​​​​​
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init('epro_production_location', 'id');
    }
}
