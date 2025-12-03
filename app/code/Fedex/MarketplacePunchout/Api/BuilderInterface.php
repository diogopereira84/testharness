<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Api;

use Magento\Framework\Simplexml\Element;

interface BuilderInterface
{
    /**
     * @param $productSku
     * @param mixed|null $productConfigData
     * @return Element
     */
    public function build($productSku = null, mixed $productConfigData = null): Element;
}
