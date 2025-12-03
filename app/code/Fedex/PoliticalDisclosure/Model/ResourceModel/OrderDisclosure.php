<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrderDisclosure extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('fedex_sales_order_political_disclosure', 'entity_id');
    }
}
