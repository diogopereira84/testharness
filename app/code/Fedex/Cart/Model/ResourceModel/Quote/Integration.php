<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\ResourceModel\Quote;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Integration extends AbstractDb
{
    /**
     * Resource initialisation
     */
    protected function _construct()
    {
        $this->_init('quote_integration', 'integration_id');
    }
}
