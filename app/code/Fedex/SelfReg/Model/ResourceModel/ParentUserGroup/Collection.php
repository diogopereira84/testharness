<?php

declare (strict_types = 1);

namespace Fedex\SelfReg\Model\ResourceModel\ParentUserGroup;

use Fedex\SelfReg\Model\ParentUserGroup as ParentUserGroupModel;
use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup as ParentUserGroupResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize collection
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ParentUserGroupModel::class, ParentUserGroupResourceModel::class);
    }
}
