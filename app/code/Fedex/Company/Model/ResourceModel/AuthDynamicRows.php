<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Model for auth rows.
 */
class AuthDynamicRows extends AbstractDb
{
   /**
    * _construct
    * @codeCoverageIgnore
    */
    protected function _construct()
    {

        $this->_init('company_auth_rule', 'role_id');
    }
}
