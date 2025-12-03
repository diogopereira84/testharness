<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Model\ResourceModel\UserPreference;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Fedex\Login\Model\UserPreference', 'Fedex\Login\Model\ResourceModel\UserPreference');
    }
}
