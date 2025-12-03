<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Model\Config;

use Fedex\ProductCustomAtrribute\Model\Config\AbstractConfig;

class Backend extends AbstractConfig
{
    private const PREFIX_KEY                = 'product_engine/general';
    public const XPATH_PRODUCT_ENGINE_URL   = 'url';

    public function getProductEngineUrl()
    {
        return (string)$this->getScopeValue(self::XPATH_PRODUCT_ENGINE_URL);
    }

    protected function getConfigPrefix(): string
    {
        return self::PREFIX_KEY;
    }
}
