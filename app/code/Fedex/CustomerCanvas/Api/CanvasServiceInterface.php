<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Api;

use Magento\Catalog\Api\Data\ProductInterface;

interface CanvasServiceInterface
{
    /**
     * @param $productId
     * @return bool
     */
    public function isExpired($productId): bool;

    /**
     * @return array
     */
    public function getRequiredCanvasParams(): array;
}
