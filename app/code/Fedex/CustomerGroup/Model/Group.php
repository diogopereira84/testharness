<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Model;

use Magento\Customer\Model\Group as GroupOriginal;

class Group extends GroupOriginal
{
    const GROUP_CODE_MAX_LENGTH = 200;

    /**
     * Prepare customer group data
     *
     * @return $this
     */
    protected function _prepareData()
    {
        $this->setCode(substr($this->getCode(), 0, self::GROUP_CODE_MAX_LENGTH));
        return $this;
    }
}
