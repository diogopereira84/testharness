<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Model\ResourceModel\Patch;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fedex\PatchData\Model\Patch as PatchModel;
use Fedex\PatchData\Model\ResourceModel\Patch as PatchResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(PatchModel::class, PatchResourceModel::class);
    }
}
