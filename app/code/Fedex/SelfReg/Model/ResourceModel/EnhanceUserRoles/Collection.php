<?php
/**
 * B-1257375 - Add configuration for site access
 * (use access flow- all registered users, users from specific domain and admin approval)
 *
 */

namespace Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles;

use Fedex\SelfReg\Model\EnhanceUserRoles as EnhanceUserRolesModel;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles as EnhanceUserRolesResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize collection
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(EnhanceUserRolesModel::class, EnhanceUserRolesResourceModel::class);
    }
}
