<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CustomerCanvasProductFetcher
{
    public const IS_CUSTOMER_CANVAS = 'is_customer_canvas';

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {}

    /**
     * @return array
     */
    public function get(): array
    {
        $this->searchCriteriaBuilder->addFilter(self::IS_CUSTOMER_CANVAS, 1);
        return $this->productRepository->getList($this->searchCriteriaBuilder->create())->getItems();
    }
}
