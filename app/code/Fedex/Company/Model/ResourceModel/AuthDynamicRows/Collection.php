<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Model\ResourceModel\AuthDynamicRows;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'role_id';

   /**
    * _construct
    * @codeCoverageIgnore
    */
    protected function _construct()
    {
        $this->_init(
            'Fedex\Company\Model\AuthDynamicRows',
            'Fedex\Company\Model\ResourceModel\AuthDynamicRows'
        );
    }
}
