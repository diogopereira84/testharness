<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @codeCoverageIgnore
 */
class UserPreference extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('pod2_user_preference', 'id');
    }
}
