<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Core\Model\ResourceModel\Shop as Resource;

class ShopRepository implements ShopRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @param ShopFactory $factory
     * @param Resource $resource
     */
    public function __construct(
        private ShopFactory $factory,
        private Resource $resource
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getById(int $shopId): ShopInterface
    {
        if (!isset($this->instances[$shopId])) {
            $shop = $this->factory->create();
            $this->resource->load($shop, $shopId);

            if (!$shop->getId()) {
                throw new NoSuchEntityException(__('Could not find shop id: %id.', ['id' => $shopId]));
            }
            $this->instances[$shopId] = $shop;
        }

        return $this->instances[$shopId];
    }

    public function getByIds(array $shopIds): array
    {
        $shops = [];
        foreach (array_unique($shopIds) as $id) {
            $shops[$id] = $this->getById((int)$id);
        }
        return $shops;
    }
}
