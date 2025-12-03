<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Model\Catalog\Bundle;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Load child bundle products
 */
class Products
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ){

    }

    /**
     * Get All Products from Bundle
     *
     * @param ProductInterface $bundleProduct
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getBundleChildProducts(ProductInterface $bundleProduct): array
    {
        /** @var Type $bundleTypeInstance */
        $bundleTypeInstance = $bundleProduct->getTypeInstance();

        $childIds = $bundleTypeInstance->getChildrenIds($bundleProduct->getId());
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $childIds, 'in')
            ->create();

        return $this->productRepository->getList($searchCriteria)->getItems();
    }
}
