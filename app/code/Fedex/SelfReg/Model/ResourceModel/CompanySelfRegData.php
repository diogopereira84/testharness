<?php
/**
 * B-1257375 - Add configuration for site access
 * (use access flow- all registered users, users from specific domain and admin approval)
 * 
 */

namespace Fedex\SelfReg\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CompanySelfRegData extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init('company_selfreg', 'id');
    }
}
