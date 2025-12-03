<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\CoreApi\Model\Config;

use Fedex\CoreApi\Model\Config\AbstractConfig;

class Backend extends AbstractConfig
{
    private const PREFIX_KEY = 'fedex/general';

    /**
     * @return string
     */
    protected function getConfigPrefix(): string
    {
        return self::PREFIX_KEY;
    }
}
