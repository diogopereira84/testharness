<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\SharedCatalog\Model\ProductManagement;
use Magento\SharedCatalog\Model\State as SharedCatalogState;
use Magento\SharedCatalog\Model\Management as SharedCatalogManagement;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class UpdateCatalogObserver implements ObserverInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private FilterBuilder $filterBuilder,
        private ProductManagement $sharedCatalog,
        private SharedCatalogState $sharedCatalogState,
        private SharedCatalogManagement $sharedCatalogManagement,
        private LoggerInterface $logger,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    ) {}

    public function execute(Observer $observer): void
    {
        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
            $bunch = $observer->getEvent()->getBunch();
            $skus = [];

            if (empty($bunch)) {
                return;
            }

            foreach ($bunch as $row) {
                if (!empty($row['sku'])) {
                    $skus[] = $row['sku'];
                }
            }

            if (empty($skus)) {
                return;
            }

            try {
                $filter = $this->filterBuilder
                    ->setField('sku')
                    ->setConditionType('in')
                    ->setValue($skus)
                    ->create();

                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilters([$filter])
                    ->create();

                $products = $this->productRepository->getList($searchCriteria)->getItems();

                if (!empty($products)) {
                    $sharedCatalogId = $this->sharedCatalogManagement->getPublicCatalog()->getId();
                    $this->sharedCatalog->assignProducts($sharedCatalogId, array_values($products));
                }

            } catch (\Throwable $e) {
                $this->logger->error('[CatalogImportObserver] Failed to assign products: ' . $e->getMessage());
            }
        }
    }
}
