<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Model for auth rows.
 */
class AuthDynamicRows extends AbstractModel
{
   /**
    * _construct
    * @codeCoverageIgnore
    */
    protected function _construct()
    {
        $this->_init('Fedex\Company\Model\ResourceModel\AuthDynamicRows');
    }
}
