<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * @codeCoverageIgnore
 */
class UserPreference extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Fedex\Login\Model\ResourceModel\UserPreference');
    }
}
