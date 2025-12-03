<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\MarketplaceProduct\Api\Data\OfferInterface;
use Fedex\MarketplaceProduct\Api\OfferRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Connector\Model\ResourceModel\Offer as Resource;

class OfferRepository implements OfferRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @param OfferFactory $factory
     * @param Resource $resource
     */
    public function __construct(
        private OfferFactory $factory,
        private Resource $resource
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getById(string $shopId): OfferInterface
    {
        if (!isset($this->instances[$shopId])) {
            $shop = $this->factory->create();
            $this->resource->load($shop, $shopId, 'product_sku');
            if (!$shop->getId()) {
                throw new NoSuchEntityException(__('Could not find offer id: %id.', ['id' => $shopId]));
            }
            $this->instances[$shopId] = $shop;
        }
        return $this->instances[$shopId];
    }
}
