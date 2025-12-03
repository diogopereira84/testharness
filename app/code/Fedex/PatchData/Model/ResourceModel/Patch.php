<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Patch extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('fxo_patch_list', 'patch_id');
    }
}
