<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Block\Adminhtml;

use Magento\Backend\Block\Template;
class LocationUrl extends Template
{
    /**
     * @return string
     */
    public function getLocationUrl(): string
    {
        return $this->getUrl('inbranch/location/get');
    }
}
