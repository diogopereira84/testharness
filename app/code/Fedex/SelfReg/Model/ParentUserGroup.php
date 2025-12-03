<?php

declare (strict_types = 1);

namespace Fedex\SelfReg\Model;

use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup as ParentUserGroupResourceModel;
use Magento\Framework\Model\AbstractModel;

class ParentUserGroup extends AbstractModel
{
    /**
     * Company id field name
     */
    const PARENT_GROUP_ID = 'parent_group_id';

    /**
     * Initialize model
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ParentUserGroupResourceModel::class);
    }
}
