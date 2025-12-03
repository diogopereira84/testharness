<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class for EnhanceRolePermission
 * @codeCoverageIgnore
 */
class EnhanceRolePermission extends AbstractModel
{
    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission::class);
    }
}