<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\ResourceModel\Quote;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IntegrationItem extends AbstractDb
{
    /**
     * Resource initialisation
     */
    protected function _construct()
    {
        $this->_init('quote_integration_item', 'integration_item_id');
    }
}
