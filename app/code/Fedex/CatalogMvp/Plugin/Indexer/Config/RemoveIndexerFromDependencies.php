<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\Indexer\Config;

use Magento\Framework\Indexer\Config\DependencyInfoProvider;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\CatalogPermissions\Model\Indexer\Product\Processor as ProductProcessor;
use Magento\CatalogPermissions\Model\Indexer\Category\Processor as CategoryProcessor;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Class RemoveIndexerFromDependencies
 *
 * Remove the Product Price Indexer from dependencies
 */
class RemoveIndexerFromDependencies
{
    /**
     * CatalogMvp Class Constructor.
     *
     * @param CatalogMvp $helper
     */
    public function __construct(
        protected CatalogMvp $helper
    )
    {
    }

    /**
     * Intercept list of indexers and remove the product price indexer from the list
     *
     * @param DependencyInfoProvider $subject
     * @param array[] $result
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIndexerIdsToRunBefore(
        DependencyInfoProvider $subject,
        array $result
    ): array {
            /** @var string $key */
            if (($key = array_search(Processor::INDEXER_ID, $result, true)) !== false) {
                unset($result[$key]);
            }

            if (($key = array_search(ProductProcessor::INDEXER_ID, $result, true)) !== false) {
                unset($result[$key]);
            }

            if (($key = array_search(CategoryProcessor::INDEXER_ID, $result, true)) !== false) {
                unset($result[$key]);
            }
        return $result;
    }

    /**
     * Intercept list of indexers and remove the product price indexer from the list
     *
     * @param DependencyInfoProvider $subject
     * @param array[] $result
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIndexerIdsToRunAfter(DependencyInfoProvider $subject, array $result): array
    {
            /** @var string $key */
            if (($key = array_search(Processor::INDEXER_ID, $result, true)) !== false) {
                unset($result[$key]);
            }

            if (($key = array_search(ProductProcessor::INDEXER_ID, $result, true)) !== false) {
                unset($result[$key]);
            }

            if (($key = array_search(CategoryProcessor::INDEXER_ID, $result, true)) !== false) {
                unset($result[$key]);
            }

        return $result;
    }
}
