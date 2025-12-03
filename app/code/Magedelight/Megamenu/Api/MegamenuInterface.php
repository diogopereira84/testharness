<?php

namespace Magedelight\Megamenu\Api;

interface MegamenuInterface
{
    /**
     * Get info about product by product SKU
     *
     * @return \Magedelight\Megamenu\Api\Data\ConfigInterface
     */
    public function getMenu();
}