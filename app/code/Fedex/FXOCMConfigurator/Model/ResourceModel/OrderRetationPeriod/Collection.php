<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Fedex\FXOCMConfigurator\Model\OrderRetationPeriod::class,
            \Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod::class
        );
    }
}

