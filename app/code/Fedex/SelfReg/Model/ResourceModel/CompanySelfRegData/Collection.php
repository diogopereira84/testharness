<?php
/**
 * B-1257375 - Add configuration for site access
 * (use access flow- all registered users, users from specific domain and admin approval)
 *
 */

namespace Fedex\SelfReg\Model\ResourceModel\CompanySelfRegData;

use Fedex\SelfReg\Model\CompanySelfRegData as CompanySelfRegDataModel;
use Fedex\SelfReg\Model\ResourceModel\CompanySelfRegData as CompanySelfRegDataResourceModel;
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
        $this->_init(CompanySelfRegDataModel::class, CompanySelfRegDataResourceModel::class);
    }
}
