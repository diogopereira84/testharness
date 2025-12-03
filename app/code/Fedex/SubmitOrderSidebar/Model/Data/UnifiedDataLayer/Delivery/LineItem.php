<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayer\Delivery;

use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterface;
use Magento\Framework\DataObject;

class LineItem extends DataObject implements LineItemInterface
{
    /**
     * Order product name key
     */
    private const PRODUCT_NAME = 'productName';

    /**
     * Order sku id key
     */
    private const SKU_ID = 'skuId';

    /**
     * Order quantity key
     */
    private const QUANTITY = 'quantity';

    /**
     * Order shipping/item price key
     */
    private const PRICE = 'price';

    /**
     * @inheritDoc
     */
    public function getProductName(): string
    {
        return $this->getData(self::PRODUCT_NAME) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setProductName(string $productName): LineItemInterface
    {
        return $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * @inheritDoc
     */
    public function getSkuId(): string
    {
        return $this->getData(self::SKU_ID) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setSkuId(string $skuId): LineItemInterface
    {
        return $this->setData(self::SKU_ID, $skuId);
    }

    /**
     * @inheritDoc
     */
    public function getQuantity(): string
    {
        return $this->getData(self::QUANTITY) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setQuantity(string $quantity): LineItemInterface
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): string
    {
        return $this->getData(self::PRICE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setPrice(string $price): LineItemInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $keys = []): array
    {
        return [
            self::PRODUCT_NAME => $this->getProductName(),
            self::SKU_ID => $this->getSkuId(),
            self::QUANTITY => $this->getQuantity(),
            self::PRICE => $this->getPrice(),
        ];
    }
}
