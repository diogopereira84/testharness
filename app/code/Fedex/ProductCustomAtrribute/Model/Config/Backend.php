<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\ProductCustomAtrribute\Model\Config;

use Fedex\ProductCustomAtrribute\Model\Config\AbstractConfig;

class Backend extends AbstractConfig
{
    private const PREFIX_KEY = 'fedex/canva_link';
    /**
     * @return string
     */
    protected function getConfigPrefix(): string
    {
        return self::PREFIX_KEY;
    }
}
