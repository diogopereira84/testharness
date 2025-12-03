<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Model;

use Magento\Framework\Model\AbstractModel;

class Patch extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Fedex\PatchData\Model\ResourceModel\Patch::class);
    }
}
